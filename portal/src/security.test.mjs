import assert from "node:assert/strict";
import { hashPassword, verifyPassword, escapeHtml, readCookie } from "./security.mjs";
const hash = await hashPassword("long local password 2026!");
assert.equal(await verifyPassword("long local password 2026!", hash), true);
assert.equal(await verifyPassword("wrong password", hash), false);
assert.equal(await verifyPassword("x", "broken"), false);
assert.equal(escapeHtml('<script>"&'), '&lt;script&gt;&quot;&amp;');
assert.equal(readCookie("a=x; sdai_portal_session=abc; b=y", "sdai_portal_session"), "abc");
console.log("Portal security: PASS");
