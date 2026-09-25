/**
 * Prisma adapter for MODEL-COST-001.
 * Keeps persistence outside the pure calculation engine.
 */
import { PrismaClient } from "@prisma/client";
import { calculateIntervention } from "./engine.mjs";

function hhForComplexity(activity, cplxSrv) {
  if (cplxSrv === "T1") return Number(activity.hhT1);
  if (cplxSrv === "T2") return Number(activity.hhT2);
  if (cplxSrv === "T3") return Number(activity.hhT3);
  throw new Error(`Invalid CPLX-SRV: ${cplxSrv}`);
}

export async function cubicFromCatalog({
  cplxSrv,
  activityCodes,
  effectiveAt = new Date(),
  prisma = new PrismaClient(),
}) {
  const ownsClient = arguments[0]?.prisma == null;
  try {
    const activities = await prisma.costActivity.findMany({
      where:{ code:{in:activityCodes}, active:true },
      include:{ primaryProfile:{ include:{ rates:true } } }
    });

    const missing = activityCodes.filter(code => !activities.some(a => a.code === code));
    if (missing.length) throw new Error(`Unknown/inactive ACT: ${missing.join(", ")}`);

    const normalized = activities.map(a => {
      const rates = a.primaryProfile.rates
        .filter(r => r.active && r.effectiveFrom <= effectiveAt && (!r.effectiveTo || r.effectiveTo >= effectiveAt))
        .sort((x,y)=>y.effectiveFrom-x.effectiveFrom);
      if (!rates[0]) throw new Error(`No effective rate for ${a.primaryProfile.code}`);
      return {
        id:a.id,
        code:a.code,
        name:a.name,
        front:a.front,
        profile:a.primaryProfile.code,
        hh:hhForComplexity(a,cplxSrv),
        shared:a.activityType === "MASTER",
        rateUf:Number(rates[0].ufPerHour)
      };
    });

    const rates = Object.fromEntries(normalized.map(a=>[a.profile,a.rateUf]));
    return calculateIntervention(normalized,rates);
  } finally {
    if (ownsClient) await prisma.$disconnect();
  }
}

export async function persistDraftExecution({
  result,
  cplxOrg,
  cplxSrv,
  clientRef,
  assessmentRef,
  catalogVersion,
  prisma = new PrismaClient(),
}) {
  const ownsClient = arguments[0]?.prisma == null;
  try {
    return await prisma.costExecution.create({
      data:{
        clientRef,
        assessmentRef,
        cplxOrg,
        cplxSrv,
        catalogVersion,
        status:"DRAFT",
        technicalUf:Number(result.technicalUf.toFixed(2)),
        estimatedHh:result.totalHh,
        lines:{
          create:result.activities.map(a=>({
            activityId:a.id,
            profileCode:a.profile,
            rateUf:a.rateUf,
            estimatedHh:a.hh,
            technicalUf:a.technicalUf
          }))
        }
      },
      include:{lines:true}
    });
  } finally {
    if (ownsClient) await prisma.$disconnect();
  }
}
