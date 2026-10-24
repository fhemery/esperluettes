# Calendar: dedicated time field for activity dates — architecture

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Shared** owns it: the change is the existing `<x-shared::datetime-local-input>`
component plus pure helpers in `date-utils.js`. No new domain, no table, no
server code.

Reality check against the spec (§4.2 of the functional spec was corrected in
the same commit): only Calendar uses the shared component today. Auth codes and
the Events log use raw `datetime-local` inputs with **server-time** semantics
(no UTC conversion) and are out of scope (decision 5). Story's chapter form
uses a raw input with **local value + `timezone` field** semantics and is
brought in (decision 5).

### 1.1 Changes in other domains

- **Calendar** (Core form, Secret Gift, Quote Contest): consumers keep using the
  component; only the Quote Contest read-only mirror changes, from a raw
  `text-input` reading the source's DOM value to the component's mirror mode.
- **Story**: the chapter `publish_at` input becomes the component in
  local-submit mode; the `timezone` hidden field and server handling stay.

Direct component calls; no extension point.

## 2. Data model

None. Stored values, request keys and validation are unchanged.

## 3. PHP architecture

None. Request keys (`preview_starts_at`, `quote_contest[votes_start_at]`,
`publish_at`, …) and their formats (`Y-m-d\TH:i`) are exactly what the server
receives today, which is the contract the design protects.

## 4. Frontend architecture

### 4.1 Component contract

`<x-shared::datetime-local-input>` renders, inside one wrapper:

- a visible `<input type="date">` and a visible `<input type="time">` side by
  side (`flex`, wrapping to a column on narrow widths), **unnamed**;
- one `<input type="hidden">` carrying the original `name` and `id`'s value, in
  the combined `YYYY-MM-DDTHH:mm` shape.

The `id` attribute goes on the date input so the existing `<label for>` still
focuses the field; the time input gets `aria-label` from a new Shared lang key
(« Heure »), and the pair is wrapped in `role="group"` labelled by the field
label's id when given.

Props (existing ones kept): `name`, `id`, `value`, `disabled`, plus

| Prop | Default | Meaning |
|------|---------|---------|
| `submit` | `utc` | `utc`: hidden value converted local→UTC on form submit (today's behaviour). `local`: submitted as typed (Story, which sends `timezone` separately). |
| `min` | none | Lower bound for the **date** input (`YYYY-MM-DD`), used by Story. |
| `mirrorOf` | none | `id` of another component's hidden input; the field then shows that value read-only and follows its changes. Replaces the Quote Contest DOM hack. |

Behaviour (spec §4.1):

1. On load, the hidden `value` (server UTC for `submit=utc`) is converted with
   `utcToLocalInput` and split into date/time; both inputs filled. Empty value →
   both empty.
2. Date set while time empty → time set to `00:00`.
3. Date cleared → time cleared, hidden value empty.
4. Any edit recomputes the hidden value (`join`) and dispatches `input`, so
   mirrors and any listener keep working.
5. On form submit, hidden value converted `localToUtcInput` when `submit=utc`;
   untouched otherwise. Time without a date is treated as empty (nothing is
   sent), consistent with 3.
6. `step` is not set on the time input: minutes are free.

### 4.2 Helpers

Two pure functions added to `date-utils.js` (and its existing vitest file):
`splitLocalInput('YYYY-MM-DDTHH:mm') → {date, time}` and
`joinLocalInput(date, time) → string` (returns `''` when `date` is empty,
applying the `00:00` default when `time` is empty). The Alpine in the component
is thin glue over these, so the logic is unit-testable without a DOM.

### 4.3 Styling

Reuses the component's current class merge (`border-accent …`) for each input;
`class` passed by callers (`mt-1 block w-full`) applies to the wrapper's inputs
so call sites need no edit. Story's custom classes are passed the same way.

## 5. Deptrac

No new edge. Story already depends on Shared; Calendar already uses the
component.

## 6. Testing strategy

- **vitest**: `splitLocalInput` / `joinLocalInput` (empty, midnight default,
  DST-adjacent local strings), alongside the existing UTC helpers.
- **Feature tests**: Blade render tests asserting each consumer page emits a
  hidden input with the original `name` and the unchanged server-side value
  (create/edit activity, Secret Gift and Quote Contest config, chapter form),
  and that existing Calendar/Story save tests still pass untouched — they post
  the same keys.
- **Visual / e2e** (VERIFY): Alpine behaviour — prefill `00:00`, clear date
  clears time, UTC round-trip at submit, Quote Contest mirror following
  `active_ends_at`, Story `min` and `timezone`, phone-width fit,
  validation-error redisplay. `e2e/pages/AdminActivityFormPage.ts` fills these
  fields and must be updated to drive the two inputs.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 5 | Scope of the split | (A) Calendar + Quote Contest only; (B) + Story chapter; (C) + Auth codes and Events log | B | Story already has UTC-via-`timezone` semantics a `submit=local` mode covers cheaply; Auth/Events are server-time and would need a third mode or a behaviour change. |
| 6 | How the value is submitted | (A) hidden combined input under the original name; (B) two named inputs recombined server-side | A | Server contracts, `old()` and error keys stay identical; B contradicts "no server change". |
| A1 | Quote Contest mirror (assumption, not arbitrated) | (A) `mirrorOf` mode in the component; (B) keep raw text-input reading the source DOM | A | The raw mirror would keep a single `datetime-local` and break the split. |

## 8. File layout

No new files except the Shared lang key; component, `date-utils.js` and its test
are edited in place.

## 9. Risks acknowledged

- Story re-feeds `old('publish_at')` (the *local* value submitted) through the
  UTC→local conversion on a validation error, so the displayed time can shift.
  This is pre-existing; the new component preserves it rather than fixing it
  silently. Revisit if reported.
- Native `<input type="time">` rendering (12h/24h, picker or typed segments)
  follows the browser's locale; the spec accepts this.
- Auth codes and Events log keep a single `datetime-local`; the app will have
  both patterns until someone brings them over.
