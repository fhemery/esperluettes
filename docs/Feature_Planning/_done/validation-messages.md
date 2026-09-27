# Validation messages — file rules print raw keys

**Status:** DONE — 2026-09-27 · **Domain(s):** `Shared` (+ one Blade line each in
`Story`, `News`, `StaticPage`) · mode `auto`

## What it does

Every Laravel built-in validation rule now has a French default message, so a
rule a form request does not override (`image`, `max` on a file, `required`, …)
no longer prints its raw key (`validation.image`). The defaults are a standard
`validation.php` owned by Shared and registered **unnamespaced**. Separately, the
three block-editor forms (chapter, news, static page) now show per-block errors
(`blocks.<uid>.file`) in their existing `blocks` error list under the editor —
before, those errors reached only the layout's generic « Oups » box.

## Key behaviour

- Roles/visibility: N/A — whoever submitted the form sees the errors.
- A form request's own `messages()` always wins; the file only fills gaps.
- Messages never use `:attribute` — errors render under their own field, so no
  technical path (`blocks.3f2a.file`) can leak. Rules needing a second field use
  `:other` / `:values`. **Counter-intuitive:** `attributes` is empty, so those
  placeholders get Laravel's raw-name fallback (`password_confirmation` →
  « password confirmation »). No current request hits this.
- File sizes are stated in **Ko** (`:max`/`:min`/`:size` are kilobytes). The old
  JSON « :max Mo » default was wrong and is gone.
- JSON lines win over PHP groups in `Translator::get()`: **never** put
  `validation.*` keys back into `Shared/Resources/lang/fr.json`, they shadow the file.
- `validation.uploaded` kept its old text verbatim (« L'image ne vérifie pas… »).
- Calendar's `ValidatesActivityPayload` calls `trans('validation.required')` —
  now « Ce champ est obligatoire. » (was « Champ obligatoire »).
- Block errors: exact `blocks` errors merged with `Arr::flatten($errors->get('blocks.*'))`,
  de-duplicated — two blocks failing the same rule show the message once under
  the editor. `blocks_order` errors do not match `blocks.*`.
- Tests run in `zz` and still assert keys; French assertions switch to `fr`.
  Block-error tests assert on the field-level `<ul class="text-sm text-red-600 …">`
  list, because the flash box already lists every error on the page.

## Where the code lives

| Concern | Path |
|---------|------|
| French defaults | `app/Domains/Shared/Resources/lang-framework/fr/validation.php` |
| Registration (`FileLoader::addPath`) | `app/Domains/Shared/Providers/SharedServiceProvider.php` (`boot()`) |
| Block error area | `Story/Private/Resources/views/chapters/partials/form.blade.php`, `News/Private/Resources/views/pages/admin/news/_form.blade.php`, `StaticPage/Private/Resources/views/pages/admin/_form.blade.php` |
| Top « Oups » box (unchanged) | `Shared/Resources/views/components/flash-block.blade.php` |
| Tests — completeness, no `:attribute`, Ko | `Shared/Tests/Unit/FrameworkValidationMessagesTest.php` |
| Tests — French output, override wins | `Shared/Tests/Feature/DefaultValidationMessagesTest.php` |
| Tests — block errors | `ChapterAdvancedModeTest.php`, `NewsAdvancedModeTest.php` (+ 3000 Ko → « 2048 Ko »), `StaticPageAdvancedModeTest.php` |

No migration, no JS, no public API, no new deptrac edge.

## Extension points used

None. The completeness test compares the key set with the framework's
`Illuminate/Translation/lang/en/validation.php`, so a Laravel upgrade that adds a
rule fails the suite instead of printing a raw key.

## Decisions worth remembering

Decision #1 (the only arbitrated one): request accepted as drafted, `auto` mode.
Everything else is an **assumption made without asking** — all reversible:

| # | Assumption |
|---|------------|
| A1 | Scope is every built-in rule, not just `image`/`max` |
| A2/A3 | Block errors were silently dropped; making them visible was in scope, shown in the form's existing `blocks` area, not inline next to the block |
| A4 | « :max Mo » corrected to Ko |
| A5/A8 | No field paths; attribute-less phrasing instead of a French `attributes` map |
| A6 | Tests keep `APP_LOCALE=zz` |
| A7 | Defaults in a Shared PHP file via `addPath()` — rejected root `lang/` (code outside `app/Domains`), JSON (flat, shadows), `laravel-lang` package (dependency, uses `:attribute`). Must stay out of `Shared/Resources/lang/`, whose `fr/validation.php` is `shared::validation` and would merge |
| A9 | The three JSON `validation.*` keys removed |
| A10 | 2 phases; block-error tests added to existing `*AdvancedModeTest.php` files |
| A11 | Folder `lang-framework/`; subject per rule (« Ce champ » / « Cette valeur » / « Le fichier » / « Le texte »); `unique` → « Cette valeur est déjà utilisée. »; `custom` shipped empty |
| A12 | The flash box already showed the (raw) block errors before; it was left as-is, so block errors now appear in both places |

## Plan vs code

Code matches `02`/`03`. Only drift: the plan's tests asserted « on the page »;
BUILD narrowed them to the field-level list (A12). The Shared README
"Translations" section was updated in the same commit (stale `maxstripped` row fixed).

## Not done

- **Non-goals:** rewording domains' own `messages()`; client-side validation;
  changing the rules (2 Mo, types); inline per-block errors; locales other than French.
- **Oversize message rarely reachable:** Media's `<x-media::image-field>` rejects
  > 2 Mo client-side (« Le fichier dépasse la taille maximale de 2 Mo. ») and
  clears the input, so the server « 2048 Ko » text only shows when the guard is
  bypassed. Same limit, different wording — left as is, no row.
- **Pushed to backlog** — [`validation-messages-followups`](./validation-messages-followups.md) (done):
  1. the top « Oups » box repeats a shared block error once per faulty block
     (renders `$errors->all()` without de-dup, A12);
  2. Shared's own custom rules (`maxstripped`, `minstripped`, `required_trimmed`)
     have no default message — `validation.maxstripped` still prints raw
     (checked in `fr`). Latent: every current use overrides it in `messages()`.
- **e2e:** no spec added (all server-side / Blade, covered by feature tests);
  nothing to retire.
