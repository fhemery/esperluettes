# Feature toggles declared in service providers — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-09-26 | REFINE | Production state source for the cleanup skill | Read-only artisan command, output pasted by the user | — |
| 2 | 2026-09-26 | REFINE | Deleting rows of toggles removed from code | By hand in the admin UI after deploy; no migration | — |
| 3 | 2026-09-26 | REFINE | Config parameters in scope? | No, toggles only | — |
| 4 | 2026-09-26 | REFINE | Order of work | Skill, first cleanup, then this feature (which readjusts the skill) | — |
| 5 | 2026-09-26 | REFINE | French strings on a toggle | None | — |
| 6 | 2026-09-26 | REFINE | Mode and branch | `auto`, on `chore/feature-flag-cleanup` | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Checking an undeclared toggle throws, in every environment (not "log and return false") | REFINE | Yes — cheap |
| A2 | A declared toggle without a row reads as `off`; there is no per-declaration default access | REFINE | Yes — cheap |
| A3 | Admin visibility is part of the declaration and no longer editable in the UI | REFINE | Yes |
| A4 | The admin "create toggle" form is removed; the first access change creates the row | REFINE | Yes |
| A5 | Orphan rows are shown to tech admins only, labelled « Non déclaré dans le code », delete being their only action | REFINE | Yes |
| A6 | The toggle description lookup (`<domain>::config.feature_toggles.<name>`) and its column are removed | REFINE | Yes — cheap |
| A7 | Declaring the same toggle twice keeps the last declaration, like config parameters | REFINE | Yes — cheap |
| A8 | `shared/dark_theme` is declared "tech admins only" | REFINE | Yes — one line |
| A10 | Declarations live in a static registry in `FeatureToggleService`, like config parameters | DESIGN | Yes |
| A11 | `admin_visibility` column dropped (migration with `down()`) | DESIGN | Yes — `down()` re-adds it |
| A12 | Rows created lazily on the first state change, never at boot | DESIGN | Yes — cheap |
| A13 | `addFeatureToggle` / `editFeatureToggle` removed; roles set through `updateFeatureToggle(..., ?array $roles)` | DESIGN | Yes |
| A14 | `FeatureToggleAdded` no longer emitted but stays registered so stored events deserialize | DESIGN | Yes — cheap |
| A15 | The command's report method stays internal to Config, not on `ConfigPublicApi` | DESIGN | Yes — cheap |
| A9 | Command is `config:toggles` with `--json`; table output otherwise; lists declared toggles and orphan rows with declared flag, access, roles, last change date | REFINE | Yes |
| A16 | `UndeclaredFeatureToggleException` names the toggle lowercased (`domain/name`), as the lookup key is | BUILD 1 | Yes — cheap |
| A17 | `AddFeatureToggleTest` "proceeds normally" left as is: `createFeatureToggle()` now declares the toggle, so the extra declaration the plan asked for was redundant | BUILD 1 | Yes — cheap |
| A18 | `Admin/FeatureToggleControllerTest`'s `makeToggle()` also declares its toggle: otherwise the `setAccess` cases hit an undeclared toggle and throw. The plan only named the `destroy` change | BUILD 2 | Yes — cheap |
| A19 | `AddFeatureToggleTest` calls `addFeatureToggle` through a file-local helper (and declares in "proceeds normally"), since `createFeatureToggle()` no longer goes through it. The file is deleted in phase 3 | BUILD 2 | Yes — cheap |
| A20 | `updateFeatureToggle` resolves an existing row case-insensitively via the cache, then updates it or creates a lowercased one, instead of a raw `updateOrCreate` — same result, and a legacy mixed-case row is updated rather than duplicated on a case-sensitive DB | BUILD 2 | Yes — cheap |
| A21 | Order in `updateFeatureToggle`: undeclared check first, then permission. In `deleteFeatureToggle`: permission, then declared refusal, then missing-row no-op | BUILD 2 | Yes — cheap |
| A22 | `listFeatureToggles` reports the domain/name as written in the declaration; orphans keep the row's casing | BUILD 2 | Yes — cheap |
| A23 | `destroy` is `DELETE /feature-toggles/{domain}/{name}`, not model binding as the plan said: the orphan DTO has no id and the index no longer queries the model. Side effect: `GET /feature-toggles/create` matches no route (404, not 405) | BUILD 3 | Yes — cheap |
| A24 | Edit loads the toggle through `listFeatureToggles()` (404 when undeclared) rather than a new API method; update with no `roles` field clears roles (`[]`), as the old edit did | BUILD 3 | Yes — cheap |
| A25 | View tests assert translation keys, not French text: the test locale is `zz` | BUILD 3 | Yes — cheap |
| A26 | `UpdateFeatureToggleTest`'s "declaration wins over the row" case loses its `admin_visibility: all_admins` row value (the column is gone); it still proves an admin cannot change a tech-only toggle | BUILD 3 | Yes — cheap |
