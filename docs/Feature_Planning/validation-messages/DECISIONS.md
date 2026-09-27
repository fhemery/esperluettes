# Validation messages — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-09-27 | REFINE | Request wording and mode | Draft `00-request.md` accepted as written; run in `auto` mode | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Scope is **every** Laravel built-in rule, not just `image`/`max` — the request says "app-wide", and ~15 form requests rely on defaults | REFINE | yes |
| A2 | Block-level errors (`blocks.*.file`) are **silently dropped** today (views render only the exact `blocks` key; the editor has no error UI). Making them visible is in scope — otherwise fixing the text changes nothing for chapter/news block images, the defect the request names | REFINE | yes |
| A3 | Block errors show in the form's existing `blocks` error area, not inline next to the faulty block | REFINE | yes |
| A4 | The existing `validation.max.file` default « :max Mo » is wrong (`:max` is in Ko) and is corrected to Ko | REFINE | yes |
| A5 | Messages never expose technical field paths (`blocks.x.file`); fields get a French label or the message is phrased without one | REFINE | yes |
| A7 | T1 — defaults live in a Shared-owned PHP `validation.php`, registered unnamespaced via `FileLoader::addPath()`; rejected root `lang/`, JSON, `laravel-lang` package | DESIGN | yes (cheap: one file moves) |
| A8 | T2 — messages phrased without `:attribute` (supersedes A5's "French label" branch) | DESIGN | yes |
| A9 | T4 — the three `validation.*` keys in `Shared/fr.json` are removed (JSON shadows PHP) | DESIGN | yes |
| A10 | Plan cut into 2 phases (Shared defaults; block-error display in the 3 forms). Block errors: exact `blocks` errors merged with `Arr::flatten($errors->get('blocks.*'))`, de-duplicated (the wildcard returns a nested array); block-error tests added to each domain's existing `*AdvancedModeTest.php` rather than new files | PLAN | yes |
| A11 | Folder name kept as planned: `Shared/Resources/lang-framework/`. French wording per rule chosen at BUILD (subject « Ce champ » / « Cette valeur » / « Le fichier » / « Le texte » depending on the rule; `unique` → « Cette valeur est déjà utilisée. »); `custom` shipped as an empty array (English file's placeholder example dropped). An extra unit case asserts `custom`/`attributes` keys exist | BUILD (1/2) | yes |
| A12 | The layout's flash block already lists `$errors->all()` (so the French block message was on the page even before the fix, in the top « Whoops » box). Phase 2 tests therefore assert on the field-level `<ul class="text-sm text-red-600 …">` list rather than on the whole response. News/StaticPage cases added as HTTP tests in new `form errors` describes of the existing (service-level) `*AdvancedModeTest.php` files. The flash block is left as-is (block errors now appear in both places) | BUILD (2/2) | yes |
| A6 | Tests keep `APP_LOCALE=zz`; the French text is verified by tests that switch to `fr` | REFINE | yes |
