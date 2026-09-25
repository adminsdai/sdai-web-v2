import crypto from "node:crypto";

export const SESSION_COOKIE = "sdai_session";
export const SESSION_TTL_MS = 8 * 60 * 60 * 1000;

export function hashSessionToken(token) {
  return crypto.createHash("sha256").update(token).digest("hex");
}

export function newSessionToken() {
  return crypto.randomBytes(32).toString("base64url");
}

export async function createIdentitySession({ prisma, userId, ip, userAgent, ttlMs = SESSION_TTL_MS }) {
  const token = newSessionToken();
  const tokenHash = hashSessionToken(token);
  const expiresAt = new Date(Date.now() + ttlMs);
  await prisma.identitySession.create({
    data: {
      userId,
      tokenHash,
      expiresAt,
      ipHash: ip ? crypto.createHash("sha256").update(ip).digest("hex") : null,
      userAgent: userAgent?.slice(0, 500) || null
    }
  });
  return { token, expiresAt };
}

export async function resolveIdentitySession({ prisma, token }) {
  if (!token) return null;
  const tokenHash = hashSessionToken(token);
  const session = await prisma.identitySession.findUnique({ where: { tokenHash } });
  if (!session || session.revokedAt || session.expiresAt <= new Date()) return null;

  const user = await prisma.authorizedUser.findUnique({
    where: { userId: session.userId },
    include: { identityRoles: { include: { role: true } } }
  });
  if (!user) return null;

  await prisma.identitySession.update({
    where: { id: session.id },
    data: { lastSeenAt: new Date() }
  });

  return {
    userId: user.userId,
    roles: user.identityRoles.filter(x => x.role.active).map(x => x.role.code)
  };
}

export async function revokeIdentitySession({ prisma, token }) {
  if (!token) return;
  const tokenHash = hashSessionToken(token);
  await prisma.identitySession.updateMany({
    where: { tokenHash, revokedAt: null },
    data: { revokedAt: new Date() }
  });
}

export function sessionCookie(token, expiresAt) {
  return [
    `${SESSION_COOKIE}=${encodeURIComponent(token)}`,
    "Path=/",
    "HttpOnly",
    "Secure",
    "SameSite=Lax",
    `Expires=${expiresAt.toUTCString()}`
  ].join("; ");
}
