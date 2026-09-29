import http from "node:http";
import { PrismaClient } from "../generated/client/index.js";
import { createSession, resolveSession, revokeSession, COOKIE_NAME, sessionCookie, clearSessionCookie } from "./session.mjs";
import { verifyPassword, escapeHtml as e, readCookie, sameOrigin } from "./security.mjs";
import { mayReadAccount, mayAccessTask, mayManagePrivacy } from "./authorization.mjs";

if (!process.env.DATABASE_URL || !process.env.PORTAL_ORIGIN) throw new Error("DATABASE_URL and PORTAL_ORIGIN are required");
const origin = new URL(process.env.PORTAL_ORIGIN);
if (origin.protocol !== "https:" && origin.hostname !== "127.0.0.1" && origin.hostname !== "localhost") throw new Error("Portal requires HTTPS outside localhost");
const db = new PrismaClient();
const attempts = new Map();
const secure = origin.protocol === "https:";
const port = Number(process.env.PORT || 3100);

function html(res, body, status = 200, headers = {}) {
  res.writeHead(status, { "Content-Type": "text/html; charset=utf-8", "Cache-Control": "no-store", "X-Content-Type-Options": "nosniff", "Referrer-Policy": "no-referrer", "Content-Security-Policy": "default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'", ...headers });
  res.end(`<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Portal SDAI</title><style>body{font:16px system-ui;margin:0;background:#071624;color:#f3f7fa}main{max-width:900px;margin:5vh auto;padding:24px}a{color:#80d8ef}nav{display:flex;gap:20px;flex-wrap:wrap}article{background:#102739;border:1px solid #2b5166;border-radius:12px;padding:20px;margin:20px 0}input,select,button{font:inherit;padding:10px;margin:5px;border-radius:6px}input{max-width:100%}button{background:#e1bb75;border:0;cursor:pointer}li{margin:10px 0}.muted{color:#a7b8c1}</style><main>${body}</main></html>`);
}
function redirect(res, path, cookie) { res.writeHead(303, { Location: path, ...(cookie ? { "Set-Cookie": cookie } : {}), "Cache-Control": "no-store" }); res.end(); }
function cookie(value) { return secure ? value : value.replace("; Secure;", ";"); }
async function form(req) {
  let data = "";
  for await (const part of req) { data += part; if (data.length > 16_384) throw new Error("Request too large"); }
  if (!req.headers["content-type"]?.startsWith("application/x-www-form-urlencoded")) throw new Error("Invalid content type");
  return new URLSearchParams(data);
}
function can(user, permission) { return user?.permissions.includes(permission); }
function layout(user, title, content) {
  const links = ["<a href='/'>Inicio</a>", ...(can(user, "crm:read:all") || can(user, "crm:read:own") ? ["<a href='/crm'>CRM</a>"] : []), ...(can(user, "kanban:read:all") || can(user, "kanban:read:own") ? ["<a href='/kanban'>Kanban</a>"] : []), ...(mayManagePrivacy(user) ? ["<a href='/privacidad'>Privacidad</a>"] : [])];
  return `<h1>${e(title)}</h1><nav>${links.join(" ")}<form method="post" action="/logout"><button>Salir</button></form></nav>${content}`;
}
async function handler(req, res) {
  const path = new URL(req.url, origin).pathname;
  if (req.method === "POST" && !sameOrigin(req)) return html(res, "<h1>Origen no autorizado</h1>", 403);
  const token = readCookie(req.headers.cookie, COOKIE_NAME);
  const user = await resolveSession(db, token);
  if (path === "/login" && req.method === "GET") {
    if (user) return redirect(res, "/");
    return html(res, `<h1>Portal SDAI</h1><article><form method="post" action="/login"><label>Correo <input name="email" type="email" required autocomplete="username"></label><br><label>Contraseña <input name="password" type="password" required autocomplete="current-password"></label><br><button>Ingresar</button></form></article>`);
  }
  if (path === "/login" && req.method === "POST") {
    const ip = req.socket.remoteAddress || "unknown";
    const entry = attempts.get(ip) || { count: 0, reset: Date.now() + 15 * 60_000 };
    if (entry.reset < Date.now()) { entry.count = 0; entry.reset = Date.now() + 15 * 60_000; }
    if (entry.count >= 10) return html(res, "<h1>Intenta más tarde</h1>", 429);
    const fields = await form(req);
    const email = fields.get("email")?.trim().toLowerCase() || "";
    const record = email.length <= 254 ? await db.user.findUnique({ where: { email } }) : null;
    if (!record || record.status !== "ACTIVE" || !await verifyPassword(fields.get("password"), record.passwordHash)) {
      entry.count++; attempts.set(ip, entry);
      return html(res, "<h1>Credenciales incorrectas</h1><a href='/login'>Volver</a>", 401);
    }
    attempts.delete(ip);
    const session = await createSession(db, record.id);
    await db.auditEvent.create({ data: { actorId: record.id, action: "LOGIN", entity: "Session", outcome: "SUCCESS" } });
    return redirect(res, "/", cookie(sessionCookie(session.token, session.expiresAt)));
  }
  if (!user) return req.method === "GET" ? redirect(res, "/login") : html(res, "<h1>Sesión requerida</h1>", 401);
  if (path === "/logout" && req.method === "POST") { await revokeSession(db, token); return redirect(res, "/login", cookie(clearSessionCookie())); }
  if (path === "/" && req.method === "GET") return html(res, layout(user, "Portal SDAI", "<article>Sesión activa. Usa el menú para ingresar a tus módulos autorizados.</article>"));
  if (path === "/crm" && req.method === "GET") {
    if (!can(user, "crm:read:all") && !can(user, "crm:read:own")) return html(res, "<h1>Acceso denegado</h1>", 403);
    const accounts = await db.account.findMany({ where: can(user, "crm:read:all") ? {} : { ownerId: user.id }, orderBy: { createdAt: "desc" }, take: 100 });
    const list = accounts.filter(x => mayReadAccount(user, x)).map(x => `<li>${e(x.name)} · ${e(x.taxId || "Sin RUT")}</li>`).join("");
    const add = can(user, "crm:write:all") ? `<form method="post" action="/crm"><input name="name" placeholder="Empresa" required maxlength="200"><input name="taxId" placeholder="RUT" maxlength="30"><button>Crear empresa</button></form>` : "";
    return html(res, layout(user, "CRM", `<article>${add}<ul>${list}</ul></article>`));
  }
  if (path === "/crm" && req.method === "POST") {
    if (!can(user, "crm:write:all")) return html(res, "<h1>Acceso denegado</h1>", 403);
    const fields = await form(req), name = fields.get("name")?.trim(), taxId = fields.get("taxId")?.trim();
    if (!name || name.length > 200 || taxId?.length > 30) return html(res, "<h1>Datos inválidos</h1>", 400);
    const account = await db.account.create({ data: { name, taxId: taxId || null, ownerId: user.id } });
    await db.auditEvent.create({ data: { actorId: user.id, action: "CREATE", entity: "Account", entityId: account.id, outcome: "SUCCESS" } });
    return redirect(res, "/crm");
  }
  if (path === "/kanban" && req.method === "GET") {
    if (!can(user, "kanban:read:all") && !can(user, "kanban:read:own")) return html(res, "<h1>Acceso denegado</h1>", 403);
    const tasks = await db.task.findMany({ where: can(user, "kanban:read:all") ? {} : { assigneeId: user.id }, orderBy: { createdAt: "desc" }, take: 100 });
    const list = tasks.filter(x => mayAccessTask(user, x, "read")).map(x => `<li>${e(x.title)} · ${e(x.status)} ${mayAccessTask(user, x, "write") ? `<form method="post" action="/kanban/move"><input type="hidden" name="id" value="${e(x.id)}"><select name="status"><option>BACKLOG</option><option>IN_PROGRESS</option><option>BLOCKED</option><option>DONE</option></select><button>Mover</button></form>` : ""}</li>`).join("");
    const add = can(user, "kanban:write:all") || can(user, "kanban:write:own") ? `<form method="post" action="/kanban"><input name="title" placeholder="Nueva tarea" required maxlength="220"><button>Crear tarea propia</button></form>` : "";
    return html(res, layout(user, "Kanban", `<article>${add}<ul>${list}</ul></article>`));
  }
  if (path === "/kanban" && req.method === "POST") {
    if (!can(user, "kanban:write:all") && !can(user, "kanban:write:own")) return html(res, "<h1>Acceso denegado</h1>", 403);
    const title = (await form(req)).get("title")?.trim();
    if (!title || title.length > 220) return html(res, "<h1>Datos inválidos</h1>", 400);
    const task = await db.task.create({ data: { title, assigneeId: user.id } });
    await db.auditEvent.create({ data: { actorId: user.id, action: "CREATE", entity: "Task", entityId: task.id, outcome: "SUCCESS" } });
    return redirect(res, "/kanban");
  }
  if (path === "/kanban/move" && req.method === "POST") {
    const fields = await form(req), id = fields.get("id"), status = fields.get("status");
    if (!/^[0-9a-f-]{36}$/i.test(id || "") || !["BACKLOG", "IN_PROGRESS", "BLOCKED", "DONE"].includes(status)) return html(res, "<h1>Datos inválidos</h1>", 400);
    const task = await db.task.findUnique({ where: { id } });
    if (!task || !mayAccessTask(user, task, "write")) return html(res, "<h1>Acceso denegado</h1>", 403);
    await db.$transaction(async tx => {
      // Recheck assignment in the write predicate to close the read/write race.
      const changed = await tx.task.updateMany({ where: { id, ...(can(user, "kanban:write:all") ? {} : { assigneeId: user.id }) }, data: { status } });
      if (changed.count !== 1) throw new Error("Task assignment changed");
      await tx.auditEvent.create({ data: { actorId: user.id, action: "MOVE", entity: "Task", entityId: id, outcome: "SUCCESS" } });
    });
    return redirect(res, "/kanban");
  }
  if (path === "/privacidad" && req.method === "GET") {
    if (!mayManagePrivacy(user)) return html(res, "<h1>Acceso denegado</h1>", 403);
    const requests = await db.privacyRequest.findMany({ orderBy: { receivedAt: "desc" }, take: 100 });
    const list = requests.map(x => `<li>${e(x.requestType)} · ${e(x.requesterName)} · ${e(x.status)}</li>`).join("");
    return html(res, layout(user, "Privacidad", `<article><p class="muted">Bandeja interna. Una solicitud ingresada aquí queda pendiente de verificar; el canal público requiere verificación del titular.</p><form method="post" action="/privacidad"><select name="requestType"><option>ACCESO</option><option>RECTIFICACION</option><option>CANCELACION</option><option>OPOSICION</option><option>PORTABILIDAD</option><option>BLOQUEO</option></select><input name="name" placeholder="Nombre del solicitante" required maxlength="160"><input name="email" type="email" placeholder="Correo" required maxlength="254"><button>Registrar solicitud</button></form><ul>${list}</ul></article>`));
  }
  if (path === "/privacidad" && req.method === "POST") {
    if (!mayManagePrivacy(user)) return html(res, "<h1>Acceso denegado</h1>", 403);
    const fields = await form(req);
    const requestType = fields.get("requestType"), requesterName = fields.get("name")?.trim(), requesterEmail = fields.get("email")?.trim().toLowerCase();
    if (!["ACCESO", "RECTIFICACION", "CANCELACION", "OPOSICION", "PORTABILIDAD", "BLOQUEO"].includes(requestType) || !requesterName || requesterName.length > 160 || !requesterEmail || requesterEmail.length > 254 || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(requesterEmail)) return html(res, "<h1>Datos inválidos</h1>", 400);
    await db.$transaction(async tx => {
      const request = await tx.privacyRequest.create({ data: { requestType, requesterName, requesterEmail } });
      await tx.privacyRequestEvent.create({ data: { requestId: request.id, type: "RECEIVED_INTERNAL", detail: "Pendiente de verificar identidad del titular" } });
      await tx.auditEvent.create({ data: { actorId: user.id, action: "CREATE", entity: "PrivacyRequest", entityId: request.id, outcome: "SUCCESS" } });
    });
    return redirect(res, "/privacidad");
  }
  return html(res, "<h1>No encontrado</h1>", 404);
}
const server = http.createServer((req, res) => handler(req, res).catch(error => { console.error(error); if (!res.headersSent) html(res, "<h1>Error interno</h1>", 500); else res.end(); }));
server.listen(port, "127.0.0.1", () => console.log(`Portal listening on http://127.0.0.1:${port}`));
