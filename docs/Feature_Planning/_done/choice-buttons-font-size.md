# Chapter multiple choice: choice buttons use body font size

**Status:** DONE — 2026-11-02 · **Domain(s):** `Story`

## What it does

The `chapter-choice` block ("Choix de chapitres") rendered at the end of a chapter now shows its buttons at the chapter body font size. The fix is one class: `text-sm` was dropped from the anchors. No explicit size is set, so the buttons inherit the article size and follow any future body-size change.

## Key behaviour

- Visibility and roles unchanged; whoever sees the block sees the buttons.
- The block HTML is rendered at save time and stored in `story_chapters.content`. Only chapters saved after the change get the new size; already stored chapters keep `text-sm` until re-saved.
- Padding, colour, wrapping and centring are unchanged.

## Where the code lives

| Concern | Path |
|---------|------|
| Block render view | `app/Domains/Story/Private/Resources/views/editor/chapter-choice-render.blade.php` |
| Tests | `app/Domains/Story/Tests/Feature/Chapters/ChapterChoiceBlockTest.php` (asserts stored block has no `text-(xs\|sm\|base\|lg\|xl)`) |

## Extension points used

None.

## Decisions worth remembering

- Inherit rather than set `text-base` (A2): the buttons track the body size.
- Stored chapters are not migrated (A1).

## Not done

- Non-goals: editor-side block UI font sizes; button colours or padding.
- Not re-rendering already stored chapters; a re-render command would be a separate task, not filed.
- No e2e spec: a computed-style check in VERIFY (button and paragraph both 20px) was enough.
- Plan and code agree; no deviation.
