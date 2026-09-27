# Shared — button contrast in light themes — request

*Filed at WRAP of [`multiedit-chapter-switch-block`](../_done/multiedit-chapter-switch-block.md), which kept the accent colour and left the contrast fix to this task.*

## What I want

Button text must stay readable (WCAG AA, 4.5:1) on every season in light mode.

## Why

Measured in a browser (Playwright, computed colours) at 2026-09-27:

- `<x-shared::button>` **primary** (`surface-primary text-on-surface`): 3.1–3.6
  in light themes, 4.4–5.5 in dark.
- **accent** (`surface-accent text-on-surface`), used by the chapter-choice
  buttons: light spring **3.02**, summer 4.71, winter 5.69, autumn 6.51; dark
  5.1–7.1.

Both are below AA in at least one light season, and primary is the default
colour of every shared button in the app.

## Constraints or ideas I already have

- First question: fix the palette (season surface/on-surface tokens) or pick
  per-component colours? The first fixes every button at once.
- The chapter-choice buttons store their classes in `story_chapters.content`
  at save time: changing the classes there only reaches chapters saved again.
  Changing the colour tokens reaches them all.

## Explicitly out of scope

—
