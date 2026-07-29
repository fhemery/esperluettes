# Editor Domain — Agent Instructions

- README: [app/Domains/Editor/README.md](README.md)

## Public API

- [EditorPublicApi](Public/Api/EditorPublicApi.php) — the **only** entry point other domains use: `render`, `sanitizeText`, `plainTextLength`. Autowired concrete class; there is no service provider and nothing to bind.

## Events emitted

None.

## Listens to

None.

## Non-obvious invariants

**Blocks stay plain arrays.** The block schema (README) is Editor's data contract; it is deliberately not typed. DTOs would not remove the per-type dispatch, and the seam that actually pays off is a block-type registry — a redesign to make the day a third block type appears, not before.

**`ContentBlocksRenderer` is Editor-private and `EditorPublicApi` is its only caller outside.** Other domains inject the public API, never the renderer. Deptrac enforces it.

**The renderer emits `<x-media::image>` through `Blade::render()` at runtime.** That is why `EditorPrivate` depends on `MediaPublic`, and why a change to the `<x-media::image>` component's props can break rendering with no PHP-level signal. The renderer's image cases in `Tests/Feature/ContentBlocksRendererTest.php` are the only guard.

**Rendered HTML is a derived cache.** Consumers that store blocks (News's `content_blocks`) also persist a rendered `content` column. The block array is the source of truth; the cache is rewritten from it on every save and never edited independently.

**Text sanitization uses the global `multiedit-text` Purifier profile** in `config/purifier.php` — framework configuration, not domain code. It forbids `<img>` on purpose: images must be image blocks so Media can track their paths.
