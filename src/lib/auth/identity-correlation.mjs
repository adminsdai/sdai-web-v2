export function normalizeEmail(value) {
  return String(value ?? "").trim().toLowerCase();
}

export function correlateIdentities({ web = [], crm = [], kanban = [] }) {
  const rows = [];
  for (const [source, users] of Object.entries({ web, crm, kanban })) {
    for (const user of users) {
      const email = normalizeEmail(user.email ?? user.userId);
      rows.push({ source, localId: String(user.id ?? user.userId ?? ""), name: user.name ?? null, email, role: user.role ?? null, active: user.active !== false && user.active !== 0 });
    }
  }

  const byEmail = new Map();
  for (const row of rows) {
    if (!row.email || !row.email.includes("@")) continue;
    if (!byEmail.has(row.email)) byEmail.set(row.email, []);
    byEmail.get(row.email).push(row);
  }

  return [...byEmail.entries()].map(([email, matches]) => {
    const sources = new Set(matches.map(x => x.source));
    const duplicateWithinSource = matches.some((x, i) => matches.some((y, j) => j !== i && y.source === x.source));
    const placeholder = /example\.(local|com)$/i.test(email);
    return {
      email,
      matches,
      status: duplicateWithinSource || placeholder ? "REVIEW" : sources.size > 1 ? "MATCH" : "UNMATCHED"
    };
  }).sort((a,b) => a.email.localeCompare(b.email));
}
