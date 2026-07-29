# Extract an Editor domain from Shared — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

A new domain, `app/Domains/Editor`, owning no table. It follows the **Media**
shape exactly — the most recent table-less domain in the codebase:

- `Public/Api/EditorPublicApi.php` — the single PHP entry point
- `Public/Providers/EditorServiceProvider.php` — boots views, components, lang, presets
- `Private/…` — everything else, including the Blade components

Why a domain rather than a folder in `Shared`: the editor has a real public
surface (component contract, toolbar vocabulary, block schema, sanitisation
rules) *and* a dependency of its own on `Media`. `Shared` is the base layer;
having it depend on a feature domain inverts the architecture. Why not `Media`:
settled when Media was built — Media is about storing and serving images, not
about authoring text.

### 1.1 Changes in other domains

| Domain | Change | Kind |
|--------|--------|------|
| **Shared** | Delete the editor components, partials, `ContentBlocksRenderer`, the two lang files, the two test files, `editor-bundle.js`, `quill-emoji/`, and the chrome CSS rules. Keep `anchoring/` and all read-side CSS | Deletion |
| **News** | `NewsService` injects `EditorPublicApi` instead of `ContentBlocksRenderer` | Direct call |
| **Calendar, Comment, FAQ, Message, Profile, StaticPage, Story** | Component prefix + name, toolbar preset, and deletion of the hand-written `@vite` line | Blade only |
| **Comment, Story** (tests) | Three test files assert `shared::editor.*` lang keys → `editor::…` | Test-only |

No domain gains an extension point. The one candidate — a block-type registry —
is deliberately deferred (functional decision #4); `render()` keeps its per-type
dispatch in a single private method so the registry can replace it later without
touching any consumer.

## 2. Data model

### 2.1 Tables

None. `Editor` owns no table, no migration, no model. Stored content continues
to belong to the domains that already own it; its storage format is untouched.

### 2.2 Model

N/A.

### 2.3 Lifecycle rules

N/A — see §5 of the functional spec.

## 3. PHP architecture

### 3.1 Public API

```php
namespace App\Domains\Editor\Public\Api;

final class EditorPublicApi
{
    public function __construct(private readonly ContentBlocksRenderer $renderer) {}

    /** @param array<int, array<string, mixed>> $blocks */
    public function render(array $blocks): string;

    public function sanitizeText(string $html): string;

    /** @param array<int, array<string, mixed>> $blocks */
    public function plainTextLength(array $blocks): int;
}
```

A concrete class resolved from the container, like `MediaPublicApi` — not an
interface, since there is one implementation and no seam anyone needs to fake.

The **block schema** is Editor's data contract and is documented in the domain
README, not typed:

```php
['type' => 'text',  'html' => '<p>…</p>']
['type' => 'image', 'path' => 'news/x.jpg', 'alt' => '…', 'caption' => '…'?, 'keep_original' => bool?]
```

The **toolbar vocabulary** is published as presets resolved by name inside the
component (§4.2), not as a PHP constants class — Blade templates should not
carry fully-qualified class names for a list of strings.

### 3.2 Services

`Private/Support/ContentBlocksRenderer.php` — moved verbatim from
`Shared/Support/`, namespace changed, nothing else. It stays the only place that
knows the block schema, and `EditorPublicApi` delegates to it.

`Purifier::clean($html, 'multiedit-text')` keeps using the global profile in
`config/purifier.php`; that file is framework configuration, not domain code.

### 3.3 Policy / authorization

None. Editor has no route, no controller and no user-owned data — §3 of the
functional spec.

### 3.4 Events and listeners

None emitted, none listened to.

### 3.5 Routes, controllers, form requests

None. Editor registers no route. (The image picker endpoint the block editor
uses already belongs to `Media`.)

## 4. Frontend architecture

### 4.1 Components

| Today | After |
|-------|-------|
| `<x-shared::editor>` / `<x-editor>` | `<x-editor::rich-text>` |
| `<x-shared::multi-editor>` | `<x-editor::multi>` |
| `components/multi-editor/_text-block` etc. | `components/multi/_text-block` etc. |

Registered by `EditorServiceProvider` exactly as Media does it:

```php
$this->loadViewsFrom(app_path('Domains/Editor/Private/Resources/views'), 'editor');
Blade::anonymousComponentPath(app_path('Domains/Editor/Private/Resources/views/components'), 'editor');
$this->loadTranslationsFrom(app_path('Domains/Editor/Private/Resources/lang'), 'editor');
```

Only the prefixed path is registered — no unprefixed alias (decision #2).

Props are unchanged (assumption A1). The Alpine `multiEditor` component stays
inline in `multi.blade.php`, where it lives today; moving it into a JS module is
a redesign and out of scope.

### 4.2 Toolbar presets

Resolved by name inside the component, with the array form still accepted:

| Preset | Tokens | Current users |
|--------|--------|---------------|
| `default` | bold, italic, underline, strike, blockquote, align, list, custom-emoji | Comment ×3, Profile, Message, Story description, SecretGift, and every `multi` text block |
| `links` | `default` + link | Calendar activity description |
| `editorial` | `default` + header + link | FAQ, News, StaticPage |
| `narrative` | `default` + link + spoiler | Story chapter author note |

Presets are named after the capability they add, not after the domain that
happens to use them — Editor must not encode who its consumers are. An unknown
name resolves to `default`; the array form (`:toolbar="[…]"`) bypasses presets
entirely. The rendered toolbars must be **token-for-token identical** to today,
which is exactly what the mapping above encodes and what the moved component
test asserts.

### 4.3 Assets

Two Vite entries (decision #9), declared in `vite.config.js`:

- `app/Domains/Editor/Private/Resources/js/editor-bundle.js` (moved, with `quill-emoji/`)
- `app/Domains/Editor/Private/Resources/css/editor.css` (new — the chrome rules)

Both components push both entries, guarded so a page with several editors emits
them once:

```blade
@once
  @push('scripts')
    @vite(['app/Domains/Editor/Private/Resources/css/editor.css',
           'app/Domains/Editor/Private/Resources/js/editor-bundle.js'])
  @endpush
@endonce
```

`@once` is keyed per component, so a page mixing `rich-text` and `multi` would
push twice; both push the same two entries, and Laravel's Vite tag helper is not
idempotent across separate calls. The components therefore share **one partial**
(`components/_assets.blade.php`) with a single `@once` block, and each component
includes it.

The 15 hand-written `@vite` lines in consumer pages are deleted (decision #7).
A page that renders no editor now loads neither asset — including
`secret-gift.blade.php`, which is the intended behaviour (decision #8).

### 4.4 CSS split

Moves to `Editor/Private/Resources/css/editor.css` — rules that only apply while
editing:

- `.ql-toolbar` and everything scoped under it (including `.ql-toolbar button.ql-spoiler`)
- `.ql-tooltip` and its `data-label-*` pseudo-element rules
- `.ql-editor` rules, including `.ql-editor .ql-spoiler` and `.rich-content .ql-editor…`
- `.rich-content .ql-indent .ql-toolbar`

Stays in `Shared/Resources/css/app.css` — rules that style *stored* content on
read-only pages:

- `.ql-align-left|center|right|justify` and `p.ql-align-center`
- `.rich-content`, `.rich-content .ql-indent p`
- spoiler display on read-only pages

Rule of thumb for anything ambiguous: **if a page that never loads the editor
needs it, it stays in Shared.** The split is verified by loading a read-only
page (a chapter, a news article) with the editor bundle absent and checking that
alignment, indentation and spoilers still render — a VERIFY item.

### 4.5 Comment fragments

`CommentController` renders `comment::fragments.items` into an AJAX response.
A `@push` executed during a fragment render is discarded — there is no layout to
flush it into. This is already true of the editor's existing init `@push`, and
it keeps working because `comment-list.blade.php` renders a page-level editor
(the reply form), which loads the assets for the whole page before any fragment
is injected.

Self-loading does not change that ordering, but it makes the page-level editor
load-bearing. Recorded as risk R1 and as an explicit VERIFY item: edit and reply
to a comment after an AJAX page-load of the list.

## 5. Deptrac

New layers:

```yaml
- name: EditorPublic
  collectors: [{ type: directory, value: 'app/Domains/Editor/Public/' }]
- name: EditorPrivate
  collectors: [{ type: directory, value: 'app/Domains/Editor/Private/' }]
- name: EditorTests
  collectors: [{ type: directory, value: 'app/Domains/Editor/Tests/' }]
```

Ruleset changes:

| Change | Justification |
|--------|---------------|
| `Shared:` — **remove** `MediaPublic` | The point of the task. Nothing in Shared reaches into Media once the editor is gone |
| `EditorPublic: [Shared]` | Public API layer convention |
| `EditorPrivate: [Shared, EditorPublic, MediaPublic]` | The image block composes `<x-media::image-field>`; the renderer emits `<x-media::image>` |
| `NewsPrivate:` — **add** `EditorPublic` | `NewsService` injects `EditorPublicApi` |

Nothing is added for the other eight consumers: they touch Editor only through
Blade, which deptrac does not analyse, so an allowance there would be config
that lies and would pre-approve an unreviewed PHP coupling (tradeoff #1). Each
domain adds the edge the day it first calls Editor from PHP.

**`Shared → MediaPublic` must be removed in the same phase as the move**, or the
gate stays green while the whole point of the task is unverified.

## 6. Testing strategy

| Level | Coverage |
|-------|----------|
| Feature (moved) | `ContentBlocksRendererTest` and `MultiEditorComponentTest` move to `Editor/Tests/Feature/`, re-namespaced, assertions unchanged (assumption A5). They are the behaviour-preservation net for rendering and sanitisation |
| Feature (new, small) | One `EditorAssetsTest`: each component emits both Vite tags; a page rendering both components emits each tag exactly once; a page with no editor emits neither (tradeoff #3) |
| Feature (new, small) | One `EditorToolbarPresetTest`: each preset resolves to the exact token list of §4.2, an explicit array bypasses presets, an unknown name falls back to `default` |
| Feature (existing, updated) | The three Comment/Story tests asserting `shared::editor.*` keys |
| Feature (existing, untouched) | Every consumer page test — they must pass with no change beyond the lang keys. That is the real regression signal for the rename |
| Unit | None. Nothing here is isolated logic |
| Vitest | None. No JS module changes — `editor-bundle.js` moves byte-identical |
| VERIFY | The CSS split on read-only pages (§4.4); comment edit/reply through AJAX fragments (§4.5); one editing page per consumer domain; SecretGift with and without an editor |

**Build ordering constraint:** `public/build/manifest.json` is gitignored but
must exist for tests that render `@vite`. Renaming the entries invalidates it —
`npm run build` has to run after the asset move and before the PHP tests, or
every editor-rendering test fails on a missing manifest key. This bites at the
start of the asset phase and is worth stating in the plan.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | What goes in `deptrac.yaml` for the eight Blade-only consumers | (a) only where PHP references Editor; (b) all nine rulesets, as documentation | (a) | deptrac analyses PHP; 10 of 11 consumers use Blade only. An allowance for an edge that cannot exist is config that lies and silently pre-approves future coupling. Surfaced during DESIGN: the request's "consumers become deptrac-enforceable" payoff is not obtainable — the real payoff is deleting `Shared → MediaPublic` |
| 2 | How call sites name a toolbar preset | (a) string preset resolved in the component; (b) PHP constants class referenced from Blade | (a) | Blade-idiomatic and consistent with how `scope` is already passed; a constants class drags an FQCN into 11 templates for a list of strings. Stringly-typed, mitigated by the preset test |
| 3 | Regression net for the 15 deleted `@vite` lines | (a) component-level asset test + VERIFY; (b) assert on all seven consumer domains' page tests; (c) VERIFY only | (a) | The failure mode is "asset missing", which a component test catches at the source; (b) touches seven more test files for a case (a) already covers. Would flip if a consumer rendered an editor in a branch the component test cannot reach |
| 4 | Block schema typing | (a) keep arrays + document; (b) typed DTOs | (a) | Functional decision #4 — the seam that pays off for future block types is a registry, not DTOs. `render()` keeps single-point dispatch so the registry drops in later |
| 5 | Editor's public PHP surface | (a) `EditorPublicApi` facade; (b) move `ContentBlocksRenderer` into `Public/` | (a) | Functional decision #3 — one API per domain, as Media does |

## 8. File layout

```
app/Domains/Editor/
  Public/
    Api/EditorPublicApi.php                         new (delegates to the renderer)
    Providers/EditorServiceProvider.php             new
  Private/
    Support/ContentBlocksRenderer.php               moved from Shared/Support/
    Support/ToolbarPresets.php                      new (§4.2 mapping)
    Resources/
      css/editor.css                                new (chrome rules from Shared/app.css)
      js/editor-bundle.js                           moved from Shared/Resources/js/
      js/quill-emoji/                               moved from Shared/Resources/js/
      lang/fr/rich-text.php                          moved from Shared/…/lang/fr/editor.php
      lang/fr/multi.php                              moved from Shared/…/lang/fr/multi-editor.php
      views/components/
        rich-text.blade.php                         moved from Shared/…/components/editor.blade.php
        multi.blade.php                             moved from Shared/…/components/multi-editor.blade.php
        multi/_text-block.blade.php                 moved
        multi/_image-block.blade.php                moved
        multi/_insert-affordance.blade.php          moved
        _assets.blade.php                           new (§4.3, single @once)
  Tests/Feature/
    ContentBlocksRendererTest.php                   moved from Shared/Tests/Feature/
    MultiEditorComponentTest.php                    moved from Shared/Tests/Feature/
    EditorAssetsTest.php                            new
    EditorToolbarPresetTest.php                     new
  README.md                                         new
  AGENTS.md                                         new

modified:
  bootstrap/providers.php                           register EditorServiceProvider
  vite.config.js                                    two Editor entries replace the Shared one
  deptrac.yaml                                      §5
  app/Domains/Shared/Resources/css/app.css          chrome rules removed
  app/Domains/Shared/Providers/SharedServiceProvider.php   (only if it references moved files)
  app/Domains/Shared/{README,AGENTS}.md             editor references removed
  app/Domains/News/Private/Services/NewsService.php EditorPublicApi
  11 consumer Blade call sites                      prefix + name + toolbar preset
  15 consumer Blade pages                           @vite line deleted
  3 consumer test files                             lang keys

deleted:
  app/Domains/Shared/Resources/views/components/editor.blade.php
  app/Domains/Shared/Resources/views/components/multi-editor.blade.php (+ multi-editor/)
  app/Domains/Shared/Support/ContentBlocksRenderer.php
  app/Domains/Shared/Resources/lang/fr/{editor,multi-editor}.php
  app/Domains/Shared/Resources/js/editor-bundle.js (+ quill-emoji/)
```

Note: several files appear in both the `@vite`-deleted list and the call-site
list (e.g. `comment-list.blade.php`), so the real file count is ~26, not 26+15.

## 9. Risks acknowledged

| # | Risk | Trigger to revisit |
|---|------|--------------------|
| R1 | Comment's AJAX fragments contain editors whose `@push` is discarded; they work only because `comment-list` renders a page-level editor. Self-loading makes that page-level editor load-bearing | A future change removing the reply form from `comment-list`, or any other domain rendering an editor **only** inside a fragment. Then the asset must be pushed by the page, or the fragment must inline its own tag |
| R2 | The CSS split is a judgement call applied rule by rule; a misfiled rule silently degrades a read-only page | Any visual regression on a chapter, article or static page. §4.4 gives the discriminating test: does a page that never loads the editor need this rule? |
| R3 | Toolbar presets are stringly-typed; a typo falls back to `default` rather than failing | If a call site is ever found with a silently-wrong toolbar. The preset test covers the presets themselves, not the call sites' choice of one |
| R4 | The Vite manifest is gitignored, so a stale manifest fails every editor test after the entries are renamed | Immediate, at the asset phase. Mitigation is ordering, stated in §6 |
| R5 | This lands before `chapters-multi-edit/` and `annotations/`, both of which touch block anchoring. A long-lived branch here will conflict with them | If this task stretches across several sessions, land it in small merged phases rather than one branch |
