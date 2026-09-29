import assert from "node:assert/strict";
import { mayReadAccount, mayAccessTask, mayManagePrivacy, mayRunMotor } from "./authorization.mjs";

const analyst = { id: "a", status: "ACTIVE", permissions: ["crm:read:own", "kanban:read:own", "kanban:write:own"] };
const admin = { id: "b", status: "ACTIVE", permissions: ["crm:read:all", "kanban:read:all", "kanban:write:all", "privacy:manage", "motor:execute"] };
assert.equal(mayReadAccount(analyst, { ownerId: "a" }), true);
assert.equal(mayReadAccount(analyst, { ownerId: "b" }), false);
assert.equal(mayAccessTask(analyst, { assigneeId: "a" }, "write"), true);
assert.equal(mayAccessTask(analyst, { assigneeId: "b" }, "write"), false);
assert.equal(mayManagePrivacy(analyst), false);
assert.equal(mayRunMotor(analyst), false);
assert.equal(mayReadAccount(admin, { ownerId: "a" }), true);
assert.equal(mayAccessTask(admin, { assigneeId: "a" }, "write"), true);
assert.equal(mayManagePrivacy(admin), true);
assert.equal(mayRunMotor(admin), true);
assert.equal(mayAccessTask({ ...admin, status: "DISABLED" }, { assigneeId: "a" }, "read"), false);
console.log("Portal authorization: PASS");
