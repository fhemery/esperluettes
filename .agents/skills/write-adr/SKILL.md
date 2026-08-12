---
name: write-adr
description: >-
  Write or update an Architecture Decision Record under docs/adr/. Use when
  asked to "add an ADR", "record this decision", "write an ADR for X", or when
  a feature's architecture locks a durable cross-cutting choice (package
  manager, auth model, storage backend) that must outlive the task folder.
---

# Write an ADR

Produce a durable Architecture Decision Record under `docs/adr/`. ADRs outlive
the loop task that created them: when WRAP deletes
`docs/Feature_Planning/<slug>/`, the ADR must still make sense on its own.

## When to write one

Write an ADR when the decision is:

- **expensive to reverse** (lockfile, auth scheme, primary datastore, public API
  shape shared by many domains);
- **not obvious from the code alone** (why we rejected the default);
- **cross-cutting** (repo tooling, CI, conventions) rather than a single
  domain's internal detail (those belong in the domain README).

Do **not** write an ADR for routine feature tradeoffs that the domain README
or WRAP record already captures.

## Hard rule — no planning references

**`docs/adr/**` must never mention `Feature_Planning`** — not a link to an
active task folder, not a link to `_done/<slug>.md`, not the bare path in
prose. Planning paths are renamed and deleted on WRAP; an ADR that points at
them rots by design.

`pnpm run gate` (docs step) fails on any such mention.

Fold whatever a future reader needs into the ADR body. If a WRAP summary has
useful history, copy the durable bits in — do not link.

Allowed references: code paths, `docs/adr/` siblings, domain READMEs,
setup/deploy docs, external URLs.

## Steps

### 1. Pick the next number

List `docs/adr/NNNN-*.md`. Next id is max + 1, zero-padded to four digits
(`0002-…`). Slug: lowercase kebab-case summary of the decision.

### 2. Write the file

Create `docs/adr/<NNNN>-<slug>.md` from
[`references/template.md`](references/template.md). Fill every section; delete
a section only if it would be empty noise (say so in Consequences if you drop
Alternatives).

Rules of thumb:

- **Context** states the forces, not the solution.
- **Decision** is imperative and specific ("Use pnpm 11 as the sole Node package
  manager", not "prefer a modern package manager").
- **Rejected alternatives** stay in the Decision or a short Alternatives
  subsection — that is what stops the question reopening.
- **Consequences** lists operational cost (bootstrap, CI, migration for
  existing clones), not marketing.
- Tense: write as if the decision is in force. Avoid "after phase 2 lands"
  unless the ADR is Accepted *and* the code already matches; prefer present
  tense + a dated note if something landed later.

### 3. Index it

Add a row to [`docs/adr/README.md`](../../../docs/adr/README.md).

### 4. Verify

```bash
pnpm run gate
```

The docs step must report `ADRs do not reference Feature_Planning` and no
broken relative links.

## Status vocabulary

| Status | Meaning |
|--------|---------|
| Proposed | Draft; not yet the house rule |
| Accepted | In force |
| Superseded by NNNN | Replaced; leave the file, point at the new ADR |
| Deprecated | No longer advised; no replacement yet |

Never delete an Accepted ADR silently — supersede or deprecate it.
