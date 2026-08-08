# News — pin to carousel white screen — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Fix a UI bug on the News admin edit form: clicking the « Épingler dans le
carousel » toggle makes the page jump so the form leaves the viewport (looks
like a white screen). After the fix, toggling the pin control must keep the
form visible and usable. Behaviour of pin itself (save on submit, carousel
membership) is unchanged.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Épingler dans le carousel | Existing admin form toggle that marks an actualité for the homepage carousel |
| Carousel | Homepage strip of pinned, published news |

## 3. Roles & visibility

Unchanged from current News admin (see `news-moderator-access`):

| Role | Can see | Can do |
|------|---------|--------|
| Guest | — | — |
| `user` / `user-confirmed` | — | — |
| Moderator / Admin / tech-admin | News edit form, including the pin toggle | Toggle pin; save via Enregistrer |

## 4. Functional requirements

### 4.1 Toggle pin without losing the form

1. An eligible admin opens an existing actualité edit page.
2. They click (or activate via keyboard) « Épingler dans le carousel ».
3. The toggle visual state flips (on ↔ off).
4. The edit form, including the toggle and surrounding fields, remains in view —
   no blank/white viewport caused by the click alone.
5. Pin is still only persisted when the user submits the form (Enregistrer), as
   today.

### 4.2 Edge paths

- Keyboard activation of the control must not trigger the same scroll-away
  failure.
- Create form (if it exposes the same control) must behave the same as edit.
- No change to validation, redirects, or flash messages after save.

## 5. Lifecycle

N/A — no new data. Existing pin / `display_order` / observer behaviour on save
is unchanged.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — no change; same News admin roles |
| Visibility / privacy | N/A — admin-only surface, unchanged |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A — pin/carousel rights already include moderators |
| Lifecycle / cascade | N/A — no new entities |
| Media | N/A |
| Search | N/A |
| i18n | Keep existing French label; no new strings expected |
| Mobile | Toggle must remain usable on narrow admin viewports without scrolling away |
| Accessibility | Control must remain operable by keyboard and keep focus without jumping the scrollable admin pane off-screen |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| — | (auto mode — none asked) | See assumptions in `DECISIONS.md` |

## 8. Out of scope

- Changing how pin is saved (AJAX pin endpoint, using `NewsService::pin()`).
- Carousel reorder page (`admin/news/pinned`).
- Putting a newly pinned item first in the carousel order (that is
  [`news-pin-carousel-first`](../_done/news-pin-carousel-first.md)).
- Redesigning the Shared toggle component beyond what is needed to stop the
  scroll-away (if the bug is in Shared and fixing it there is the right place,
  that is allowed — cosmetic redesign is not).
- Public carousel rendering.

## 9. Open questions

None blocking. Cause is assumed to be focus+scroll on a clipped checkbox inside
the admin layout’s overflow pane; DESIGN confirms the fix locus.
