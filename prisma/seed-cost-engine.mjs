/**
 * MODEL-COST-001 — governed baseline seed v0.1
 * Run after Prisma schema deployment.
 */
import { PrismaClient } from "@prisma/client";
import { CS002_ACTIVITIES } from "../src/lib/cubicacion/engine.mjs";
const prisma = new PrismaClient();

const profiles = [
 ["PRF-PRO-001","Especialista de Procesos",1.00],
 ["PRF-AFN-001","Analista Funcional",1.40],
 ["PRF-DAT-001","Consultor Datos",1.90],
 ["PRF-COM-001","Consultor Compliance",2.00],
 ["PRF-CYB-001","Consultor Ciberseguridad",1.89],
 ["PRF-AI-001","Consultor AI",1.80],
];

async function main() {
  for (const [code,name,ufPerHour] of profiles) {
    const profile = await prisma.costProfile.upsert({
      where:{code}, update:{name,active:true}, create:{code,name,active:true}
    });
    await prisma.costRate.upsert({
      where:{profileId_version:{profileId:profile.id,version:"2026-09"}},
      update:{ufPerHour,effectiveFrom:new Date("2026-09-23T00:00:00Z"),active:true},
      create:{profileId:profile.id,version:"2026-09",ufPerHour,effectiveFrom:new Date("2026-09-23T00:00:00Z"),active:true}
    });
  }
  // Initial governed ACT catalog. CS-002 is used only as the regression fixture
  // that bootstraps the first activity baseline.
  const profileByCode = Object.fromEntries(
    (await prisma.costProfile.findMany()).map(p => [p.code, p])
  );

  for (const a of CS002_ACTIVITIES) {
    const profile = profileByCode[a.profile];
    if (!profile) throw new Error(`Missing profile ${a.profile}`);
    await prisma.costActivity.upsert({
      where:{code:a.code},
      update:{
        name:a.name,
        activityType:a.shared ? "MASTER" : "SPECIALIZED",
        front:a.front,
        primaryProfileId:profile.id,
        hhT1:a.hh,
        hhT2:a.hh,
        hhT3:a.hh,
        version:"0.1",
        active:true
      },
      create:{
        code:a.code,
        name:a.name,
        activityType:a.shared ? "MASTER" : "SPECIALIZED",
        front:a.front,
        primaryProfileId:profile.id,
        hhT1:a.hh,
        hhT2:a.hh,
        hhT3:a.hh,
        version:"0.1",
        active:true
      }
    });
  }

  console.log(`MODEL-COST-001 seeded: ${profiles.length} profiles + ${CS002_ACTIVITIES.length} activities`);
}

main().finally(()=>prisma.$disconnect());
