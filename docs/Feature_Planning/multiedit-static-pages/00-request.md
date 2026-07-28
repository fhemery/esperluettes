# MultiEdit — advanced mode for static pages — request

Migrated from `MultiEdit_Planning.md` Phase 5a. MultiEdit v1 shipped on News —
see [`../multiedit/README.md`](../multiedit/README.md).

## What I want

Give static pages the same advanced (block) editing mode News has: a mode
toggle, `<x-shared::multi-editor>`, image blocks with upload and reuse.

## Why

Static pages are admin-authored long-form content — exactly the case the
multi-editor was built for. They are currently limited to a single rich-text
body with no way to interleave images.

## The plan as it stands

Mirrors Phase 4 (News) on `StaticPageService` and its form:

- `content_blocks` column on `static_pages`, scope `static-pages`.
- Conditional advanced validation rules in the form request: blocks array,
  per-block type/alt, `path`-xor-`file`, `path` within scope, summed-text
  min/max.
- Service advanced branch: resolve image blocks (store for files, reuse for
  paths), normalise and sanitise text blocks, persist `content_blocks`, write
  `content = ContentBlocksRenderer::render(...)`.
- Extend the StaticPage `MediaUsageProvider` to yield header `image_path` **and**
  every `content_blocks` image path — one provider, both sources.
- Public view unchanged (`{!! $content !!}`).

## Dependency

Do `media-consumer-migration/` first: StaticPage must be on `MediaPublicApi`
with a usage provider before that provider can be extended with block paths.

## Constraints

- Simple mode must be untouched — existing static pages keep working.
- The mode is remembered per page; returning from advanced to simple is subject
  to the same rules as News.

## Explicitly out of scope

- Chapters — separate backlog task (`chapters-multi-edit/`).
