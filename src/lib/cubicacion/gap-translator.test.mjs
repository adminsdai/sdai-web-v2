import { deriveNetIntervention } from "./gap-translator.mjs";

const gaps=[
 {gapCode:"GAP-001",capabilityCode:"CAP-PRI-001",currentMaturity:1,targetMaturity:2},
 {gapCode:"GAP-002",capabilityCode:"CAP-CYB-001",currentMaturity:1,targetMaturity:2}
];
const links=[
 {capabilityCode:"CAP-PRI-001",activityCode:"ACT-MST-002",fromMaturity:1,toMaturity:2},
 {capabilityCode:"CAP-PRI-001",activityCode:"ACT-PRI-A01",fromMaturity:1,toMaturity:2},
 {capabilityCode:"CAP-CYB-001",activityCode:"ACT-MST-002",fromMaturity:1,toMaturity:2},
 {capabilityCode:"CAP-CYB-001",activityCode:"ACT-CYB-C01",fromMaturity:1,toMaturity:2}
];
const r=deriveNetIntervention(gaps,links);
if(r.activityCodes.length!==3) throw new Error("Expected shared ACT to be deduplicated");
const shared=r.activityRequirements.find(x=>x.code==="ACT-MST-002");
if(shared.supports.length!==2) throw new Error("Expected shared ACT traceability to both GAPs");
console.log(JSON.stringify(r,null,2));
console.log("GAP → CAP → ACT net intervention test OK");
