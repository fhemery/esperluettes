# Chapter annotations — image annotation — request

Split out of [`annotations-v4`](../annotations-v4/01-functional.md) at its
REFINE (2026-10-24, decision #1). Theme H of that task's request.

## What I want

Readers can annotate an **image block** of a chapter: attach a comment or a
reaction to a whole image (later, possibly a region of it), like text
annotations today.

## Constraints or ideas I already have

- Original vision: `annotations-v4/vision-spec.md` §10 (moves here when v4 wraps).
- Needs a **stable block anchor**, which does not exist today (found at v4 REFINE):
  - `story_chapters.content_blocks` is a position-only array, rebuilt on every
    save by `ChapterContentResolver`; editor uids (`b<i>`, `n<seq>`) are transient;
  - the rendered page carries no block attribute, and image blocks render as
    `<figure>`, which `closestBlock()` does not recognise;
  - the same image `path` can appear twice in a chapter, so the path is not a key.
- Likely needs a preceding task that persists block ids through the Editor and
  Story serialisation. Overlaps per-block anchoring: do not run in parallel with
  multi-edit work.
