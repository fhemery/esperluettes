# Chapters — MultiEdit content — request

> **Stub.** Written by the orchestrator on 2026-07-28 to record why this task
> exists and what already constrains it. The *what I want* section is the user's
> to fill in or correct at REFINE — do not treat the sketch below as settled.

## Why this exists now

Raised while planning
[`../quotes-author-view/`](../quotes-author-view/) (decision #21). The user
decided chapters should move to MultiEdit **before** the in-chapter author view
is implemented, so quoting is built against the final content structure rather
than migrated twice. That task is now `BLOCKED` on this one.

## What I want (sketch — to confirm at REFINE)

Chapter content moves from the single `content` rich-text field to MultiEdit
content blocks, so a chapter can interleave text and images the way News already
does.

## What already exists

- `<x-shared::multi-editor>` with `_text-block`, `_image-block` and
  `_insert-affordance` partials, plus `Shared\Support\ContentBlocksRenderer`.
- **News** is a live consumer — see its
  `2026_07_24_000000_add_content_blocks_to_news_table` migration for the shape of
  the change and `NewsFormRendersMultiEditorTest` for the test pattern.
- Background: [`../multiedit/README.md`](../multiedit/README.md) — the compact
  record of MultiEdit v1 and the Media domain. `media-consumer-migration/` is
  about Media adoption, not the editor — it does **not** cover chapters.

## What the pre-loop plan asked for (Phase 5b)

Carried over from `MultiEdit_Planning.md` §5a–b and `MultiEdit_Architecture.md`
§10, which planned chapters as the last and largest adoption:

- `content_blocks` on `story_chapters`, scope **`chapters/{userId}`** —
  per-author scoping, unlike the flat scopes used elsewhere. `MediaService`
  already accepts this scope shape.
- A chapter `MediaUsageProvider` over the block image paths, **without which
  `media:gc` cannot see chapter images**.
- Repeating one image within a chapter (a separator used ten times) is supported
  natively by the path-based model — no special case.
- Word and character counts summed across text blocks, with the chapter
  minimum-length rule applied to the sum.
- The architecture also anticipated rendering each text block as its own
  `[data-annotable]` region so annotation canonical text builds per block. **That
  conflicts with the single-root constraint below** — reconcile the two at
  REFINE rather than assuming either.
- Image annotation stays out of scope.

## Hard constraint inherited from Quotes

Every **quotable text block must render inside a single `[data-quote-article]`
root**. Quote's re-anchoring builds one canonical text from that element
(`Shared/Resources/js/anchoring/`), and reader quotes saved before this migration
must still re-anchor afterwards.

If the migration splits chapter text across several roots, or changes the
rendered text of existing chapters, **every existing quote goes stale at once** —
silently, since a stale quote simply stops being tinted. That risk applies to
Quotes v1, which is already live, not only to the blocked author view.

Images are not quotable; only text blocks need to sit in that root.

## Open questions for REFINE

- Does existing chapter content need a data migration into blocks, or can a
  single legacy text block be rendered as-is?
- Does the reading page's typography (`prose rich-content [text-indent:2rem]`)
  survive block-per-block rendering unchanged? Any change to whitespace or
  element boundaries is an anchoring risk.
- Are chapter word/character counts, reading progress or search affected?
