# Validation messages — file rules print raw keys — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Shared — French default validation messages | S | — | DONE |
| 2 | Story / News / StaticPage — show block errors in the blocks error area | S | 1 | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

Context for both phases: tests run with `APP_LOCALE=zz` / `APP_FALLBACK_LOCALE=zz`
(`.env.testing`) so they normally assert raw keys. Tests that check French text
call `app()->setLocale('fr')` explicitly (precedent:
`app/Domains/Shared/Tests/Unit/TranslationKeysExistTest.php`).

---

## Phase 1 — Shared: French default validation messages

**Goal.** Every Laravel built-in validation rule resolves to a French sentence
without `:attribute`, served from a Shared-owned, unnamespaced `validation.php`.

Reads: architecture §1, §3.6, §6, §7 (T1, T2, T4), §8.

**Deliverables.**
- `app/Domains/Shared/Resources/lang-framework/fr/validation.php` — **new**.
  Folder name may change if a better one fits, but it must **not** be
  `Resources/lang/` (that folder's `fr/validation.php` is the `shared::validation`
  namespace; registering it globally would merge the two).
  - Same key set and nesting as
    `vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php`
    (`max.file`/`max.string`/`max.numeric`/`max.array`, `between.*`, `size.*`, …),
    plus empty `'custom' => []` and `'attributes' => []`.
  - No message contains `:attribute`. Messages whose meaning needs another field
    may use `:other`, `:values`, `:value`, `:date`, etc.
  - File sizes in **Ko** (`:max`/`:min`/`:size` are kilobytes): e.g.
    `'image' => 'Le fichier doit être une image.'`,
    `'max' => ['file' => 'Le fichier ne doit pas dépasser :max Ko.', …]`,
    `'required' => 'Ce champ est obligatoire.'`.
  - `'uploaded'` keeps the current French text verbatim:
    « L'image ne vérifie pas les critères de taille et de format. »
- `app/Domains/Shared/Providers/SharedServiceProvider.php` — in `boot()`, next
  to the existing "Register language files" block, register the new folder as an
  unnamespaced path: `$this->app['translator']->getLoader()->addPath(__DIR__ . '/../Resources/lang-framework');`
  (`FileLoader::addPath` verified present in the installed framework).
- `app/Domains/Shared/Resources/lang/fr.json` — remove the three keys
  `validation.uploaded`, `validation.required`, `validation.max.file` (JSON wins
  over PHP groups in `Translator::get()`, so leaving them would shadow the new
  file; `max.file` also has the wrong unit, « Mo »).
- `app/Domains/Shared/README.md` — "Translations (French)" section: add a line
  for the framework defaults (`Resources/lang-framework/fr/validation.php`,
  unnamespaced, fills rules a form request does not override, attribute-less
  phrasing, Ko for file sizes); fix the `validation.php` row, which lists
  `maxstripped`/`minstripped` that are no longer in that file (it holds only
  `unique_profile_display_name`). Must not link to `docs/Feature_Planning`.

No change in Calendar: `ValidatesActivityPayload` calls
`trans('validation.required')`, which now resolves from the PHP file.

**Tests.**
- `app/Domains/Shared/Tests/Unit/FrameworkValidationMessagesTest.php`
  - `it covers every key of the framework's English validation file` — flatten
    both arrays with `Arr::dot()` (ignoring `custom`/`attributes` contents) and
    assert every English key exists in the French file. A framework upgrade
    adding a rule then fails here.
  - `it never uses :attribute in a French message`.
  - `it states file sizes in Ko` — `max.file`, `min.file`, `size.file`,
    `between.file` contain `Ko` and not `Mo`.
- `app/Domains/Shared/Tests/Feature/DefaultValidationMessagesTest.php`
  (locale `fr`)
  - `it translates image and max on a file into French` — `Validator::make`
    with an `UploadedFile::fake()->create('x.pdf', 3000)` against
    `['nullable', 'image', 'max:2048']`; messages contain « image » and
    « 2048 Ko », and none starts with `validation.`.
  - `it resolves validation.required from the PHP file` —
    `__('validation.required')` in `fr` is the new sentence (proves JSON no
    longer shadows it).
  - `it lets a form request's own messages win` — `Validator::make(..., rules,
    ['field.required' => 'Custom'])` yields `Custom`.

**Acceptance.**
- ✅ In `fr`, a file failing `image` + `max:2048` yields two French messages,
  the size one stating « 2048 Ko »; no message is a raw `validation.*` key.
- ✅ Every key of Laravel's English `validation.php` exists in the French file.
- ✅ No French default message contains `:attribute`.
- ✅ `fr.json` no longer contains any `validation.*` key.
- ✅ Existing tests (all in `zz`) unchanged and green.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Story / News / StaticPage: block errors in the blocks error area

**Goal.** Errors on individual editor blocks (`blocks.<uid>.file`, …) are shown
in each block-editor form's existing `blocks` error area.

Reads: architecture §1.1, §4, §6 (last bullet), §7 T3.

State after Phase 1: French defaults exist for every built-in rule
(`__('validation.image')` in `fr` is « Le fichier doit être une image. »,
`max.file` states Ko), served by Shared. Nothing in the three views changed yet.

**Why it is broken today.** Each form renders
`$errors->get('blocks')`, which matches only the exact key `blocks`; the
per-block errors (`blocks.b0.file`) are dropped. `$errors->get('blocks.*')`
returns them, but as a **nested** array keyed by field
(`['blocks.b0.file' => ['…']]`), and `<x-input-error>` echoes each item — so the
result must be flattened before being passed.

**Deliverables.** In each view, replace the `blocks` error line's `:messages`
with the exact-key errors merged with the flattened wildcard errors, de-duplicated
(two blocks failing the same rule show the message once), e.g.
`:messages="collect($errors->get('blocks'))->merge(\Illuminate\Support\Arr::flatten($errors->get('blocks.*')))->unique()->values()->all()"`.
Keep the existing component and classes of each line.
- `app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php`
  (line ~65, `<x-input-error>`)
- `app/Domains/News/Private/Resources/views/pages/admin/news/_form.blade.php`
  (line ~121, `<x-shared::input-error>`)
- `app/Domains/StaticPage/Private/Resources/views/pages/admin/_form.blade.php`
  (line ~119, `<x-shared::input-error>`)

No request, controller, JS or editor-component change. `blocks_order` errors do
not match `blocks.*` and are untouched.

**Tests.** One case added to each domain's existing advanced-mode test file,
reusing its setup/helpers (they already post `'mode' => 'advanced'` with image
blocks via `UploadedFile::fake()->image(...)`):
- `app/Domains/Story/Tests/Feature/Chapters/ChapterAdvancedModeTest.php` —
  `it shows an image block error in the blocks error area`: author (confirmed
  user) posts a chapter with `'blocks' => ['b0' => ['type' => 'image', 'file' =>
  UploadedFile::fake()->create('doc.pdf', 10)]]` from the create page, following
  the redirect, locale `fr`; response contains « Le fichier doit être une
  image. » and does not contain `validation.`.
- `app/Domains/News/Tests/Feature/Admin/NewsAdvancedModeTest.php` —
  same case as admin on the news create form; also a variant with an image of
  3000 Ko asserting « 2048 Ko ».
- `app/Domains/StaticPage/Tests/Feature/Admin/StaticPageAdvancedModeTest.php` —
  same case as admin on the static page create form.

**Acceptance.**
- ✅ Posting a chapter with a non-image file in an image block re-displays the
  form with « Le fichier doit être une image. » in the blocks error area.
- ✅ Same for news and static page forms (admin).
- ✅ An oversize image block shows « … 2048 Ko. » (News test).
- ✅ Existing `blocks.required` / `blocks.min` messages still render there.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter edit, advanced mode, desktop | Upload a non-image (e.g. PDF) in an image block, save → blocks error area shows « Le fichier doit être une image. » in French, red, under the editor | |
| Chapter edit, advanced mode, desktop | Upload an image > 2 Mo → message states « 2048 Ko », no raw key | |
| Chapter edit, mobile 375px | Same error renders under the editor without overflow | |
| News admin form, advanced mode | Non-image in an image block → French message in the blocks area | |
| News admin form, header image | Oversize header image → domain's own message still shown (unchanged) | |
| Static page admin form, advanced mode | Non-image in an image block → French message in the blocks area | |
| Any form relying on defaults (e.g. a required field left empty) | French sentence, no field path, no `validation.` key | |
| Chapter form, two faulty blocks with the same error | Message shown once | |

## Open items

- None blocking. Verified at PLAN: `FileLoader::addPath` exists; the three
  views render only `$errors->get('blocks')`; `ChapterRequest`, `NewsRequest`,
  `StaticPageRequest` all declare `blocks.*.file => ['nullable', 'image',
  'max:2048']` without a message override; no test or view asserts the old
  « Champ obligatoire » text; `TranslationKeysExistTest` only checks namespaced
  keys, so removing the JSON `validation.*` keys cannot trip it.
- Phase 2: if the redirect lands on a page whose layout itself leaks a
  `validation.` string elsewhere (unlikely), narrow the negative assertion to the
  blocks error list rather than the whole response.
