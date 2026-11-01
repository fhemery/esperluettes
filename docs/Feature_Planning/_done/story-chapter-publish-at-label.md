# Label the chapter form publish_at field

**Status:** DONE — 2026-11-01 · **Domain(s):** `Story`

## What it does

On the chapter create/edit form, the scheduled-publication date/time field
(shown when « Publié » is off) now has the accessible name « Publication
planifiée ». Previously the group was unnamed and the time input read « Heure ».
No visual change.

## Key behaviour

- `<x-shared::input-label for="publish_at" class="sr-only">` added before `<x-shared::datetime-local-input id="publish_at">`, inside the `x-show="!published"` row (hidden together with the field).
- `input-label` auto-sets `id="publish_at_label"`; the datetime component's `aria-labelledby` already pointed at it, so the group and both inputs are named.
- Reuses the existing, previously unused key `story::chapters.form.publish_at.label`. No new key, PHP, or deptrac edge.
- Label is `sr-only` (assumption, reversible): flip to a visible label if wanted.

## Where the code lives

| What | Path |
|------|------|
| Label | `app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php` |
| Test | `app/Domains/Story/Tests/Feature/Chapters/CreateChapterTest.php` (`create form labels the scheduled publication field`) |

## Plan vs code

Plan and code agree; no deviation. VERIFY (browser): row bounding box identical before/after, accessibility tree shows the group and textboxes named.

## Not done

- Non-goals: visible label, restyling the row, other datetime fields (Calendar forms already labelled).
- No e2e spec written (markup-only, covered by the feature test).
- No open questions.
