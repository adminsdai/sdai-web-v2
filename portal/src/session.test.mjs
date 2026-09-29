import assert from "node:assert/strict";
import { createSession, resolveSession, revokeSession, sessionCookie } from "./session.mjs";

const now = new Date("2026-09-29T00:00:00Z");
const state = { status: "ACTIVE", session: null };
const db = {
  user: { findUnique: async () => ({ status: state.status }) },
  session: {
    create: async ({ data }) => { state.session = { ...data, revokedAt: null }; },
    findUnique: async ({ where }) => where.tokenHash === state.session?.tokenHash
      ? { ...state.session, user: { status: state.status, roles: [{ role: { permissions: [{ permission: { code: "kanban:read:own" } }] } }] } }
      : null,
    updateMany: async () => { state.session.revokedAt = now; }
  }
};
const { token, expiresAt } = await createSession(db, "user-1", now);
assert.equal(token.length, 43);
assert.notEqual(token, state.session.tokenHash);
assert.match(sessionCookie(token, expiresAt), /HttpOnly; Secure; SameSite=Lax/);
assert.deepEqual((await resolveSession(db, token, now)).permissions, ["kanban:read:own"]);
assert.equal(await resolveSession(db, "wrong", now), null);
state.status = "DISABLED";
assert.equal(await resolveSession(db, token, now), null);
state.status = "ACTIVE";
await revokeSession(db, token, now);
assert.equal(await resolveSession(db, token, now), null);
console.log("Portal session: PASS");
