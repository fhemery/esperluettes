# Calendar: dedicated time field for activity dates — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.

## 1. Overview

Every date-and-time field in the app is a single native `datetime-local` input
whose calendar helps with the date only; the time has to be typed segment by
segment. Each such field becomes a **date field** (with its calendar picker) and
a **dedicated time field** side by side. Meant for the admins and moderators who
set up Calendar activities, and for every other form using the same field.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Champ date et heure | A form field holding one moment, now shown as a date input and a time input next to each other |

## 3. Roles & visibility

No change: the fields appear where they already do, for the same roles
(admins/moderators in the Calendar activity form; authors in the chapter form;
admins in activation codes and the domain events log).

## 4. Functional requirements

### 4.1 Entering a moment

1. The field shows a date input (calendar picker) and a time input side by side.
2. Choosing a date while the time is empty prefills the time with `00:00`; the
   user can edit it.
3. Clearing the date clears the time and leaves the field empty.
4. An existing value loads with both inputs filled, displayed in the browser's
   local time zone.
5. On submit the value is sent as before: local time converted to UTC.
6. Minutes are free (no step).
7. Validation (required, ordering of dates, etc.) and stored data are unchanged;
   a validation error re-displays the previously entered date and time.

### 4.2 Scope of the split

Every usage of the shared field: Calendar core form (visibility start/end, start,
end), Secret Gift (end of registrations), Quote Contest (end of submissions, start
of votes, plus its read-only mirror of the activity end), plus the Story chapter
form's scheduled publication. Auth activation codes and the Events log filters use
raw inputs with server-time semantics and stay as they are (DESIGN decision 5).

## 5. Lifecycle

N/A — no data is created or removed; nothing stored changes.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — same audiences as today |
| Visibility / privacy | N/A |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A |
| i18n | French labels unchanged; time input uses the browser's locale |
| Mobile | Two inputs must fit and stay usable at phone width |
| Accessibility | Both inputs keyboard-operable and labelled (the date/time pair shares the field label) |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | How is the time entered? | Date + time native inputs side by side |
| 2 | Scope | The shared component, so every datetime field in the app |
| 3 | Date chosen, time empty | Prefill `00:00` |

Assumptions (confirmed in the replay): clearing the date clears the time; Quote
Contest read-only mirrors get the same split; no server validation or storage
change; minutes free.

## 8. Out of scope

- Third-party date/time picker libraries.
- Changing time zone handling, validation rules or stored formats.
- Per-field minute steps or custom time dropdowns.
- Reworking the activity form layout beyond the date fields.

## 9. Open questions

None.
