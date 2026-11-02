# Moderation — accessible name for the compact report button

**Status:** DONE — 2026-11-02 · **Domain(s):** `Moderation`

## What it does

In `compact` mode `<x-moderation::report-button>` shows only the `flag` icon.
Its ligature text made screen readers announce « flag ». The button now carries
`aria-label` = `__('moderation::report.button')` (« Signaler ») and the icon
span is `aria-hidden="true"`. No visual change.

## Key behaviour

- Compact: `aria-label` and `title` both set to the report label; icon span hidden from the accessibility tree.
- Non-compact: no `aria-label` (visible text is the name); icon span is also `aria-hidden` (harmless).
- `<x-shared::button>` forwards the attribute bag as-is; `:aria-label="null"` is omitted by Blade.
- Keep `title`: e2e specs locate `button[title="Signaler"]`.

## Where the code lives

| Concern | Path |
|---------|------|
| View | `app/Domains/Moderation/Private/Resources/views/components/report-button.blade.php` |
| Tests | `app/Domains/Moderation/Tests/Feature/ReportButtonComponentTest.php` (compact aria-label + hidden icon; non-compact has no aria-label) |

## Extension points used

None.

## Decisions worth remembering

- Non-compact mode needs no `aria-label`.
- The empty `title=""` in non-compact mode was deliberately left alone.
- Tests compare against `__()` output, so they hold whether or not translations resolve.

## Not done

- Out of scope: report form, reasons, moderation flow; the empty `title=""` in non-compact mode.
- No e2e spec added; VERIFY checked the accessibility tree in the browser (`button "Signaler"` on fanart pages). Visual identity was not diffed (markup-only).
- No plan/code disagreement; nothing pushed back to the backlog.
