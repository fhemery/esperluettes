# News — pin to carousel white screen — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Shared owns the fix.** The bug is in `<x-shared::toggle>`: its `sr-only`
checkbox is clipped off-layout; focusing it makes the browser scroll the nearest
`overflow-y-auto` ancestor (Administration’s `<main>`) until the clipped node is
“in view”, which empties the viewport. The News pin control is only one
consumer; the same component is used across admin forms (FAQ, Calendar,
StoryRef, Moderation, Story chapter publish, Notification settings).

News itself needs no behavioural change — pin still posts with the form.

### 1.1 Changes in other domains

- **Shared** — adjust the toggle markup/CSS so focus stays on the visible
  control geometry (see §4). No new public API.
- **News / Administration / others** — no required domain changes. VERIFY
  exercises the News edit form (the reported surface). Regression risk on other
  toggle consumers is mitigated by keeping the public props and form semantics
  identical.

## 2. Data model

N/A — no schema, models, or lifecycle changes.

## 3. PHP architecture

N/A — no controllers, services, routes, events, or auth changes. Pin continues
to ride `NewsRequest` + `NewsService::update` on form submit.

## 4. Frontend architecture

Keep `<x-shared::toggle>` as a pure Blade component (no Alpine, no JS).

**Focus geometry:** replace the clipped `sr-only` input with a checkbox that
occupies the same box as the visible track (label `relative`; input
`absolute` over the track, opacity 0 / or equivalent), so:

- click/keyboard focus lands where the user sees the switch;
- `peer-*` / `peer-checked` / `peer-focus` styling on the track still works;
- props (`name`, `checked`, `value`, `disabled`, colours, label) stay unchanged.

Do not change Administration layout scrolling as the primary fix — the layout is
correct; the control was asking the browser to scroll a non-visible node.

## 5. Deptrac

No new edges.

## 6. Testing strategy

| Level | What |
|-------|------|
| Feature (PHP) | Assert the toggle still renders a named checkbox that can be checked in form posts — prefer extending an existing Shared or News admin form test if one already submits `is_pinned` / a toggle; do not invent a heavy browser assertion in PHPUnit. |
| Vitest | N/A — no JS module. |
| VERIFY | Click « Épingler dans le carousel » on News edit: form stays in view, toggle flips; keyboard activation same. Spot-check one other admin toggle (e.g. FAQ) if cheap. |

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where to fix | A) Shared toggle · B) News-only CSS/workaround · C) Change Administration `overflow-y-auto` | A | Same bug latent on every admin toggle; layout scroll is legitimate |
| 2 | Fix technique | A) Overlay/positioned checkbox matching the track · B) JS preventDefault on focus · C) `scroll-margin` hacks | A | No JS; keeps peer styles; standard custom-control pattern |
| 3 | Scope of Shared change | A) Markup/CSS only, same props · B) Redesign toggle API | A | Bugfix; out of scope forbids cosmetic redesign |

## 8. File layout

No new classes. Edit lands in the existing Shared Blade component
`Private/Resources/views/components/toggle.blade.php` (and a test under Shared
or News Feature tests as PLAN decides).

## 9. Risks acknowledged

- Other screens that relied on accidental scroll-on-focus behaviour: none
  expected; if something breaks, props-compatible rollback of the toggle markup
  is cheap.
- If VERIFY still reproduces white screen after the Shared fix, re-open cause
  (assumption #1 in `DECISIONS.md`) — possible Quill/editor interaction rather
  than the checkbox.
