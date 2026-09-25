/**
 * MODEL-COST-001 — governed baseline seed v0.1
 * Run after Prisma schema deployment.
 */
import { PrismaClient } from "@prisma/client";
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
  console.log("MODEL-COST-001 profiles/rates seeded");
}

main().finally(()=>prisma.$disconnect());
