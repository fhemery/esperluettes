# Fix audit vulnerabilities — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Composer audit — `league/commonmark` bump | S | — | DONE |
| 2 | NPM audit — fixable advisories (Quill exception) | S | — | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (2/2)` resume correctly.

**Total: 2 phases.** Composer and NPM lockfiles are independent; either order
works. Phase 1 is Composer first so PHP-side resolution is settled before the
NPM pass. VERIFY is **N/A** (architecture §6, DECISIONS #7) — no visual QA
rows; WRAP documents the Quill exception.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- No new test files for this chore (architecture §6). Existing gate coverage
  is the regression proof; audit commands are the success criteria.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Do **not** add audit enforcement to CI or `npm run gate` (functional §8,
  DECISIONS #3).
- Do **not** force-downgrade Quill; leave `quill@2.0.3` exact pin (DECISIONS
  #1, architecture §4).

---

## Phase 1 — Composer audit — `league/commonmark` bump

**Goal.** Clear all six Composer audit advisories by bumping transitive
`league/commonmark` from 2.8.3 to ≥2.9.0 (dry-run target 2.10.0) within
Laravel's existing `^2.8.1` constraint.

**Architecture sections.** §3 (PHP / Composer remediation), §6 (testing
strategy), §7 tradeoff #1 (targeted update only), §8 (file layout).

**Deliverables.**
- `composer.lock` — `league/commonmark` at ≥2.9.0; review diff for unexpected
  transitive churn beyond the targeted package.
- `composer.json` — **only if** a targeted update refuses to resolve without a
  direct constraint change (unlikely per architecture §3).

**Commands (run after lockfile edit; redirect output, do not paste full reports
into chat):**

```bash
./vendor/bin/sail composer update league/commonmark --with-dependencies
./vendor/bin/sail composer audit
npm run gate
```

If `composer update league/commonmark` pulls a side-effect `laravel/framework`
patch bump (13.23 → 13.25), that is allowed per architecture §3 — not a goal,
but acceptable when it comes along for free.

**Tests.**
- No new PHP integration tests (architecture §6 — no domain code changes).
- Regression: full existing suite via `npm run gate` (PHPUnit, Vitest, deptrac,
  docs, Vite build).

**Acceptance.**
- ✅ `./vendor/bin/sail composer audit` reports **0** advisories.
- ✅ `composer.lock` diff is attributable to the commonmark remediation (no
  drive-by `composer update` of unrelated packages).
- ✅ `./vendor/bin/sail composer install` succeeds on a clean vendor tree.
- ✅ `npm run gate` green.

---

## Phase 2 — NPM audit — fixable advisories (Quill exception)

**Goal.** Clear all fixable NPM audit findings via `npm audit fix` (no
`--force`); leave `quill@2.0.3` as the sole accepted exception until upstream
ships a patched release.

**Architecture sections.** §4 (Frontend / NPM remediation), §6 (testing
strategy), §7 tradeoffs #2 (audit fix method) and #3 (Quill documented in WRAP
only), §8 (file layout), §9 risk (lockfile churn, dirty worktree).

Earlier phases left `composer.lock` clean; this phase touches only NPM
artefacts.

**Deliverables.**
- `package-lock.json` — patched versions for fixable advisories:
  `brace-expansion`, `fast-uri`, `js-yaml`, `nanoid`, `postcss` (functional
  §4.2).
- `package.json` — **only if** a direct range must move (e.g. `js-yaml`
  `^4.1.1` caret already allows ≥4.3.1; lockfile bump may suffice).
- Do **not** change the exact `quill` pin (`"quill": "2.0.3"`).
- Do **not** add `overrides` / `resolutions` unless `npm audit fix` (no
  `--force`) cannot reach a patched transitive version (architecture §4).

**Commands (run after lockfile edit; redirect output):**

```bash
npm audit fix          # no --force
npm audit
npm audit --omit=dev   # optional: expect Quill-only (or fully clean)
npm ci                 # verify lockfile is installable
npm run gate
```

Review `git diff package-lock.json` before commit. Revert unrelated lockfile
noise if the diff is gate-irrelevant and large (architecture §9).

**Pre-existing worktree noise:** the current unstaged diff renames the lockfile
root from `"esperluettes"` to `"esperluettes-bis"` (worktree basename). **Do
not** include that rename in this phase's commit unless it is an unavoidable
side-effect of the audit fix — keep the diff attributable to advisory
remediation (architecture §9).

**Tests.**
- No new Vitest or integration tests (architecture §6).
- Regression: full existing suite via `npm run gate`.

**Acceptance.**
- ✅ `npm audit` reports **no fixable** findings; the only remaining advisory
  is `quill@2.0.3` (GHSA-v3m3-f69x-jf25), or the audit is fully clean if
  upstream patched during BUILD (functional §9 non-blocking note).
- ✅ `npm audit --omit=dev` shows at most the Quill advisory (functional §4.2.6).
- ✅ `quill` remains at exact `2.0.3` in `package.json` — no downgrade to 2.0.2.
- ✅ `npm ci` succeeds.
- ✅ `npm run gate` green.

---

## Visual QA checklist

**Skipped — VERIFY N/A.** No user-facing surface, no intentional UI or Quill
behaviour change (architecture §6, DECISIONS #7). WRAP documents the accepted
Quill exception in the task README.

| Surface | Check | OK? |
|---------|-------|-----|
| — | VERIFY skipped per architecture §6 | N/A |

## Open items

| Item | Phase | Notes |
|------|-------|-------|
| Sail/Docker must be running for `./vendor/bin/sail composer audit` and gate PHP tests | 1 | Could not verify in PLAN — sandbox had no Docker. BUILD uses `LOCAL_RUNNER` if set. |
| Exact `npm audit fix` outcome for transitive packages | 2 | If `audit fix` (no `--force`) leaves fixable advisories, escalate to manual lockfile bumps or `overrides` per architecture §4 — do not use `--force`. |
| Pre-existing `package-lock.json` name-field diff (`esperluettes` → `esperluettes-bis`) | 2 | Worktree noise; exclude from commit unless unavoidable (architecture §9). |
| Upstream Quill patch during BUILD | 2 | Non-blocking: if ≥2.0.4 ships, prefer upgrading over leaving the exception (functional §9). |
