# Quotes — in-chapter author view (vNext) — request

Migrated from the Quotes v1 specification, which deferred this to a later
version. Source: [`../Quotes.md`](../Quotes.md) §4.5, §10 decision #6, §11.

## What I want

On the chapter page, show the author(s) of the chapter which passages have been
quoted by readers — a gutter marker with an aggregate count, following the same
pattern as the Annotations vNext roadmap.

## Why

Quotes are a strong signal of which passages land with readers. v1 gives that
signal to nobody but the reader who saved it; the author, who would act on it,
sees nothing.

## Constraints or ideas I already have

- Reader **notes are strictly private** (Quotes v1 decision #7) and must never
  become visible to the author through this view.
- v1 already notifies chapter authors on each quote
  (`ChapterPassageQuoted` → `ChapterQuotedNotification`), so the author already
  learns *that* a passage was quoted and by whom — this feature is about seeing
  it *in place*, in aggregate.
- The gutter pattern should be consistent with what Annotations does.

## Open question carried over from v1

**What exactly is shown to the author** — an aggregate count per passage, the
list of readers who quoted it, or both? Undecided; to be settled at REFINE.

## Explicitly out of scope

- Anything that would expose the private note.
- Moderation of quotes — that is a separate backlog task.
