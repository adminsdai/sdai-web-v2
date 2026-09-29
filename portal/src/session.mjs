import crypto from "node:crypto";

const TTL_MS = 8 * 60 * 60 * 1000;
export const COOKIE_NAME = "sdai_portal_session";
export const hashToken = token => crypto.createHash("sha256").update(token).digest("hex");

export async function createSession(db, userId, now = new Date()) {
  const user = await db.user.findUnique({ where: { id: userId }, select: { status: true } });
  if (user?.status !== "ACTIVE") throw new Error("User is not active");
  const token = crypto.randomBytes(32).toString("base64url");
  const expiresAt = new Date(now.getTime() + TTL_MS);
  await db.session.create({ data: { userId, tokenHash: hashToken(token), expiresAt } });
  return { token, expiresAt };
}

export async function resolveSession(db, token, now = new Date()) {
  if (typeof token !== "string" || !/^[A-Za-z0-9_-]{43}$/.test(token)) return null;
  const session = await db.session.findUnique({
    where: { tokenHash: hashToken(token) },
    include: { user: { include: { roles: { include: { role: { include: { permissions: { include: { permission: true } } } } } } } } }
  });
  if (!session || session.revokedAt || session.expiresAt <= now || session.user.status !== "ACTIVE") return null;
  return {
    id: session.userId,
    status: session.user.status,
    permissions: [...new Set(session.user.roles.flatMap(x => x.role.permissions.map(y => y.permission.code)))]
  };
}

export async function revokeSession(db, token, now = new Date()) {
  if (typeof token !== "string" || !/^[A-Za-z0-9_-]{43}$/.test(token)) return;
  await db.session.updateMany({ where: { tokenHash: hashToken(token), revokedAt: null }, data: { revokedAt: now } });
}

export function sessionCookie(token, expiresAt) {
  return `${COOKIE_NAME}=${token}; Path=/; HttpOnly; Secure; SameSite=Lax; Expires=${expiresAt.toUTCString()}`;
}

export function clearSessionCookie() {
  return `${COOKIE_NAME}=; Path=/; HttpOnly; Secure; SameSite=Lax; Max-Age=0`;
}
