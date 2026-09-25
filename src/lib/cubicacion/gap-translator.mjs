/**
 * MODEL-COST-001 — GAP → CAP → ACT translator
 *
 * Produces the net intervention before economic calculation.
 * A GAP never maps directly to a commercial service.
 */

export function validateGap(gap) {
  if (!gap.capabilityCode) throw new Error("GAP requires capabilityCode");
  if (!Number.isInteger(gap.currentMaturity) || gap.currentMaturity < 0 || gap.currentMaturity > 5)
    throw new Error(`Invalid current maturity for ${gap.capabilityCode}`);
  if (!Number.isInteger(gap.targetMaturity) || gap.targetMaturity < 0 || gap.targetMaturity > 5)
    throw new Error(`Invalid target maturity for ${gap.capabilityCode}`);
  if (gap.targetMaturity < gap.currentMaturity)
    throw new Error(`Target below current maturity for ${gap.capabilityCode}`);
  return gap;
}

export function deriveNetIntervention(gaps, catalogLinks) {
  const openGaps = gaps.map(validateGap).filter(g => g.targetMaturity > g.currentMaturity);
  const activityMap = new Map();

  for (const gap of openGaps) {
    const links = catalogLinks.filter(l =>
      l.capabilityCode === gap.capabilityCode &&
      l.fromMaturity <= gap.currentMaturity &&
      l.toMaturity <= gap.targetMaturity
    );

    for (const link of links) {
      const existing = activityMap.get(link.activityCode);
      if (!existing) {
        activityMap.set(link.activityCode, {
          code:link.activityCode,
          supports:[gap.gapCode ?? gap.capabilityCode],
          capabilities:[gap.capabilityCode],
          reasons:[link.rationale].filter(Boolean)
        });
      } else {
        existing.supports = [...new Set([...existing.supports, gap.gapCode ?? gap.capabilityCode])];
        existing.capabilities = [...new Set([...existing.capabilities, gap.capabilityCode])];
        if (link.rationale) existing.reasons = [...new Set([...existing.reasons, link.rationale])];
      }
    }
  }

  return {
    gaps:openGaps,
    activityRequirements:[...activityMap.values()],
    activityCodes:[...activityMap.keys()]
  };
}

/**
 * Initial conservative mapping rule:
 * links explicitly declare the maturity transition they support.
 * No inference from domain, score or service code is allowed.
 */
export function buildCatalogLink({capabilityCode, activityCode, fromMaturity, toMaturity, rationale}) {
  if (toMaturity <= fromMaturity) throw new Error("Catalog link must increase maturity");
  return {capabilityCode,activityCode,fromMaturity,toMaturity,rationale};
}
