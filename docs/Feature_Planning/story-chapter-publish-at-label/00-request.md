# Story — label the chapter form publish_at field — request

*Leftover from `shared-datetime-input-polish` VERIFY (pre-existing, not a regression).*

## What I want

The Story chapter form (`app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php`)
renders `<x-shared::datetime-local-input id="publish_at">` with no
`<x-shared::input-label for="publish_at">`. The date/time group is therefore
unnamed and the time input is announced only as « Heure ». Add the label (the
component already derives the group and time names from it).

## Why

Accessibility of the chapter publication date.

## Constraints or ideas I already have

Check the existing visible text near the field; reuse its translation key if any.
