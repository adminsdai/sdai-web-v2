export async function auditIdentity(prisma, {
  actorUserId = null,
  action,
  resource,
  resourceId = null,
  outcome = "SUCCESS",
  metadata = null,
  ipHash = null
}) {
  return prisma.identityAuditEvent.create({
    data: { actorUserId, action, resource, resourceId, outcome, metadata, ipHash }
  });
}
