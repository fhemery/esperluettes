# Validation messages — follow-ups — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-09-27 | REFINE | Branch and mode | Run on `chore/validation-messages` (same branch as its parent task), `auto` mode | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | « Oups » box de-duplicates by message text, first occurrence order kept | REFINE | yes |
| A2 | Custom-rule wording: `maxstripped` « Ce champ ne doit pas dépasser :max caractères. », `minstripped` « Ce champ doit contenir au moins :min caractères. », `required_trimmed` « Ce champ est obligatoire. » | REFINE | yes |
| A3 | De-dup lives in the `flash-block` view, not a helper; custom defaults go in the framework-defaults file (unnamespaced lookup) | DESIGN | yes |
| A4 | Two S phases (custom-rule defaults, then flash de-dup), independent of each other | PLAN | yes |
| A5 | `required_trimmed` stays a non-implicit rule (it only fires on `null` in a direct `Validator::make`; `''`/blank reach it as `null` over HTTP). Making it implicit is out of scope; its test uses `null` | PLAN | yes |
| A6 | Custom-rule tests extend `Shared/Tests/Feature/DefaultValidationMessagesTest.php`; flash de-dup gets a new `Shared/Tests/Feature/View/Components/FlashBlockTest.php` | PLAN | yes |

