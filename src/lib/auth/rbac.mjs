/**
 * AUTHZ-001 — RBAC mínimo para superficies privadas SDAI.
 * Autenticación (sesión/passkey) resuelve userId; este módulo autoriza.
 */
export const ROLE_PERMISSIONS = Object.freeze({
  ADMIN: ["*"],
  CONSULTOR: [
    "assessment:read","assessment:review",
    "cost:execute","cost:review",
    "catalog:read","rates:read",
    "report:generate"
  ],
  ANALISTA: [
    "assessment:read",
    "catalog:read"
  ]
});

export function normalizeRole(role) {
  return String(role ?? "").trim().toUpperCase();
}

export function can(role, permission) {
  const grants = ROLE_PERMISSIONS[normalizeRole(role)] ?? [];
  return grants.includes("*") || grants.includes(permission);
}

export function assertPermission(user, permission) {
  if (!user?.userId) {
    const error = new Error("AUTHENTICATION_REQUIRED");
    error.status = 401;
    throw error;
  }
  if (!can(user.role, permission)) {
    const error = new Error("FORBIDDEN");
    error.status = 403;
    throw error;
  }
  return true;
}
