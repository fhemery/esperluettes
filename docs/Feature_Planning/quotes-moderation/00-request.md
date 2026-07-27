# Quotes — moderation of quotes and notes — request

Migrated from the Quotes v1 specification, which deliberately deferred this.
Source: [`../Quotes.md`](../Quotes.md) §4.6, §9, §11.

## What I want

Give moderators a way to act on abusive use of the quote feature.

## Why

v1 shipped with no moderation at all, on the reasoning that the quoted text is
verbatim chapter content (not user-generated) and the note is private. The
identified risk was **access-control circumvention**: saving quotes from a
chapter to retain readable content after losing access to it.

## Constraints or ideas I already have

- Reader notes are strictly private in v1 (decision #7). Letting a moderator
  read them contradicts that promise — v1 §11 lists "moderator access to private
  quote books" as an open question with no use case yet.
- Reporting topics register through `ModerationRegistry`; the pattern already
  exists in other domains.

## Open questions carried over from v1

- Is there an actual abuse case yet, or is this still speculative? If
  speculative, the honest answer may be to close this task rather than build it.
- If moderation is added: does a moderator see the quoted passage only, or also
  the private note? The privacy promise and the moderation need are in direct
  tension — that tension is the decision to take, not to paper over.

## Explicitly out of scope

- The in-chapter author view — separate backlog task.
