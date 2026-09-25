import { PrismaClient } from "@prisma/client";

const prisma = new PrismaClient();
const USER_ID = "contacto@sdaichile.com";
const ROLE_CODE = "ANALISTA";

async function main() {
  const role = await prisma.identityRole.findUnique({ where: { code: ROLE_CODE } });
  if (!role) throw new Error("AUTH-001: role ANALISTA is not seeded.");

  const user = await prisma.authorizedUser.upsert({
    where: { userId: USER_ID },
    update: {},
    create: { userId: USER_ID, role: "analista" }
  });

  await prisma.identityUserRole.upsert({
    where: { userId_roleId: { userId: user.userId, roleId: role.id } },
    update: {},
    create: { userId: user.userId, roleId: role.id, assignedBy: "AUTH-001-TEST-PROVISIONING" }
  });

  console.log(`AUTH-001 test identity provisioned: ${USER_ID} -> ${ROLE_CODE}`);
}

main()
  .catch((error) => {
    console.error(error);
    process.exitCode = 1;
  })
  .finally(async () => prisma.$disconnect());
