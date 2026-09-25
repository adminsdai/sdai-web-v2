const ROLES = {
  ADMIN: ["*"],
  CONSULTOR: ["assessment:read","assessment:review","crm:read","crm:write","kanban:read","kanban:write","privacy:read","motor:read","motor:execute","motor:review","motor:catalog","motor:report"],
  ANALISTA: ["assessment:read","crm:read","kanban:read","kanban:write","privacy:read","motor:read"],
  AUDITOR: ["assessment:read","crm:read","crm:audit","kanban:read","privacy:read","privacy:audit","motor:read"],
  COMERCIAL: ["crm:read","crm:write","kanban:read"]
};

export const PERMISSIONS = [...new Set(Object.values(ROLES).flat().filter(p => p !== "*").concat([
  "crm:users","kanban:users","privacy:manage","motor:rates","identity:users","identity:roles","identity:audit"
]))].sort();

export const ROLE_PERMISSIONS = Object.freeze(ROLES);

export function can(roleCodes, permission) {
  const roles = Array.isArray(roleCodes) ? roleCodes : [roleCodes];
  return roles.some(role => {
    const grants = ROLE_PERMISSIONS[String(role ?? "").toUpperCase()] ?? [];
    return grants.includes("*") || grants.includes(permission);
  });
}

export function assertPermission(identity, permission) {
  if (!identity?.userId) {
    const e = new Error("AUTHENTICATION_REQUIRED"); e.status = 401; throw e;
  }
  if (!can(identity.roles ?? identity.role, permission)) {
    const e = new Error("FORBIDDEN"); e.status = 403; throw e;
  }
  return true;
}
