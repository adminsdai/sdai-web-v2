// Check permission and record scope together on every server endpoint.
export function mayReadAccount(identity, account) {
  if (!identity || identity.status !== "ACTIVE") return false;
  if (identity.permissions.includes("crm:read:all")) return true;
  return identity.permissions.includes("crm:read:own") && account.ownerId === identity.id;
}

export function mayAccessTask(identity, task, operation) {
  if (!identity || identity.status !== "ACTIVE") return false;
  const permission = operation === "read" ? "kanban:read:all" : "kanban:write:all";
  if (identity.permissions.includes(permission)) return true;
  const own = operation === "read" ? "kanban:read:own" : "kanban:write:own";
  return identity.permissions.includes(own) && task.assigneeId === identity.id;
}

export function mayManagePrivacy(identity) {
  return Boolean(identity?.status === "ACTIVE" && identity.permissions.includes("privacy:manage"));
}

export function mayRunMotor(identity) {
  return Boolean(identity?.status === "ACTIVE" && identity.permissions.includes("motor:execute"));
}
