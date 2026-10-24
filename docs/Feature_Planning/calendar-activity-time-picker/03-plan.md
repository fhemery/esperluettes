# Calendar: dedicated time field for activity dates — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Shared — `splitLocalInput` / `joinLocalInput` helpers | S | — | DONE |
| 2 | Shared — split `<x-shared::datetime-local-input>` (UTC mode + mirror mode), Calendar consumers | M | 1 | DONE |
| 2v | Checkpoint — Calendar admin activity form (core, Secret Gift, Quote Contest) | S | 2 | DONE |
| 3 | Story — chapter `publish_at` on the component (`submit=local`, `min`) | S | 2 | DONE |
| 4 | Story — chapter form: redisplay the typed `publish_at` unshifted after a validation error (VERIFY defect, decision 7) | S | 3 | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.
Checkpoint rows (`2v` — see `plan-phases`) are run by `visual-verifier` and
change no code.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- No server code changes in any phase: request keys, formats (`Y-m-d\TH:i`),
  validation and storage stay exactly as they are (architecture §2, §3).

**Why the Quote Contest mirror is in phase 2 and not its own phase.** The
existing mirror reads `document.getElementById('active_ends_at').value`. Once
phase 2 puts that id on a `type="date"` input, the raw mirror would show a
date-only value in a `datetime-local` box (i.e. nothing). Splitting the mirror
into a later phase would leave the Quote Contest form broken between phases,
so it ships with the component.

---

## Phase 1 — Shared: `splitLocalInput` / `joinLocalInput` helpers

**Goal.** Add the two pure string helpers the split component will use, with
vitest coverage, without touching any consumer.

Architecture: §4.2.

**Deliverables.**
- `app/Domains/Shared/Resources/js/date-utils.js` — add and export:
  - `splitLocalInput(value: string) → { date: string, time: string }` —
    `'2026-06-21T15:07'` → `{ date: '2026-06-21', time: '15:07' }`; accepts the
    `' '` separator and a trailing `:ss` like `utcToLocalInput` does (keep the
    first 16 chars); `''`, `null`, `undefined` or unparseable → `{ date: '', time: '' }`.
  - `joinLocalInput(date: string, time: string) → string` — `''` when `date` is
    empty (whatever `time` holds); `${date}T00:00` when `time` is empty;
    `${date}T${time}` otherwise (`time` truncated to `HH:mm` if a browser hands
    back `HH:mm:ss`).
  - Add both to the usage comment at the top of the file.
- No other file. `window.DateUtils` already exposes every export of this
  module (`app/Domains/Shared/Resources/js/app.js`), so nothing to wire.

**Tests.** In `app/Domains/Shared/Resources/js/date-utils.test.js`, new
`describe('split / join of a local datetime value')`:
- `splits a combined local value into date and time`
- `splits a value with a space separator and seconds`
- `splits an empty or unparseable value into two empty strings`
- `joins a date and a time into the combined shape`
- `defaults the time to 00:00 when only the date is set`
- `returns an empty string when the date is empty, even with a time`
- `truncates a time carrying seconds`
- `round-trips split -> join` (including a DST-adjacent local string such as
  `2026-03-29T02:30`, which is a plain string here and must come back untouched —
  no `Date` parsing in these helpers)

**Acceptance.**
- ✅ The new vitest cases pass; the existing UTC conversion cases are untouched and pass.
- ✅ No Blade or PHP file changed.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Shared: split `<x-shared::datetime-local-input>`, Calendar consumers

**Goal.** The shared component renders a date input and a time input side by
side over one hidden input carrying the original `name`, keeps today's
local↔UTC behaviour, and gains a read-only `mirrorOf` mode that replaces the
Quote Contest DOM-reading mirrors.

Architecture: §1.1, §4.1, §4.3, §6, §7 (decisions 6, A1). Builds on phase 1:
`window.DateUtils.splitLocalInput` / `joinLocalInput` exist (plus the existing
`utcToLocalInput` / `localToUtcInput`).

**Today's state.** The component
(`app/Domains/Shared/Resources/views/components/datetime-local-input.blade.php`)
is a single `<input type="datetime-local">`; all attributes (`id`, `name`,
`value`, `class`) are merged onto it; Alpine converts UTC→local on init and
local→UTC on the parent form's `submit`. Consumers: Calendar core form
(`preview_starts_at`, `active_starts_at`, `active_ends_at`, `archived_at`),
Secret Gift (`sg_registration_ends_at`), Quote Contest (`qc_submissions_end_at`,
`qc_votes_start_at`). Quote Contest also has two raw read-only mirrors
(`qc_submissions_start_at` ← `active_starts_at`, `qc_votes_end_at` ←
`active_ends_at`) built from `<x-shared::text-input type="datetime-local">`
with a wrapper `x-data="{ mirrored }"` reading the source by id.

**Deliverables.**
- `app/Domains/Shared/Resources/views/components/datetime-local-input.blade.php` — rewritten:
  - `@props(['name' => null, 'id' => null, 'value' => '', 'disabled' => false, 'mirrorOf' => null])`.
  - Wrapper `<div role="group" class="flex flex-wrap gap-2 …">` holding an
    Alpine `x-data` scope (`date`, `time`, `hidden` value).
  - `<input type="date">` — carries `id` (so the callers' `<label for>` still
    focuses it), no `name`.
  - `<input type="time">` — no `name`, no `step`, `aria-label` =
    `__('shared::components.datetime_input.time')`.
  - Both visible inputs get the current class merge
    (`border-accent focus:border-accent/80 focus:ring-accent surface-read text-on-surface`)
    plus the caller's `class` (`$attributes->get('class')`), so call sites
    (`mt-1 block w-full`) need no edit. They must fit side by side or wrap
    cleanly at 375 px (`min-w-0 flex-1` or equivalent on each — BUILD's call,
    checked visually).
  - `<input type="hidden" name="{name}" id="{id}_value" value="{value}">` —
    rendered only when not in mirror mode. Its server-rendered `value` is the
    raw UTC string (so existing render tests asserting `name="…"` and
    `2026-09-20T10:00` keep passing).
  - Alpine behaviour (architecture §4.1, points 1–6):
    1. init: if the hidden value is set, `utcToLocalInput` it, write it back to
       the hidden input, split into `date`/`time`, then dispatch a bubbling
       `input` event on the hidden input (so mirrors catch up whatever the init
       order);
    2. date set while time empty → time `00:00`;
    3. date cleared → time cleared, hidden `''`;
    4. any edit → hidden = `joinLocalInput(date, time)`, dispatch `input` on it;
    5. on the closest form's `submit`: if hidden is non-empty, replace it with
       `localToUtcInput(hidden)`; **no** `input` dispatch (a mirror must not
       flash the UTC value before navigation);
    6. `disabled` disables both visible inputs and the hidden input (a disabled
       field is not submitted today either).
  - Mirror mode (`mirror-of="<source component id>"`): no hidden input; both
    visible inputs `disabled` + `aria-disabled="true"`; on init reads
    `document.getElementById(mirrorOf + '_value')`, splits its value (already
    local by then — no UTC conversion), and re-splits on each `input` event from
    it. Absent source → both empty.
  - Replace the Blade comment at the top with one describing the new shape
    (hidden carries the value; local in the DOM while editing, UTC at submit).
- `app/Domains/Shared/Resources/lang/fr/components.php` — add
  `'datetime_input' => ['time' => 'Heure']`.
- `app/Domains/Calendar/Private/Activities/QuoteContest/Resources/views/components/quote-contest-config.blade.php`
  — replace both raw mirrors with
  `<x-shared::datetime-local-input id="qc_submissions_start_at" mirror-of="active_starts_at" class="mt-1 block w-full opacity-60 cursor-not-allowed" />`
  and the same for `qc_votes_end_at` / `active_ends_at`; drop the wrapper
  `x-data="{ mirrored }"` / `x-init`. Keep the `mirrored_hint` paragraphs and
  the comment above them.
- Calendar core form (`…/pages/admin/activities/_form.blade.php`) and Secret
  Gift config: **no edit expected** — they pass `id`, `name`, `class`, `:value`,
  which the new props absorb. Edit only if a test proves otherwise.
- `e2e/pages/AdminActivityFormPage.ts` — the `previewStartsAt`,
  `activeStartsAt`, `activeEndsAt`, `registrationEndsAt` getters keep pointing
  at `input#<id>` (now the date input); add a `timeOf(dateInput: Locator)`
  helper (the sibling `input[type="time"]` in the same `[role="group"]`) and an
  `async fillDateTime(dateInput: Locator, date: string, time: string)` that
  fills both. Update the class docblock to say a moment is two inputs. No spec
  uses these getters today (checked: only `confirm-modal.spec.ts` imports the
  page, for the shuffle panel), so no spec changes.

**Tests.**
- New `app/Domains/Shared/Tests/Feature/View/Components/DatetimeLocalInputTest.php`
  (`$this->blade(...)`, as in `ConfirmModalA11yTest.php`):
  - `renders a date input carrying the id and a time input, both unnamed`
  - `carries the name and the raw value on a hidden input`
  - `labels the time input` (asserts the raw key
    `shared::components.datetime_input.time` — tests run under `APP_LOCALE=zz`)
  - `sets no step on the time input`
  - `applies the caller's classes to both visible inputs`
  - `disables every input when disabled`
  - `renders no named input in mirror mode and disables both visible inputs`
- `app/Domains/Calendar/Tests/Feature/Admin/ActivityControllerTest.php`, under
  `describe('edit')`: `renders each activity date as a hidden input under its
  original name with the stored value` — an activity with known
  `preview_starts_at` / `active_starts_at` / `active_ends_at` / `archived_at`;
  asserts `type="hidden" name="active_starts_at"` … and each
  `Y-m-d\TH:i` value, plus `type="date" id="active_starts_at"`.
- `app/Domains/Calendar/Tests/Feature/QuoteContest/AdminConfigTest.php`:
  `renders the activity date mirrors read-only, without a name` — edit form
  contains `id="qc_votes_end_at"`, does not contain `name="qc_votes_end_at"` /
  `name="quote_contest[votes_end_at]"`, and no longer contains the old
  `getElementById('active_ends_at')` hack.
- Existing tests that must stay green **unedited**:
  `SecretGift/AdminConfigTest` (`renders the deadline field … prefills it on edit`),
  `QuoteContest/AdminConfigTest` (`shows the stored contest dates on the edit form`),
  all Calendar store/update tests (they post the same keys).
- `TranslationKeysExistTest` covers the new `fr` key.

**Acceptance.**
- ✅ Every Calendar date field renders one `type="date"` (with the field id), one
  `type="time"` and one `type="hidden"` with the original `name` and the stored
  UTC value.
- ✅ Existing Calendar feature tests pass without modification.
- ✅ The Quote Contest mirrors render no named input and no inline
  `getElementById` script.
- ✅ `pnpm exec tsc -p e2e` passes (run by the gate since `e2e/` changed).
- ✅ `pnpm run build` then `pnpm run e2e confirm-modal` green (the page object
  file changed).
- ✅ `pnpm run gate` green.

---

## Checkpoint 2v — Calendar admin activity form

Run by `visual-verifier`. Changes no code. Looks at the only existing consumers
of the component, which phase 2 reshaped, before Story builds on it.

- **Role:** admin (and one pass as moderator, who also reaches the form).
- **Pages:** `/admin/calendar/activities/create` with type Jardino, Secret
  Gift, Quote Contest; the edit form of a seeded Secret Gift and a seeded Quote
  Contest.
- **Look at:**
  - each date field shows a date and a time input, filled on edit with the
    **local** time (browser in Europe/Paris: a stored `10:00` UTC shows `12:00`
    in summer);
  - picking a date on an empty field prefills `00:00`; clearing the date clears
    the time;
  - save an edit without touching the dates, reopen: values unchanged (no
    double conversion);
  - change a time, save, reopen: the new local time shows;
  - submit with a validation error (e.g. Secret Gift deadline after the
    activity start): the form comes back with the entered date/time, not shifted;
  - Quote Contest: the two greyed mirrors show the activity start/end and follow
    live edits of those fields;
  - 375 px width: the pairs fit or wrap without overflow.
- **e2e:** `pnpm run build`, then `pnpm run e2e confirm-modal`.
- **Output:** screenshots under `shots/checkpoint-2v/`, then append a
  `**Result (<date>, HEAD <sha>) — PASS|FAIL**` block here. FAIL sends phase 2
  back to a `phase-implementer`.

**Result (2026-10-24, HEAD 95e89f81) — PASS**

Driven on the e2e instance (seeded activities), browser Europe/Paris, fr-FR.
Shots in `shots/checkpoint-2v/`.
- `pnpm run build` OK; `pnpm run e2e confirm-modal`: 8 passed. (Needed
  `E2E_PORT=8080 E2E_BASE_URL=http://localhost:8081` in this worktree, whose
  e2e port is published as 8081; plain run times out on the web server.)
- Create form (admin, moderator): every date field is an empty date + time pair; the type is fixed per page, all config panels render.
- Pick date on empty field prefills `00:00`; time editable to `13:37`; clearing the date clears time and hidden value; label click focuses the date input; Tab reaches the time input labelled « Heure » (after the date's day/month/year segments).
- Edit SG (stored UTC 20:10 shown 22:10 in CEST, 21:10 after the DST switch): untouched save then reopen identical; changing only the start time to 09:45, saved, reopens at 09:45.
- Validation error (SG deadline after start): error shown, entered 2026-12-30 11:11 and 10:20 redisplayed unshifted.
- Quote Contest edit: both mirrors disabled, show activity start/end, follow live edits of date and time; untouched save round-trips identically.
- 375 px: no horizontal overflow on SG and QC edit, pairs stay side by side.
- Moderator: create form renders the same pairs. Moderator edit/save not driven separately.
- Not checked: dark theme (outside this checkpoint).

---

## Phase 3 — Story: chapter `publish_at` on the component

**Goal.** The chapter form's scheduled-publication field uses the split
component, submitting the local value as typed alongside the existing
`timezone` field.

Architecture: §1.1 (Story), §4.1 (`submit`, `min` props), §9 (first risk).
Builds on phase 2: `<x-shared::datetime-local-input>` already renders a
date + time pair over a hidden input named after `name` (id `{id}_value`),
converts the hidden value UTC→local on init, and local→UTC on submit.

**Today's state.**
`app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php`
(≈ lines 72–114): a raw `<input type="datetime-local" id="publish_at"
name="publish_at" x-model="publishAtLocal" :min="…">` inside an `x-data`
holding `published`, `timezone` and `publishAtLocal` (initialised with
`DateUtils.utcToLocalInput($existingPublishAt)`), plus
`<input type="hidden" name="timezone" :value="timezone">`.
`ChapterRequest` validates `publish_at` as `nullable|date|after:now` and
resolves it with the `timezone` field — server untouched.

**Deliverables.**
- `app/Domains/Shared/Resources/views/components/datetime-local-input.blade.php`:
  - new prop `submit` (default `'utc'`); with `'local'` the submit-time
    `localToUtcInput` step is skipped. The init-time UTC→local conversion runs
    in **both** modes (Story's stored value is UTC, and today's code converts it
    the same way — architecture §9 keeps the pre-existing `old()` shift as is).
  - new prop `min` (default `null`): when set, rendered as `min` on the date
    input only.
- `app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php`:
  - replace the raw input with
    `<x-shared::datetime-local-input id="publish_at" name="publish_at" submit="local" :min="now()->toDateString()" :value="$existingPublishAt" class="…existing classes…" />`;
  - remove `publishAtLocal` from the `x-data` (verified: nothing else reads
    it); keep `published`, `timezone` and the hidden
    `timezone` input;
  - keep the `schedule` icon and the `x-show="!published"` wrapper.

**Tests.**
- `DatetimeLocalInputTest.php` (Shared, from phase 2), add:
  - `sets min on the date input only`
  - `skips the UTC conversion at submit in local mode` (asserts the rendered
    Alpine config carries the mode — e.g. `submit: 'local'` — since the submit
    handler itself is browser-only)
- `app/Domains/Story/Tests/Feature/Chapters/EditChapterTest.php`:
  `renders the scheduled publication as a date/time pair over a hidden publish_at`
  — an unpublished chapter with `publish_at` set; asserts
  `type="hidden" name="publish_at"` with the stored `Y-m-d\TH:i`,
  `type="date" id="publish_at"`, a `type="time"` input, and `name="timezone"`.
- `CreateChapterTest.php`: `renders the scheduled publication field empty on create`
  — `type="hidden" name="publish_at"` with an empty value.
- Existing `CreateChapterTest` / `EditChapterTest` /
  `PublishScheduledChaptersCommandTest` stay green unedited (same keys posted).

**Acceptance.**
- ✅ The chapter create/edit forms render the pair over a hidden `publish_at`,
  and still render the hidden `timezone` field.
- ✅ The raw `type="datetime-local"` input is gone from the chapter form.
- ✅ Calendar forms unchanged (`submit` defaults to `utc`): phase 2's tests still green.
- ✅ Existing Story chapter tests pass without modification.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. Browser in `Europe/Paris`, locale `fr-FR`.

| Surface | Check | OK? |
|---------|-------|-----|
| Activity create (admin), core dates | Four fields each show date + time; empty on create; label click focuses the date input | ✅ spec (create-core-dates.png) |
| Activity create, empty state | Picking a date with empty time prefills `00:00`; time stays editable to any minute (no step) | ✅ spec (13:47 accepted, no `step` attr) |
| Activity create, clearing | Clearing the date clears the time; saving sends no value for that field (nullable fields stay null) | ✅ spec (hidden empty; saved `archived_at` NULL; Paris 08:15 stored 07:15Z) |
| Activity edit (admin) | Stored UTC values show in local time; saving untouched reopens with identical values | ✅ spec, compared to the minute against the DB (seeded rows carry seconds the field drops, as before) |
| Activity edit, change time | Change only the time, save, reopen: the new local time shows | ✅ spec |
| Activity edit (moderator) | Same form, same behaviour | ✅ spec (same test, moderator role) |
| Validation error | Secret Gift deadline after activity start: error shown, entered date/time redisplayed unshifted | ✅ spec (17:43 stays 17:43; error shown) |
| Secret Gift config | Registration deadline pair behaves as the core fields | ✅ spec |
| Quote Contest config | Submissions end / votes start pairs behave as the core fields | ✅ spec (quote-contest-config.png) |
| Quote Contest mirrors | Greyed, non-editable pairs mirror activity start/end and update live as those change | ✅ spec (disabled, no hidden input, follow edits and clearing) |
| Chapter create (author) | Toggle "publié" off: publication pair appears; date picker refuses past days (`min`) | ✅ spec (`min` set; a day before it gives `rangeUnderflow`) |
| Chapter schedule round trip | Schedule a time, save, reopen edit: the same local time shows (no UTC shift); chapter published at that local moment | ✅ spec (21:37 Paris reopens 21:37; `publish_at` stored 20:37Z). Scheduled via the edit form of the draft chapter, not create. |
| Chapter validation error | Entered value redisplayed unshifted after an error (was the pre-existing shift of architecture §9) | ✅ fixed in phase 4, spec asserts today 00:01 comes back as 00:01. Found: redisplayed 02:01 (+2 h). Real cause: `old('publish_at')` is the local value as typed (the request flashes the original input), but the form fed it to the component, which expects UTC and converts it to local again. The form now converts `old('publish_at')` back to UTC with `old('timezone')`; the DB value was already UTC. |
| Mobile 375 px | Calendar form and chapter form: date + time fit or wrap, no horizontal overflow, both tappable | ✅ spec, no overflow, pairs side by side inside 375 px. Tight: on the calendar form the date text touches the picker icon (mobile-admin.png) — eyeball. |
| Keyboard / a11y | Tab reaches date then time; time input announces « Heure »; mirrors are not focusable as editable | ✅ spec: Tab from date reaches time (after the date's segments); time named « Heure »; mirrors disabled. Group has no accessible name (known, open item): date input is named by its `<label for>`. |
| Dark theme | Both inputs legible (picker icons visible) on Calendar and chapter forms | ✅ spec asserts text colour differs from background and `color-scheme: dark`; dark-admin.png / dark-author.png opened, legible, icons visible. Dark applied by setting `data-appearance` on `<html>`. |
| Out of scope surfaces | Auth activation codes and Events log filters still single `datetime-local`, behaving as before | ✅ spec (single `datetime-local`, no `type=time` on either page) |

## Open items

- **Hidden input id and `mirrorOf` (phase 2).** Architecture §4.1 says
  `mirrorOf` is "the id of another component's hidden input". This plan fixes
  the convention: the hidden input's id is `{id}_value`, and callers pass the
  source **component's** id (`mirror-of="active_ends_at"`). If BUILD prefers the
  literal reading, the Quote Contest call site changes accordingly — no other
  impact.
- **Group label (phase 2).** Architecture §4.1 labels the `role="group"` "by the
  field label's id when given", but `<x-shared::input-label>` renders no id and
  call sites are meant to stay unedited. The plan therefore ships the group
  without `aria-labelledby`; the date input is labelled by the existing
  `<label for>`, the time input by « Heure » only. VERIFY's a11y row checks
  whether that is acceptable; adding a label id to every call site would be a
  follow-up decision for the user.
- **Story `min` (phase 3).** Today's `:min` is computed client-side as
  `now + 1 min` in **UTC** (a pre-existing quirk). The plan replaces it with a
  server-side `now()->toDateString()` on the date input only; the time is no
  longer bounded client-side, and around midnight the server date may differ
  from the user's local date by one day. `after:now` server validation is the
  real guard and is unchanged. Surface if VERIFY finds it confusing.
- **`publishAtLocal` (phase 3).** Verified at PLAN: only referenced by its
  declaration (form.blade.php l.81) and the raw input's `x-model` (l.110), so it
  goes with the raw input.
