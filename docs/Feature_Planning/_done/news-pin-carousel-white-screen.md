# News — pin to carousel white screen

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-08-08 · **Domain(s):** `Shared` (fix), `News` (reported surface)

## What it does

Stops Administration’s scrollable main pane from jumping to a blank viewport
when focusing a Shared toggle (reported on News « Épingler dans le carousel »).
The checkbox now overlays the visible track instead of living in a clipped
`sr-only` box, so focus stays where the switch is drawn. Pin still saves only
on form submit; no News PHP behaviour change.

## Key behaviour

- **Cause** — browser scroll-into-view of a clipped checkbox inside
  `Administration` layout’s `overflow-y-auto` `<main>`.
- **Fix locus** — `<x-shared::toggle>` (all admin consumers), not News-local and
  not the layout’s overflow.
- **Props / form semantics unchanged** — `name`, `checked`, `value`, `disabled`,
  `id`, `label`, colours; still a normal checkbox POST field.
- **VERIFY skipped** — scroll-stays-in-view was checklist-only; not covered by
  PHPUnit. Spot-check News edit pin toggle in a browser if regressing.

## Where the code lives

| Concern | Path |
|---------|------|
| Toggle markup | `Shared/Private/Resources/views/components/toggle.blade.php` |
| Blade contract tests | `Shared/Tests/Feature/View/Components/ToggleComponentTest.php` |
| News pin wire regression | `News/Tests/Feature/Admin/NewsControllerTest.php` (`persists is_pinned…`) |

## Extension points used

None.

## Decisions worth remembering

- Fix Shared once rather than a News workaround — same bug latent on FAQ,
  Calendar, StoryRef, etc.
- Overlay/positioned checkbox, no Alpine/JS.
- Sibling task `news-pin-carousel-first` (insert at order 1) is unrelated UI
  behaviour; see [`news-pin-carousel-first`](./news-pin-carousel-first.md).

## Not done

- Visual QA checklist (mouse/keyboard/narrow viewport) — skipped at wrap;
  re-open if the white screen still reproduces (possible Quill interaction).
- No e2e feature specs were added.
- Carousel reorder page and “pin as first” ordering were out of scope.
