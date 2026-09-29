import { PrismaClient } from "../generated/client/index.js";
import { hashPassword } from "./security.mjs";

const email = process.env.PORTAL_BOOTSTRAP_EMAIL?.trim().toLowerCase();
const password = process.env.PORTAL_BOOTSTRAP_PASSWORD;
if (!email || !password || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
  throw new Error("Set PORTAL_BOOTSTRAP_EMAIL and PORTAL_BOOTSTRAP_PASSWORD in the process environment");
}
const permissions = ["crm:read:all", "crm:write:all", "kanban:read:all", "kanban:write:all", "privacy:manage", "motor:execute", "identity:manage"];
const db = new PrismaClient();
try {
  const existing = await db.user.findUnique({ where: { email } });
  if (existing) throw new Error("Bootstrap account exists: refusing to reset its password");
  const passwordHash = await hashPassword(password);
  await db.$transaction(async tx => {
    const user = await tx.user.create({ data: { email, name: "Administrador SDAI", passwordHash } });
    const role = await tx.role.upsert({ where: { code: "ADMIN" }, update: {}, create: { code: "ADMIN", name: "Administrador" } });
    for (const code of permissions) {
      const permission = await tx.permission.upsert({ where: { code }, update: {}, create: { code } });
      await tx.rolePermission.upsert({ where: { roleId_permissionId: { roleId: role.id, permissionId: permission.id } }, update: {}, create: { roleId: role.id, permissionId: permission.id } });
    }
    await tx.userRole.create({ data: { userId: user.id, roleId: role.id } });
    await tx.auditEvent.create({ data: { actorId: user.id, action: "BOOTSTRAP", entity: "User", entityId: user.id, outcome: "SUCCESS" } });
  });
  console.log(`Bootstrap account created: ${email}`);
} finally {
  await db.$disconnect();
}
