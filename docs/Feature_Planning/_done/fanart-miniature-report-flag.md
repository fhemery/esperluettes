# Report flag on fanart miniature

**Status:** DONE — 2026-10-26 · **Domain(s):** `Fanart` (reuses `Moderation`)

## What it does

Every fanart miniature (`<x-fanart::thumbnail>`) carries a small flag button in
its lower right corner. Clicking it opens the existing report modal for topic
`fanart` and that fanart's id, without opening the viewer. The report flow,
reasons, snapshot and events are unchanged. No data, no route, no PHP.

## Key behaviour

- Shown only to authenticated viewers on fanarts they did not submit
  (`$fanart->user_id !== auth()->id()`, same rule as the viewer's `canReport`).
  Miniatures are only rendered for confirmed users anyway.
- Shown on pending fanarts too (same as the viewer).
- Always visible (no hover reveal); mobile tap target about 50x38 px.
- Not shown on audio rows of the profile tab without a miniature, nor on the
  chapter audio player (it has its own report button). Audio miniatures that
  render through `x-fanart::thumbnail` do get the flag.
- Counter-intuitive: the thumbnail is now a `relative w-40 h-40` wrapper `div`;
  the existing button fills it and the flag is its *sibling*, not a child.
  This avoids nested interactive elements and keeps the report modal (rendered
  inside the report-button's container) from being clipped by the button's
  `overflow-hidden`. Do not move the flag inside the button.
- Known defect (not fixed here): the compact `moderation::report-button` has
  accessible name « flag » (icon text beats `title`). Tests locate it by
  `button[title="Signaler"]`.

## Where the code lives

| Concern | Path |
|---------|------|
| Component | `app/Domains/Fanart/Private/Resources/views/components/thumbnail.blade.php` |
| Used by | fragments `group`, `tab`, `profile-group` |
| Reused component | `<x-moderation::report-button topic-key="fanart" :compact="true" size="sm">` |
| Tests | `app/Domains/Fanart/Tests/Feature/FanartFragmentsTest.php` (flag present on others', absent on own) |

## Extension points used

Moderation topic `fanart` (already registered); no new registration.

## Decisions worth remembering

- Flag is a sibling of the thumbnail button in a wrapper (no nesting, no clipping).
- Hidden on own fanart, mirroring the viewer.
- Always visible rather than hover-only, for touch devices.
- No new deptrac edge (Fanart already uses Moderation views in the viewer).

## Not done

- Non-goals: flag on audio rows without miniature, chapter audio player,
  moderator actions on the miniature, any change to the report form/flow,
  flag on fanart creation/edit pages.
- E2E: `e2e/tests/features/fanart-miniature-report-flag.spec.ts` deleted at
  WRAP (no core promotion); VERIFY passed it (story tab, profile tab incl.
  unclipped modal in nested groups, keyboard, mobile 375 px). No page-object
  member was left orphaned.
- Backlog row pushed back:
  [`moderation-report-button-accessible-name`](../moderation-report-button-accessible-name/00-request.md)
  — give the compact report button an `aria-label`.
