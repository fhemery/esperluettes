# Editor Domain — Agent Instructions

- README: [app/Domains/Editor/README.md](README.md)

## Public API

- [EditorPublicApi](Public/Api/EditorPublicApi.php) — the **only** PHP entry point other domains use: `render`, `sanitizeText`, `plainTextLength`. Autowired concrete class; nothing to bind.
- `<x-editor::rich-text>` and `<x-editor::multi>` — the authoring components (see README). Registered by [EditorServiceProvider](Public/Providers/EditorServiceProvider.php).

## Events emitted

None.

## Listens to

None.

## Non-obvious invariants

**Blocks stay plain arrays.** The block schema (README) is Editor's data contract; it is deliberately not typed. DTOs would not remove the per-type dispatch, and the seam that actually pays off is a block-type registry — a redesign to make the day a third block type appears, not before.

**`ContentBlocksRenderer` is Editor-private and `EditorPublicApi` is its only caller outside.** Other domains inject the public API, never the renderer. Deptrac enforces it.

**The renderer emits `<x-media::image>` through `Blade::render()` at runtime.** That is why `EditorPrivate` depends on `MediaPublic`, and why a change to the `<x-media::image>` component's props can break rendering with no PHP-level signal. The renderer's image cases in `Tests/Feature/ContentBlocksRendererTest.php` are the only guard.

**There is no unprefixed `<x-editor>`.** Editor registers the `editor` anonymous-component path only. `Message`'s compose page used the unprefixed spelling before the extraction; it now uses `<x-editor::rich-text>` like everyone else.

**Toolbar presets are named after a capability, never after a consumer.** `Private/Support/ToolbarPresets::resolve()` maps `default` / `links` / `editorial` / `narrative` to token lists; an unknown name falls back to `default` silently, so a typo in a Blade call site degrades the toolbar instead of failing. Components reach the class through `@use()` — no FQCN inline in Blade. A domain that needs a new combination adds a capability-named preset here; it does not paste a token array back into its own view.

**A new Quill CSS rule has to pick a side.** Chrome (`.ql-toolbar`, `.ql-tooltip`, `.ql-editor`, editing-side spoiler) goes in Editor's `editor.css`, which only loads when an editor renders; anything present in *stored* HTML (`.ql-align-*`, `.rich-content`, `.ql-custom-emoji*`, the `:not(.ql-editor …)` spoiler variants) stays in `Shared/Resources/css/app.css`. Misfiling one degrades a read-only page with no test failure — state the side in a comment next to the rule.

**Rendered HTML is a derived cache.** Consumers that store blocks (News's `content_blocks`) also persist a rendered `content` column. The block array is the source of truth; the cache is rewritten from it on every save and never edited independently.

**The components load their own assets — never hand-write `@vite` for the editor.** `rich-text` and `multi` both `@include('editor::components._assets')`, a single `@once` + `@push('scripts')` holding the `@vite` of **two** entries: `Private/Resources/css/editor.css` then `Private/Resources/js/editor-bundle.js`. The `@once` lives in the shared partial on purpose: it is keyed per call site, so one guard per component would push twice on a page rendering both. A page with no editor loads no editor asset. `Tests/Feature/EditorAssetsTest.php` guards all of it — if you add a third entry, add it there too.

**Quill's own `snow` stylesheet rides on the JS entry.** `editor-bundle.js` imports it, so the JS entry emits an `editor-bundle-<hash>.css` link of its own alongside `editor-<hash>.css`. Any assertion that counts editor stylesheets must distinguish the two basenames.

**A `@push` inside an AJAX-rendered fragment is discarded** — there is no layout to flush the stack into. Comment's fragments carry editors and work only because `comment-list.blade.php` renders a page-level editor first. A domain that renders an editor *only* inside a fragment must push the assets from the page itself.

**`initQuillEditor` is idempotent.** It checks `container.dataset.quillInited` and skips if already initialised. Always call it by the container's `id`.

**Quill images are always blocked.** `editor-bundle.js` drops pasted and dropped images at the Quill level. Do not attempt to add image upload support through the Quill toolbar.

**Text sanitization uses the global `multiedit-text` Purifier profile** in `config/purifier.php` — framework configuration, not domain code. It forbids `<img>` on purpose: images must be image blocks so Media can track their paths.
