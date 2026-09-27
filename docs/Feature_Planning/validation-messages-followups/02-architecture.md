# Validation messages — follow-ups — architecture

> DESIGN output (`auto` mode). No real tradeoff: both changes are local to
> Shared and cheap to reverse.

## 1. Domain placement

**Shared** only.

- `<x-shared::flash-block>` lists `$errors->all()` de-duplicated (keep first
  occurrence order — `array_unique` on the list).
- The three custom-rule keys are added to Shared's framework defaults file
  (`Resources/lang-framework/fr/validation.php`), attribute-less, beside the
  built-in rules. `CustomValidators` already registers `:max` / `:min`
  replacers for the stripped rules (check `minstripped` has one; add it if not).
- The completeness test keeps comparing against Laravel's English file; the
  "no `:attribute`" check naturally covers the new keys.

### 1.1 Changes in other domains
None. Story / Calendar keep their `messages()` overrides.

## 2. Data model
N/A.

## 3. PHP architecture
No API, service, route or event change.

## 4. Frontend architecture
Blade only; one loop in `flash-block.blade.php`.

## 5. Deptrac
No new edge.

## 6. Testing strategy

- **Integration (Shared)** — in `fr`, a validator using each custom rule with no
  override yields the French text with the number substituted, never
  `validation.<rule>`.
- **Integration (Shared)** — rendering `flash-block` with an error bag holding
  the same message on two keys shows it once; two distinct messages both show.

## 7. Tradeoffs locked

| # | Question | Chosen | Rejected | Why |
|---|----------|--------|----------|-----|
| T1 | Where de-duplication happens | In the `flash-block` view | In a shared helper / MessageBag macro | One consumer; minimum code |
| T2 | Where the custom defaults live | Same framework-defaults file as the built-ins | `shared::validation` namespace | The validator looks up unnamespaced `validation.<rule>` |

## 8. File layout

- `app/Domains/Shared/Resources/views/components/flash-block.blade.php`
- `app/Domains/Shared/Resources/lang-framework/fr/validation.php`
- `app/Domains/Shared/Validation/CustomValidators.php` (only if a replacer is missing)
- Shared tests.

## 9. Risks acknowledged

None beyond wording.
