# Editor domain — record the visual QA that was never recorded

Leftover from [`editor-domain/`](../editor-domain/README.md), wrapped 2026-07-30
with **VERIFY skipped**. The refactor is a pure move: every checklist row's
expected result is "identical to before". PHP/vitest coverage is green, but
nothing client-side (Quill init, toolbar rendering, counters, spoiler insertion,
asset-once-per-page) has a recorded pass.

## What exists here

Four Playwright flows for the `run-app` / `verify-visually` skills, written
2026-07-30 and executed at least once (screenshots landed in the gitignored
`.agents/skills/run-app/shots/`), but **no row of the checklist was filled and no
VERIFY report was written** — so there is no record of what passed.

| Flow | Covers (rows of `editor-domain/03-plan.md` § Visual QA checklist) |
|------|------|
| `verify-readonly.mjs` | 1–3 — read-only pages load **no** editor asset, stored-content styles intact (guest) |
| `verify-authenticated.mjs` | 3, 4–6 — AJAX comment fragments (risk R1), two editors on one page, assets once each |
| `verify-editing.mjs` | 7–20 — every editing surface, mobile 375 px, dark mode |
| `verify-interactions.mjs` | 10, 13, 14, 17, 18 — counters, block add/reorder, spoiler insert, save+render |

`seed-*.php` are the fixtures the flows expect (chapter `qa-editor-domain-24`,
a profile/static page, a SecretGift activity in gift-preparation phase). Run with
`./vendor/bin/sail artisan tinker --execute="require '<file>';"` — or check the
data already exists before re-seeding. They mutate local dev data; do not run
them anywhere else.

## Task

Re-run the four flows, fill the checklist in
[`editor-domain/03-plan.md`](../editor-domain/03-plan.md), fix anything that
regressed, and delete this folder. Selectors may have drifted — the flows are a
head start, not a guarantee.
