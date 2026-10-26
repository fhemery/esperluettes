# Moderation — give the compact report button an accessible name — request

*Leftover from `fanart-miniature-report-flag` VERIFY (pre-existing defect, not a regression).*

## What I want

`app/Domains/Moderation/Private/Resources/views/components/report-button.blade.php`
in `compact` mode renders only `<span class="material-symbols-outlined">flag</span>`
plus a `title="Signaler"`. The icon's ligature text wins over `title` in the
accessible-name computation, so screen readers announce « flag » instead of
« Signaler ». Give the button a real accessible name (e.g. `aria-label` set to
`__('moderation::report.button')` when compact, and `aria-hidden="true"` on the
icon span). Check that `<x-shared::button>` forwards the attribute.

## Why

Accessibility (WCAG 4.1.2 / 1.1.1). Affects every compact consumer: the fanart
viewer, the fanart miniature flag, and any other caller of the component.

## Constraints or ideas I already have

Add a Pest test asserting the `aria-label` in the rendered markup. Existing e2e
specs locate the compact flag by `button[title="Signaler"]`; keep `title`.

## Explicitly out of scope

Any change to the report form, reasons or moderation flow.
