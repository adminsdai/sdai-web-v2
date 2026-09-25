import { runCs002Regression } from "./engine.mjs";

const result = runCs002Regression();
console.log(JSON.stringify(result, null, 2));
if (!result.pass) {
  console.error("MODEL-COST-001 regression failed");
  process.exit(1);
}
console.log("MODEL-COST-001 CS-002 regression OK");
