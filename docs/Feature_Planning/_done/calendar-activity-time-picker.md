# Calendar: dedicated time field for activity dates

**Status:** DONE — 2026-10-24 · **Domain(s):** `Shared` (owner), `Calendar`, `Story`
## What it does

`<x-shared::datetime-local-input>` no longer renders one native
`datetime-local`: it renders a native `type="date"` (with its calendar picker)
and a native `type="time"` side by side, both **unnamed**, over one hidden
input that carries the original `name` and the combined `YYYY-MM-DDTHH:mm`
value. The server sees exactly what it saw before — same keys, same format,
same validation, no PHP change except Story's `old()` redisplay fix. Calendar's
activity form, Secret Gift, Quote Contest (including its two read-only
mirrors) and Story's chapter scheduled publication use it.

## Key behaviour

- Roles/visibility: unchanged — same fields, same audiences.
- `value` is always **UTC**; init converts it to browser local time (both
  modes). Default `submit="utc"` converts back on the form's `submit` event;
  `submit="local"` posts the local value as typed (Story, which posts `timezone`).
- Date picked with empty time → time `00:00`. Date cleared → time cleared,
  hidden `''`. A time typed without a date is dropped (nothing submitted).
- Minutes free: no `step` on the time input.
- Every edit rewrites the hidden input and dispatches a bubbling `input` on it;
  the submit-time UTC conversion dispatches nothing (a mirror would flash UTC).
- `mirror-of="<id>"`: no hidden input, both inputs `disabled` +
  `aria-disabled`, reads and follows `#<id>_value`. Quote Contest uses it for
  `qc_submissions_start_at` ← `active_starts_at`, `qc_votes_end_at` ←
  `active_ends_at` (replaces the old inline `getElementById` hack).
- `id` lands on the **date** input (so `<label for>` works); hidden id is
  `{id}_value`; time input `aria-label` = `shared::components.datetime_input.time` (« Heure »).
- `disabled` disables all three inputs (a disabled field is not submitted).
- `min` bounds the date input only. Story passes `now()->toDateString()`
  (server date); it used to be a client-side `now + 1 min` datetime in UTC.
  `after:now` server validation is the real guard.
- Story validation-error redisplay: `old('publish_at')` is the local value the
  user typed; the form converts it back to UTC with `old('timezone')` before
  handing it to the component. Without that, the time came back shifted by the
  offset (+2 h in summer) — pre-existing bug, fixed here (decision 7, `98dec795`).

## Where the code lives

| Concern | Path |
|---------|------|
| Component | `app/Domains/Shared/Resources/views/components/datetime-local-input.blade.php` |
| JS helpers | `app/Domains/Shared/Resources/js/date-utils.js` (`splitLocalInput`, `joinLocalInput`) |
| Lang key | `app/Domains/Shared/Resources/lang/fr/components.php` (`datetime_input.time`) |
| Calendar consumers | `Calendar/Private/Resources/views/pages/admin/activities/_form.blade.php` (unedited), `…/SecretGift/…/secret-gift-config.blade.php` (unedited), `…/QuoteContest/…/components/quote-contest-config.blade.php` (mirrors) |
| Story consumer | `app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php` |
| Tests | `Shared/Tests/Feature/View/Components/DatetimeLocalInputTest.php`, `Shared/Resources/js/date-utils.test.js`, `Calendar/Tests/Feature/Admin/ActivityControllerTest.php`, `Calendar/Tests/Feature/QuoteContest/AdminConfigTest.php`, `Story/Tests/Feature/Chapters/{Create,Edit}ChapterTest.php` |
| e2e page objects | `e2e/pages/DateTimeField.ts`, `AdminActivityFormPage.ts` (`field()`, `timeOf()`, `fillDateTime()`, QC getters), `ChapterCreatePage.ts` / `ChapterEditPage.ts` (`publishAt`) |
| Migrations | none |

## Extension points used

None — direct component calls. No new deptrac edge (Calendar and Story already
depend on Shared).

## Decisions worth remembering

- **Hidden combined input under the original name** (decision 6), not two
  named inputs recombined server-side: server contracts, `old()` and error keys
  stay identical.
- **Scope** (decision 5, supersedes 2 in part): Calendar + Story chapter.
  Auth activation codes and Events log filters keep a raw `datetime-local` —
  they use **server-time** semantics (no UTC conversion) and would need a third
  mode or a behaviour change.
- **Mirrors are a component mode** (assumption A1, never arbitrated by the
  user): `mirror-of` takes the source *component's* id and reads `{id}_value`.
- Native inputs only; time rendering (12/24 h, picker vs segments) follows the
  browser locale.

## Plan vs code

- Architecture §4.1 says the `role="group"` is labelled by the field label's
  id: **not built** — `<x-shared::input-label>` renders no id and call sites
  were kept unedited. The group has no accessible name.
- Architecture §4.1 says `mirrorOf` is "the id of another component's hidden
  input"; the code takes the source component's id and appends `_value`.
- Architecture §9's accepted `old('publish_at')` shift risk was removed: VERIFY
  reproduced it and phase 4 fixed it. Phase 4 has a row in the plan index but
  no detail section.

## Not done

- Non-goals (spec §8): third-party pickers; time-zone, validation or storage
  changes; minute steps or custom time dropdowns; activity form layout rework.
- Not converted: Auth activation codes, Events log filters (decision 5).
- Pushed to backlog: [`shared-datetime-input-polish`](./shared-datetime-input-polish.md)
  — group accessible name; at 375 px on the Calendar form the date text
  touches the picker icon (no overflow).
- e2e: `e2e/tests/features/calendar-activity-time-picker.spec.ts` (17 VERIFY
  rows) **deleted**, not promoted — the server contract is covered by PHP
  tests, and the Alpine behaviour is one component on admin/author forms, not
  a cross-app path. Page objects kept for the next browser test.
