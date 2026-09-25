import { can, assertPermission } from "./rbac-unified.mjs";
const cases=[
 [["ADMIN"],"identity:users",true],
 [["CONSULTOR"],"motor:execute",true],
 [["CONSULTOR"],"motor:rates",false],
 [["ANALISTA"],"kanban:write",true],
 [["ANALISTA"],"crm:write",false],
 [["AUDITOR"],"crm:audit",true],
 [["COMERCIAL"],"crm:write",true],
 [["COMERCIAL"],"motor:read",false],
 [["ANALISTA","COMERCIAL"],"crm:write",true]
];
for(const [roles,p,expected] of cases){
 if(can(roles,p)!==expected) throw new Error(`RBAC failed ${roles} ${p}`);
}
let ok=false; try{assertPermission(null,"crm:read")}catch(e){ok=e.status===401}
if(!ok) throw new Error("Expected 401");
console.log("AUTH-001 unified RBAC regression OK");
