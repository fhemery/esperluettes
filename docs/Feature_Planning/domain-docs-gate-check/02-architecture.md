# Domain docs gate check — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**No application domain owns this.** It is developer tooling under `scripts/`,
extending the existing docs step of `pnpm run gate`. The only product-domain
touch is documenting and registering **Follow**, which already exists on disk
without the documentation trio or a Domain Registry row.

### 1.1 Changes in other domains

#### Follow

Create the documentation trio (`README.md`, `AGENTS.md`, `CLAUDE.md`) under
`app/Domains/Follow/` using the `document-domain` content contract. No PHP,
routes, or schema changes.

#### Root `AGENTS.md`

Add a Domain Registry row for Follow (Path `` `app/Domains/Follow` `` plus
Responsibilities / Tables filled from the domain's actual Public API and
migrations). This is the canonical registry the new check will parse.

## 2. Data model

N/A — no tables, models, or migrations.

## 3. PHP architecture

N/A — no PHP surface. Enforcement is the Node docs checker.

### 3.1 Docs checker contract (`scripts/check-docs.js`)

Keep the three existing checks. Add two more that share the same report style
(label + list of failures; all checks run; non-zero exit if any failed):

**Rule 4 — domain documentation trio**

- Input: immediate child directories of `app/Domains/`.
- For each domain name `D`, require files:
  - `app/Domains/D/README.md`
  - `app/Domains/D/AGENTS.md`
  - `app/Domains/D/CLAUDE.md`
- Additionally require `CLAUDE.md` bytes equal exactly `@AGENTS.md\n`
  (Unix newline). Wrong content is reported separately from missing file.
- Failure messages name domain + missing file or "CLAUDE.md is not the
  `@AGENTS.md` shim".

**Rule 5 — Domain Registry ↔ disk**

- Parse root `AGENTS.md`: locate the `## Domain Registry` section; collect Path
  cells that match `` `app/Domains/<Name>` `` (backtick-wrapped).
- Every disk domain `D` must appear as Path `` `app/Domains/D` ``.
- Every such Path in the registry must correspond to an existing directory
  `app/Domains/<Name>/`.
- Do not validate Responsibilities / Tables text.

Domain discovery for rules 4–5 is filesystem-first (assumption A1), not
"domains that already have a README".

## 4. Frontend architecture

N/A.

## 5. Deptrac

No new edges. Follow already has layers if present; documenting it does not
change PHP dependencies. Tooling lives outside `app/Domains`.

## 6. Testing strategy

| Layer | What |
|-------|------|
| Node unit / script tests | Pure helpers for: list domains, validate CLAUDE shim, parse Domain Registry paths, compute missing/orphan sets. Prefer a small Vitest (or `node --test`) file next to the script that feeds fixtures — no PHPUnit (assumption A5). |
| Gate / phase acceptance | On a clean tree after Follow is documented: `pnpm run gate` docs step green. Negative path: a temporary fake domain directory (or a mutated CLAUDE) must make the docs step fail with the expected message, then be cleaned up. |
| VERIFY | Skip — no browser surface (same rationale as `domain-claude-md-shims`). |

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where does the check live? | A) Extend `scripts/check-docs.js` · B) New script + gate wiring | **A** | Docs step already always-on; one place for doc rules |
| 2 | Domain source of truth for discovery | A) Disk `app/Domains/*` · B) Registry alone | **A** | Catches domains never registered (Follow); registry orphans covered by bidirectional check |
| 3 | CLAUDE enforcement | A) Exact `@AGENTS.md\n` · B) File exists only | **A** | Matches `document-domain` / prior WRAP contract; presence-only lets drift return |
| 4 | Follow | A) Document + register in this task · B) Exclude / allowlist | **A** | Exclusion would leave the filed hole open |
| 5 | Test vehicle | A) Node tests on helpers + gate acceptance · B) PHPUnit file-exists test | **A** | Prior WRAP rejected PHPUnit for this as theatre |

## 8. File layout

```
scripts/
  check-docs.js          # rules 4–5 added (existing file)
  check-docs.test.js     # optional colocated Vitest; PLAN decides exact name
app/Domains/Follow/
  README.md              # new
  AGENTS.md              # new
  CLAUDE.md              # new (`@AGENTS.md`)
AGENTS.md                # Domain Registry row for Follow
```

## 9. Risks acknowledged

| Risk | Trigger to revisit |
|------|--------------------|
| Registry parser is brittle if the Domain Registry table format changes | Someone redesigns the root `AGENTS.md` table (columns / heading) |
| Exact CLAUDE byte check rejects CRLF or accidental trailing space | Editor/OS rewrite; then either normalize in the check or fix the files |
| Temporary negative-path fixtures left behind break CI | Phase cleanup must be `try/finally` (or equivalent) |
