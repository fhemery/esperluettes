# Validation messages — follow-ups

**Status:** DONE — 2026-09-27 · **Domain(s):** `Shared` · Leftovers of
[`validation-messages`](./validation-messages.md).

## What it does

Two small Shared fixes so no raw key and no duplicated line reaches a user:
the layout's « Oups ! Une erreur s'est produite. » box lists each validation
message once, and Shared's custom rules (`maxstripped`, `minstripped`,
`required_trimmed`) get French defaults instead of printing
`validation.<rule>`. Code matches the plan; nothing cut.

## Key behaviour

- « Oups » box: `array_unique($errors->all())` — de-dup by exact message text,
  first-occurrence order kept. Field-level errors elsewhere are untouched.
- Applies to every layout using `<x-shared::flash-block />` (app and
  Administration, both checked in VERIFY).
- Custom-rule defaults: `maxstripped` « Ce champ ne doit pas dépasser :max
  caractères. », `minstripped` « Ce champ doit contenir au moins :min
  caractères. », `required_trimmed` « Ce champ est obligatoire. ».
  Attribute-less, like the rest of the file.
- A form request's `messages()` still wins over these defaults (every current
  use overrides them, so the defaults have no reachable UI today — test-only).
- Counter-intuitive: `required_trimmed` is **not implicit** — in a direct
  `Validator::make` it only fires on `null`; over HTTP `''`/blank arrive as
  `null` through Laravel's `TrimStrings` + `ConvertEmptyStringsToNull`.
- The completeness test (`english ⊆ french`) is unchanged; the three custom
  keys are extras it tolerates.

## Where the code lives

| Concern | Path |
|---------|------|
| Custom-rule defaults | `app/Domains/Shared/Resources/lang-framework/fr/validation.php` (unnamespaced) |
| Rule registration (unchanged) | `app/Domains/Shared/Validation/CustomValidators.php` |
| « Oups » box | `app/Domains/Shared/Resources/views/components/flash-block.blade.php` |
| Tests | `app/Domains/Shared/Tests/Feature/DefaultValidationMessagesTest.php` (3 defaults + override wins), `app/Domains/Shared/Tests/Feature/View/Components/FlashBlockTest.php` (new) |

## Extension points used

None.

## Decisions worth remembering

- De-dup in the view, not a helper; by message text, not by field (A1, A3).
- Custom defaults go in the framework-defaults file (unnamespaced
  `validation.<rule>` lookup), not in `shared::validation`, which only holds
  `unique_profile_display_name` (A3). Shared's `AGENTS.md`/`README.md` now say so
  (the `AGENTS.md` sentence was stale — A7).
- `required_trimmed` left non-implicit (A5).

## Assumptions made without asking (auto mode — reversible)

- A1 de-dup by message text, first occurrence order.
- A2 the three French wordings above.
- A3 de-dup in the Blade view; defaults in `lang-framework`.
- A4 two independent S phases.
- A5 `required_trimmed` stays non-implicit; its test uses `null`.
- A6 test placement as in the table above.
- A7 Shared `AGENTS.md` sentence fixed at WRAP, not in BUILD.

## Not done

- Non-goals: whether the « Oups » box should list field errors at all;
  changing the completeness test; rewording existing `messages()` overrides.
- Nothing cut mid-build. No open questions. No backlog rows pushed.
- No e2e spec was added under `e2e/tests/features/`; VERIFY was manual (5/5).
