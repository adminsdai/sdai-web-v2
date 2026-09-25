/**
 * MODEL-COST-001 — SDAI PYME Cubicacion Engine
 * v0.1 WORKING
 *
 * Pure domain engine: no DB, UI or pricing side effects.
 * ACT is the atomic costing unit.
 */

export const RATES_UF = Object.freeze({
  "PRF-PRO-001": 1.00,
  "PRF-AFN-001": 1.40,
  "PRF-DAT-001": 1.90,
  "PRF-COM-001": 2.00,
  "PRF-CYB-001": 1.89,
  "PRF-AI-001": 1.80,
});

export const CS002_ACTIVITIES = Object.freeze([
  // Master/common layer — 17 HH / 24.63 UF
  { code:"ACT-MST-001", front:"MASTER", name:"Kickoff, alcance y contexto", profile:"PRF-PRO-001", hh:1.5, shared:true },
  { code:"ACT-MST-002", front:"MASTER", name:"Levantamiento sistemas, datos y tratamientos", profile:"PRF-AFN-001", hh:5, shared:true },
  { code:"ACT-MST-003", front:"MASTER", name:"Terceros y dependencias", profile:"PRF-AFN-001", hh:3, shared:true },
  { code:"ACT-MST-004", front:"MASTER", name:"Validacion de dependencias tecnicas", profile:"PRF-CYB-001", hh:1, shared:true },
  { code:"ACT-MST-005", front:"MASTER", name:"Responsables y accesos", profile:"PRF-AFN-001", hh:1.5, shared:true },
  { code:"ACT-MST-006", front:"MASTER", name:"Validacion de accesos criticos", profile:"PRF-CYB-001", hh:1, shared:true },
  { code:"ACT-MST-007", front:"MASTER", name:"Criticidad y priorizacion", profile:"PRF-COM-001", hh:1, shared:true },
  { code:"ACT-MST-008", front:"MASTER", name:"Criticidad operacional", profile:"PRF-PRO-001", hh:1, shared:true },
  { code:"ACT-MST-009", front:"MASTER", name:"Consolidacion de evidencia", profile:"PRF-AFN-001", hh:1.5, shared:true },
  { code:"ACT-MST-010", front:"MASTER", name:"QA evidencia tecnica", profile:"PRF-CYB-001", hh:.5, shared:true },

  // A — Gobierno de tratamientos — 15 HH / 25.80 UF
  { code:"ACT-PRI-A01", front:"GOVERNANCE", name:"Validar tratamientos y ROPA", profile:"PRF-AFN-001", hh:3 },
  { code:"ACT-PRI-A02", front:"GOVERNANCE", name:"Finalidades y criterios", profile:"PRF-COM-001", hh:2 },
  { code:"ACT-PRI-A03", front:"GOVERNANCE", name:"Retencion y ciclo de vida", profile:"PRF-COM-001", hh:2 },
  { code:"ACT-PRI-A04", front:"GOVERNANCE", name:"Revision de terceros", profile:"PRF-COM-001", hh:2 },
  { code:"ACT-PRI-A05", front:"GOVERNANCE", name:"Responsabilidades", profile:"PRF-COM-001", hh:1 },
  { code:"ACT-PRI-A06", front:"GOVERNANCE", name:"Consolidacion ROPA", profile:"PRF-AFN-001", hh:3 },
  { code:"ACT-PRI-A07", front:"GOVERNANCE", name:"Evidencia y validacion", profile:"PRF-AFN-001", hh:1 },
  { code:"ACT-PRI-A08", front:"GOVERNANCE", name:"Cierre compliance", profile:"PRF-COM-001", hh:1 },

  // B — Derechos y transparencia — 10 HH / 16.60 UF
  { code:"ACT-PRI-B01", front:"RIGHTS", name:"Flujo de derechos", profile:"PRF-COM-001", hh:2 },
  { code:"ACT-PRI-B02", front:"RIGHTS", name:"Localizacion de datos", profile:"PRF-AFN-001", hh:2 },
  { code:"ACT-PRI-B03", front:"RIGHTS", name:"Responsabilidades y escalamiento", profile:"PRF-PRO-001", hh:1 },
  { code:"ACT-PRI-B04", front:"RIGHTS", name:"Canal y registro", profile:"PRF-AFN-001", hh:1 },
  { code:"ACT-PRI-B05", front:"RIGHTS", name:"Transparencia", profile:"PRF-COM-001", hh:2 },
  { code:"ACT-PRI-B06", front:"RIGHTS", name:"Caso simulado", profile:"PRF-COM-001", hh:1 },
  { code:"ACT-PRI-B07", front:"RIGHTS", name:"Ajuste y evidencia", profile:"PRF-AFN-001", hh:1 },

  // C — Seguridad y respuesta — 10 HH / 17.53 UF
  { code:"ACT-CYB-C01", front:"SECURITY", name:"Validar identidades y accesos", profile:"PRF-CYB-001", hh:1.5 },
  { code:"ACT-CYB-C02", front:"SECURITY", name:"Privilegios administrativos", profile:"PRF-CYB-001", hh:1 },
  { code:"ACT-CYB-C03", front:"SECURITY", name:"Altas y bajas", profile:"PRF-AFN-001", hh:1 },
  { code:"ACT-CYB-C04", front:"SECURITY", name:"Acceso de terceros", profile:"PRF-CYB-001", hh:1 },
  { code:"ACT-CYB-C05", front:"SECURITY", name:"Procedimiento de incidentes", profile:"PRF-CYB-001", hh:2 },
  { code:"ACT-CYB-C06", front:"SECURITY", name:"Roles y escalamiento", profile:"PRF-PRO-001", hh:1 },
  { code:"ACT-CYB-C07", front:"SECURITY", name:"Ejercicio de incidente", profile:"PRF-CYB-001", hh:1.5 },
  { code:"ACT-CYB-C08", front:"SECURITY", name:"Correcciones y evidencia", profile:"PRF-CYB-001", hh:1 },

  // D — Continuidad y recuperacion — 20 HH / 34.06 UF
  { code:"ACT-CON-D01", front:"CONTINUITY", name:"Cadena critica", profile:"PRF-AFN-001", hh:2 },
  { code:"ACT-CON-D02", front:"CONTINUITY", name:"Backup/export SaaS", profile:"PRF-CYB-001", hh:2 },
  { code:"ACT-CON-D03", front:"CONTINUITY", name:"Dependencias de proveedores", profile:"PRF-CYB-001", hh:2 },
  { code:"ACT-CON-D04", front:"CONTINUITY", name:"Escenarios de interrupcion", profile:"PRF-PRO-001", hh:2 },
  { code:"ACT-CON-D05", front:"CONTINUITY", name:"Diseno minimo de recuperacion", profile:"PRF-CYB-001", hh:3 },
  { code:"ACT-CON-D06", front:"CONTINUITY", name:"Preparar pruebas", profile:"PRF-CYB-001", hh:2 },
  { code:"ACT-CON-D07", front:"CONTINUITY", name:"Ejecutar/acompaniar pruebas", profile:"PRF-CYB-001", hh:3 },
  { code:"ACT-CON-D08", front:"CONTINUITY", name:"Documentar resultados", profile:"PRF-AFN-001", hh:2 },
  { code:"ACT-CON-D09", front:"CONTINUITY", name:"Procedimiento y evidencia", profile:"PRF-CYB-001", hh:2 },
]);

export function calculateActivity(activity, rates = RATES_UF) {
  const rate = rates[activity.profile];
  if (rate == null) throw new Error(`Missing rate for profile ${activity.profile}`);
  // Monetary rule: each ACT is valued and rounded to 2 decimals before aggregation.
  // This preserves auditable line-item UF values and reproduces the governed CS-002 cubicacion.
  const technicalUf = Math.round((activity.hh * rate + Number.EPSILON) * 100) / 100;
  return { ...activity, rateUf: rate, technicalUf };
}

export function consolidateActivities(activities) {
  const seen = new Map();
  for (const act of activities) {
    const key = act.code;
    if (!seen.has(key)) {
      seen.set(key, { ...act, supports: [...(act.supports ?? [])] });
      continue;
    }
    const current = seen.get(key);
    // Deduplicate only when the ACT code represents the same execution scope.
    current.supports = [...new Set([...(current.supports ?? []), ...(act.supports ?? [])])];
  }
  return [...seen.values()];
}

export function calculateIntervention(activities, rates = RATES_UF) {
  const consolidated = consolidateActivities(activities);
  const lines = consolidated.map(a => calculateActivity(a, rates));
  const fronts = {};
  for (const line of lines) {
    const key = line.front ?? "OTHER";
    fronts[key] ??= { hh:0, technicalUf:0 };
    fronts[key].hh += line.hh;
    fronts[key].technicalUf += line.technicalUf;
  }
  return {
    activities: lines,
    fronts,
    totalHh: lines.reduce((s,a)=>s+a.hh,0),
    technicalUf: lines.reduce((s,a)=>s+a.technicalUf,0),
  };
}

export function runCs002Regression() {
  const result = calculateIntervention(CS002_ACTIVITIES);
  const expected = { totalHh:72, technicalUf:118.62 };
  const actual = { totalHh:result.totalHh, technicalUf:Number(result.technicalUf.toFixed(2)) };
  return {
    pass: actual.totalHh === expected.totalHh && actual.technicalUf === expected.technicalUf,
    expected,
    actual,
    fronts:Object.fromEntries(Object.entries(result.fronts).map(([k,v])=>[k,{
      hh:v.hh, technicalUf:Number(v.technicalUf.toFixed(2))
    }]))
  };
}
