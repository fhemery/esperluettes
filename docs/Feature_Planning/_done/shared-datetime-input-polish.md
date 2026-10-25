# Shared — datetime input polish

**Status:** DONE — 2026-10-25 · **Domain(s):** `Shared`

## What it does

`<x-shared::datetime-local-input>` now announces which field it belongs to: the `role="group"` is named by the caller's label, and the time input reads « <label> Heure ». The date and time inputs have minimum widths so the pair wraps earlier on narrow screens (date text no longer touches the picker icon at 375 px).

## Key behaviour

- `<x-shared::input-label for="x">` now renders `id="x_label"` (unless it has its own `id`) — applies to every `for`-bound label app-wide.
- With component `id="x"`: group `aria-labelledby="x_label"`; time input `id="x_time"`, `aria-labelledby="x_label x_time"` (self-reference resolves to its `aria-label` « Heure »).
- Trap: a caller whose label lacks `for`, or a datetime input without `id`, gets no group name (dangling reference ignored). The required `*` is part of the accessible name.
- Min widths: date `min-w-[10rem]`, time `min-w-[7rem]`; `min-w-0` dropped. At 375 px in the Calendar form both still fit one row (160px + 117px).
- Submitted values unchanged; no call-site edits.

## Where the code lives

| Concern | Path |
|---------|------|
| Components | `app/Domains/Shared/Resources/views/components/{input-label,datetime-local-input}.blade.php` |
| Tests | `app/Domains/Shared/Tests/Feature/View/Components/DatetimeLocalInputTest.php` |
| Docs | `app/Domains/Shared/README.md` (components list) |

## Decisions worth remembering

- Naming goes through an `input-label` id (`aria-labelledby`), not an `aria-label` built from the label text.
- Earlier wrap via min widths on both inputs, not a date-only min width.

## Not done

- Out of scope: Auth activation-code and Events-log raw `datetime-local` filters.
- Layout/AT verified manually only; no e2e spec (one-off, nothing left in `e2e/tests/features/`).
- Pushed to backlog: Story chapter form has no `<label for="publish_at">` (group unnamed, time reads only « Heure »; pre-existing) — `story-chapter-publish-at-label/`.
