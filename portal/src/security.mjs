import crypto from "node:crypto";

const KEY_LENGTH = 64;
const scrypt = (password, salt) => new Promise((resolve, reject) =>
  crypto.scrypt(password, salt, KEY_LENGTH, { N: 16384, r: 8, p: 1 }, (error, key) => error ? reject(error) : resolve(key)));

export async function hashPassword(password) {
  if (typeof password !== "string" || password.length < 12 || password.length > 1024) throw new Error("Password must be 12-1024 characters");
  const salt = crypto.randomBytes(16).toString("hex");
  return `scrypt$16384$${salt}$${(await scrypt(password, salt)).toString("hex")}`;
}

export async function verifyPassword(password, stored) {
  if (typeof password !== "string" || typeof stored !== "string") return false;
  const parts = stored.split("$");
  if (parts.length !== 4 || parts[0] !== "scrypt" || parts[1] !== "16384" || !/^[0-9a-f]{32}$/.test(parts[2]) || !/^[0-9a-f]{128}$/.test(parts[3])) return false;
  const actual = await scrypt(password, parts[2]);
  return crypto.timingSafeEqual(actual, Buffer.from(parts[3], "hex"));
}

export function escapeHtml(input) {
  return String(input ?? "").replace(/[&<>"']/g, x => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[x]);
}

export function readCookie(header, name) {
  for (const pair of (header || "").split(";")) {
    const index = pair.indexOf("=");
    if (index !== -1 && pair.slice(0, index).trim() === name) return pair.slice(index + 1).trim();
  }
  return null;
}

export function sameOrigin(request) {
  const origin = request.headers.origin;
  if (!origin || !process.env.PORTAL_ORIGIN) return false;
  return origin === process.env.PORTAL_ORIGIN;
}
