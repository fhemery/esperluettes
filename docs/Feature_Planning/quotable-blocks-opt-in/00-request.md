# Quotable blocks opt in — request

*Written by the orchestrator from the user's instruction during the DESIGN of
`multiedit-chapter-switch-block/` (its decision #12), 2026-09-26.*

## What I want

Quote anchoring should only read content that **opts into** being quotable,
instead of every text node of the chapter article.

Today `Shared/Resources/js/anchoring/canonical-text.js` walks every text node
under `[data-quote-article]`. Nothing marks a block as quotable or not:
images happen to drop out only because `<img>` has no text, and **image
captions (`<figcaption>`) are currently part of the canonical text**. Any new
block type with visible text (e.g. the upcoming chapter-choice buttons) would
silently become quotable.

Expected: in Avancé (multi-edit) chapters, text blocks carry an explicit
"quotable" property; other blocks (image, future chapter-choice) do not, and
their text never enters the canonical text. Simple-mode chapters (no blocks)
must keep working as today.

## Why

A debt we contracted without noticing when multi-edit landed. The
chapter-switch block needs labels excluded from quotes, and the user refuses an
opt-out stopgap that would have to be reversed later. This task unblocks
`multiedit-chapter-switch-block/`.

## Constraints or ideas I already have

- Users do not quote images today, so dropping captions from the canonical text
  should break nothing — but verify it against existing quotes (prefix/suffix
  may span a caption) before relying on it.
- All anchoring consumers must agree on the same canonical text: Quote
  `chapter-highlights.js`, `mini-form.js`, `author-heat.js`,
  `author-anchoring.js`, and `block-elements.js`'s block predicate.
- The marker is produced by the Editor renderer (text block wrapper), so stored
  chapter HTML needs it — decide whether existing chapters are re-rendered or
  whether the absence of any marker means "legacy, everything quotable".
- `annotations/` (backlog) overlaps on per-block anchoring — keep it in mind.

## Explicitly out of scope

The chapter-choice block itself.
