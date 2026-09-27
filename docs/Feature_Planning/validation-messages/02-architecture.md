# Validation messages — file rules print raw keys — architecture

> DESIGN output. Shape and contracts only; the change list is `03-plan.md`'s.
> Mode `auto`: every tradeoff below took the recommended option and is recorded
> as an assumption in `DECISIONS.md`.

## 1. Domain placement

**Shared** owns the framework's default validation messages: it already hosts
"translations shared across domains" and today holds the only generic
`validation.*` keys (three flat keys in `Shared/Resources/lang/fr.json`).

The defaults become a standard Laravel `validation.php` array (rules, nested
`max.file` / `size.file` / …, `between.*`, empty `custom` and `attributes`),
living in a dedicated Shared folder that `SharedServiceProvider` registers as an
**unnamespaced** translation path through the translator loader's `addPath()`
(`Illuminate\Translation\FileLoader::addPath`, verified present). Unnamespaced
is required: the validator looks up `validation.<rule>`, not `shared::…`.

It must be a folder distinct from `Shared/Resources/lang/` — that folder already
has a `fr/validation.php` serving the `shared::validation` namespace, and
registering it as a global path would merge the two files.

The three `validation.*` keys in `fr.json` are **removed**: JSON lines win over
PHP groups in `Translator::get()`, so leaving `validation.max.file` (« :max Mo »,
wrong unit) there would shadow the corrected PHP entry.

### 1.1 Changes in other domains

- **Story, News, StaticPage** — the block-editor form's existing `blocks` error
  area also renders errors on nested block keys (`blocks.*`), de-duplicated.
  Today `$errors->get('blocks')` matches only the exact key, so every per-block
  error (`blocks.<uid>.file`) is dropped. `MessageBag::get('blocks.*')` returns
  them.
- **Calendar** — `ValidatesActivityPayload` calls `trans('validation.required')`;
  it keeps working, now resolved from the PHP file instead of JSON. No change.
- No domain's own `messages()` changes (out of scope).

## 2. Data model

N/A — no table, no model.

## 3. PHP architecture

### 3.1 Public API
None.

### 3.2 Services
None.

### 3.3 Policy / authorization
N/A.

### 3.4 Events and listeners
None.

### 3.5 Routes, controllers, form requests
Unchanged. Form requests keep their `messages()`; the global file only fills
rules they do not override.

### 3.6 Message wording contract

- Every rule key in Laravel's own `Illuminate/Translation/lang/en/validation.php`
  exists in the French file, with the same nesting (`max.file`, `max.string`, …).
  Completeness is checked by a test against that English file, so a framework
  upgrade that adds a rule fails the suite instead of printing a raw key.
- Messages are phrased **without `:attribute`** (« Ce champ est obligatoire. »,
  « Le fichier doit être une image. », « Le fichier ne doit pas dépasser :max
  Ko. »). Errors in this app render under their own field, so the name is
  redundant, and phrasing without it means no field path (`blocks.3f2a.file`)
  can ever leak — no `attributes` map to maintain. Rules whose meaning needs a
  second field (`same`, `different`, `confirmed`, `required_with`, …) may name
  `:other`/`:values`, which Laravel substitutes.
- File sizes are stated in **Ko** (`:max` is kilobytes).
- `validation.uploaded` keeps its current French text (moved from JSON).

## 4. Frontend architecture

Blade only. No JS, no Alpine change: the editor component stays unaware of
errors; the host form's error area (existing `<x-shared::input-error>`) shows
them. No inline per-block placement (spec §8).

## 5. Deptrac

No new edge. Translation files and Blade views are not scanned as layer
dependencies; `SharedServiceProvider` touches only the framework translator.

## 6. Testing strategy

Tests run with `APP_LOCALE=zz` and keep asserting keys; the French assertions
switch to `fr` explicitly (precedent: `TranslationKeysExistTest`,
`StoryShowTest`).

- **Unit (Shared)** — completeness: every key of Laravel's English
  `validation.php` (flattened) exists in the French file; no French message
  contains `:attribute`.
- **Integration (Shared)** — in `fr`, a validator failing `image` and
  `max:2048` on a file yields French text containing « Ko » and never a
  `validation.` key; a request with its own `messages()` still wins.
- **Integration (Story, News, StaticPage)** — posting the block form with an
  image block whose file is not an image re-displays the form with the block
  error visible in the blocks error area (render assertion on the redirected
  page, locale `fr`).

## 7. Tradeoffs locked

| # | Question | Chosen | Rejected | Why |
|---|----------|--------|----------|-----|
| T1 | Where the defaults live | Shared-owned PHP `validation.php` in a dedicated folder registered with `addPath()` | (a) keep extending `Shared/fr.json` — flat keys only, so no `attributes`/`custom` arrays and awkward nested keys; (b) root `lang/fr/validation.php` — standard, but code outside `app/Domains` is forbidden unless requested; (c) `laravel-lang/lang` package — a dependency and a publish step for one file, and its messages use `:attribute` | Stays inside the domain model, uses the framework's native format, one file |
| T2 | Field names in messages | Attribute-less phrasing | `:attribute` + an `attributes` map (global or per-request `attributes()`) | Errors already sit under their field; no map to maintain; no path can leak |
| T3 | Where block errors appear | Existing `blocks` error area in each host form | Inline next to the faulty block inside `<x-editor::multi>` (needs JS error plumbing into the editor) | Minimum fix; spec §8 |
| T4 | Existing JSON `validation.*` keys | Removed, moved into the PHP file | Left in place | JSON shadows PHP; the `max.file` one has the wrong unit |

## 8. File layout

- `app/Domains/Shared/Resources/lang-framework/fr/validation.php` — new (name of
  the folder is BUILD's call if a better one fits Shared's layout; it must not be
  `Resources/lang/`).
- `app/Domains/Shared/Providers/SharedServiceProvider.php` — registers the path.
- `app/Domains/Shared/Resources/lang/fr.json` — three keys removed.
- Block-editor form views in Story, News, StaticPage — error area.
- Shared README: the "Translations" section gains the framework defaults and
  loses the stale `maxstripped`/`minstripped` mention.

## 9. Risks acknowledged

- Any view or test relying on the exact current text « Champ obligatoire »
  changes wording. Tests run in `zz`, so only `fr`-locale tests could notice.
- Rules overridden via `messages()` with a partial key (e.g. `'max'` rather than
  `'max.file'`) behave as before — not touched.
