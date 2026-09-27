# Validation messages — follow-ups — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | French defaults for Shared's custom rules | S | — | TODO |
| 2 | De-duplicate the « Oups » box | S | — | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

The two phases are independent; the order is arbitrary (latent fix first,
visible fix second). Both are Shared-only, no other domain is touched.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — French defaults for Shared's custom rules

**Goal.** `maxstripped`, `minstripped` and `required_trimmed` print a French
sentence when a form gives no message of its own, never `validation.<rule>`
(architecture §1, §7 T2; functional §4.2).

**Context.** The rules are registered in
`app/Domains/Shared/Validation/CustomValidators.php` with `Validator::extend`.
Replacers for `:max` (`maxstripped`) and `:min` (`minstripped`) **already
exist** there — verified at PLAN, no change to that file. Today, in `fr`, all
three resolve to the raw key (verified with tinker).

**Deliverables.**
- `app/Domains/Shared/Resources/lang-framework/fr/validation.php` — add three
  top-level keys, in alphabetical position among the existing rules (not in
  `custom`):
  - `'maxstripped' => 'Ce champ ne doit pas dépasser :max caractères.'`
  - `'minstripped' => 'Ce champ doit contenir au moins :min caractères.'`
  - `'required_trimmed' => 'Ce champ est obligatoire.'`
- No change to `Tests/Unit/FrameworkValidationMessagesTest.php`: its
  completeness check is `english ⊆ french`, so extra keys pass, and its
  "never `:attribute`" check now covers the three new keys for free.

**Tests.** Extend
`app/Domains/Shared/Tests/Feature/DefaultValidationMessagesTest.php` (already
sets `fr` in `beforeEach`):
- `it('gives maxstripped a French default with the limit substituted')` —
  `Validator::make(['t' => '<p>abcdef</p>'], ['t' => ['maxstripped:3']])` →
  first error is `Ce champ ne doit pas dépasser 3 caractères.`
- `it('gives minstripped a French default with the limit substituted')` —
  same with `minstripped:10` → `Ce champ doit contenir au moins 10 caractères.`
- `it('gives required_trimmed a French default')` — value **`null`** →
  `Ce champ est obligatoire.` Note: `required_trimmed` is a non-implicit rule,
  so a direct `Validator::make` skips it for `''` and `'   '` (only `null`
  reaches it; over HTTP `TrimStrings` + `ConvertEmptyStringsToNull` turn blanks
  into `null`). Do not test with `''` — it would pass validation.
- Each asserts the message does not start with `validation.`.
- `it('keeps a form request override over the custom-rule default')` —
  `['t.maxstripped' => 'Custom']` wins.

**Acceptance.**
- ✅ In `fr`, `maxstripped:3` on a 6-char stripped value yields « Ce champ ne
  doit pas dépasser 3 caractères. »
- ✅ In `fr`, `minstripped:10` on a 6-char value yields « Ce champ doit
  contenir au moins 10 caractères. »
- ✅ In `fr`, `required_trimmed` on `null` yields « Ce champ est obligatoire. »
- ✅ An explicit `messages()` override still wins.
- ✅ `FrameworkValidationMessagesTest` unchanged and green.
- ✅ `pnpm run gate` green.

---

## Phase 2 — De-duplicate the « Oups » box

**Goal.** The layout's global error box (`<x-shared::flash-block>`) lists each
distinct validation message once, first-occurrence order kept (architecture
§1, §4, §7 T1; functional §4.1).

**Context.** `app/Domains/Shared/Resources/views/components/flash-block.blade.php`
is an anonymous component (no PHP class) included by the Shared app layout and
the Administration layout. Its error list loops `@foreach ($errors->all() as $error)`.

**Deliverables.**
- `app/Domains/Shared/Resources/views/components/flash-block.blade.php` —
  loop over `array_unique($errors->all())` instead of `$errors->all()`. Nothing
  else in the view changes (the `$errors?->any()` guard stays as is).

**Tests.** New
`app/Domains/Shared/Tests/Feature/View/Components/FlashBlockTest.php`
(`uses(TestCase::class)`; render with
`$this->withViewErrors([...])->blade('<x-shared::flash-block />')`, the pattern
of `UploadComponentsTest.php`):
- `it('lists a message shared by two fields once')` — errors
  `['blocks.0.image' => 'Same', 'blocks.1.image' => 'Same']` → `<li>` for
  `Same` appears exactly once (`substr_count` of `<li>Same</li>` = 1).
- `it('lists every distinct message in first-occurrence order')` — errors
  `['a' => 'First', 'b' => 'Second', 'c' => 'First']` → both present once,
  `First` before `Second`.
- `it('renders no error box without errors')` — `withViewErrors([])` → no
  `list-disc` list in the output.

**Acceptance.**
- ✅ Two fields failing with the same text produce one `<li>` in the box.
- ✅ Distinct messages all render, in original order.
- ✅ The field-level error display (not this component) is untouched.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter edit (author), two image blocks with a non-image file each, submit | « Oups » box lists the image message **once**; field-level list under the editor unchanged | |
| Same, plus a second distinct error (e.g. empty title) | Both messages in the box, each once | |
| Chapter edit, mobile width (≈375px) | Box still readable, no layout change | |
| Admin form (e.g. Calendar activity create, empty name) | Administration layout's box still shows its errors normally | |
| Any form submitted valid | No « Oups » box | |

The custom-rule defaults (phase 1) have no reachable UI today — every current
use overrides the message — so they are covered by tests only.

## Open items

None. Verified at PLAN: the `minstripped` replacer exists; the completeness test
is `english ⊆ french` (extras allowed); the `required_trimmed` test must use
`null` (see phase 1).
