# Shared — datetime input polish — request

*Leftover pushed back by [`calendar-activity-time-picker`](../calendar-activity-time-picker/README.md)
WRAP (seen at VERIFY, accepted there as minor).*

## What I want

In `<x-shared::datetime-local-input>`
(`app/Domains/Shared/Resources/views/components/datetime-local-input.blade.php`),
the date + time pair:

1. **The `role="group"` wrapper has no accessible name.** The date input is
   named by the caller's `<label for="{id}">`, the time input only by
   « Heure ». A screen reader announces the time field without the field it
   belongs to (« Heure » for « Début de l'activité » vs « Fin »). The
   architecture wanted `aria-labelledby` on the group, but
   `<x-shared::input-label>` renders no id and call sites were kept unedited.
   Options: give `input-label` an id (e.g. `{for}_label`) and point the group
   at it, or build the time input's `aria-label` from the field label.
2. **375 px, Calendar admin activity form:** the date text touches the native
   picker icon (inputs are `min-w-0 flex-1` side by side). No overflow, just
   cramped. Give the date input a minimum width, or let the pair wrap earlier.

## Why

Accessibility of every date field in the app; legibility on phones.

## Constraints or ideas I already have

- Call sites: Calendar `_form.blade.php`, Secret Gift and Quote Contest config,
  Story `chapters/partials/form.blade.php`. Prefer a fix inside Shared that
  needs no call-site edit.

## Explicitly out of scope

- Converting the Auth activation-code and Events-log filters (raw
  `datetime-local`, server-time semantics).
