# Validation messages — follow-ups — request

*Leftovers from `validation-messages` (see `_done/validation-messages.md`).*

## What I want

1. The layout's top « Oups ! Une erreur s'est produite. » box
   (`Shared/Resources/views/components/flash-block.blade.php`) lists
   `$errors->all()` without de-duplication: a chapter with two image blocks
   failing the same rule shows the same message twice there (the field-level
   list under the editor already shows it once).
2. Shared's custom rules (`maxstripped`, `minstripped`, `required_trimmed`,
   registered in `Shared/Validation/CustomValidators.php`) have no default
   message: a request using them without a `messages()` override prints the raw
   key (`validation.maxstripped`, verified in `fr`). Add French defaults
   (attribute-less, like the rest of `Shared/Resources/lang-framework/fr/validation.php`).

## Why

No raw key and no duplicated line should ever reach a user. Item 2 is latent
today (every current use overrides the message), item 1 is visible.

## Constraints or ideas I already have

Both live in Shared; likely one small phase each. Keep the completeness test
against the framework's English file as is — the custom keys are extras.

## Explicitly out of scope

Whether the flash box should list field errors at all.
