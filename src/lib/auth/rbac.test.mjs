import { can, assertPermission } from "./rbac.mjs";

const cases=[
 ["ADMIN","rates:write",true],
 ["CONSULTOR","cost:execute",true],
 ["CONSULTOR","rates:write",false],
 ["ANALISTA","assessment:read",true],
 ["ANALISTA","cost:execute",false],
 ["UNKNOWN","catalog:read",false],
];
for(const [role,p,expected] of cases){
 const actual=can(role,p);
 if(actual!==expected) throw new Error(`RBAC failed: ${role} ${p}`);
}
let unauthorized=false;
try { assertPermission(null,"assessment:read"); } catch(e){ unauthorized=e.status===401; }
if(!unauthorized) throw new Error("Expected 401 without authenticated user");
console.log("AUTHZ-001 RBAC regression OK");
