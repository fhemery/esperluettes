# Dashboard — story to discover: exclude already-read — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

The dashboard block « Histoires à découvrir » shows up to 7 random stories.
Today it can suggest stories the reader has already started. From now on it
skips every story in which the reader has marked at least one chapter as read,
so the block only proposes stories they have not begun.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Histoires à découvrir | Existing dashboard carousel of random stories, ending on the « Découvrir plus » placeholder slide |
| Histoire déjà lue | A story with at least one chapter the reader has marked as read: via the « lu » button, or automatically by posting a root comment on the chapter |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Nothing. The dashboard requires login | — |
| `user` (non-confirmed) | Public stories, minus already-read ones | — |
| `user-confirmed` | Public + community stories, minus already-read ones | — |
| Author / co-author of the target | Their own stories are already excluded (unchanged) | — |
| Moderator | Same as their reader role | — |
| Admin | Same as their reader role | — |

## 4. Functional requirements

### 4.1 Selecting stories to discover

1. A logged-in reader opens the dashboard.
2. The block selects up to 7 random stories with the existing rules:
   - visibility by role;
   - at least one published chapter;
   - not authored or co-authored by the reader;
   - no trigger-warned stories if the reader's « Histoires » preference hides them.
3. **New:** the selection also excludes every story where the reader has at
   least one chapter marked as read.
4. Stories in the reader's « pile à lire » with no chapter read are **not**
   excluded.
5. If fewer than 7 stories match, the block shows only those, followed by the
   usual « Découvrir plus » placeholder.
6. If no story matches, the block shows only the placeholder. There is no new
   empty-state text.

## 5. Lifecycle

- The rule is evaluated on the reading marks that exist at display time.
  Once the reader unmarks every chapter of a story, that story becomes eligible
  again.
- Chapter or story deletion and unpublication behave as today: the existing
  « at least one published chapter » and visibility rules still apply.
- A deactivated or deleted user cannot reach the dashboard, so N/A.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Same rule for `user` and `user-confirmed`; visibility per role unchanged |
| Visibility / privacy | No new data shown; only filters the reader's own suggestions |
| Settings | None. The filter always applies, with no opt-out (assumption A2) |
| Notifications | N/A — display-only change |
| Domain events | N/A — no state change |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | See §5 |
| Media | N/A |
| Search | N/A. Library and search are unchanged |
| i18n | No new strings |
| Mobile | Unchanged carousel; it simply may hold fewer slides |
| Accessibility | Unchanged |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Exclude unstarted stories that are in the « pile à lire »? | No. Only stories with ≥1 chapter read are excluded |
| 2 | Fewer than 7 unread stories match? | Show fewer, placeholder still last; only the placeholder if none |

## 8. Out of scope

- Filtering already-read stories out of the library, search or any other surface.
- A user setting to turn the filter off.
- Topping the carousel up with read stories.
- Any change to the block's wording, placeholder or layout.
- Tracking « read » for guests or on chapter view.

## 9. Open questions

None.
