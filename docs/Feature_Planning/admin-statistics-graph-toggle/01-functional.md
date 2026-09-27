# Admin statistics — cumulative / non-cumulative graph toggle — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

The admin statistics page (`/admin/statistics`) only shows cumulative curves,
which hide the recent trend: a slowing community still draws a rising line.
This feature adds a page-level switch that redraws every graph as **weekly
bars** (net change per calendar week), so admins can see at a glance whether
activity is growing or shrinking. Cumulative stays the default.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Mode *Cumulé* | Today's view: running total over time (line). Default. |
| Mode *Par semaine* | One bar per calendar week (Monday → Sunday), value = net change during that week. |
| *Semaine en cours* | The current, not yet finished week — last bar in *Par semaine* mode. |

## 3. Roles & visibility

The page's access rule is unchanged: `admin` and `tech-admin` only.

| Role | Can see | Can do |
|------|---------|--------|
| Guest | — | — |
| `user` (non-confirmed) | — | — |
| `user-confirmed` | — | — |
| Author / co-author of the target | N/A | N/A |
| Moderator | — | — |
| Admin / tech-admin | The statistics page and the switch | Toggle the mode |

## 4. Functional requirements

### 4.1 Switching mode

1. Above the tabs of `/admin/statistics`, a two-position switch shows
   **Cumulé | Par semaine**. On a first visit, *Cumulé* is selected and the page
   looks exactly as today.
2. Selecting *Par semaine* redraws **every graph of the page, on every tab**
   (Utilisateurs, Contenus, Commentaires) — including tabs not currently
   visible, so opening another tab shows it already in the chosen mode.
3. Selecting *Cumulé* restores today's curves.
4. The number tiles at the top of the page are not affected by the switch.
5. The chosen mode is remembered **per browser**: the next visit from the same
   browser opens in the last chosen mode. Another browser or device starts on
   *Cumulé*.

### 4.2 Weekly mode rendering

1. Each graph becomes a **bar chart**, one bar per calendar week (Monday →
   Sunday, app timezone), from the week containing the first data point up to
   the current week.
2. A week without any activity shows a zero bar (no gap).
3. A bar's value is the **net** change for the week. It can be **negative**
   (deletions, words removed on edit); the axis extends below zero when needed.
   The sum of the bars equals the cumulative total.
4. The **current week** bar is visually distinguished (lighter / hatched) and
   labelled *semaine en cours*, so an incomplete week is not read as a drop.
5. The *racines et réponses* comments graph becomes **stacked bars**
   (roots + replies); the bar height is the week's total comments, consistent
   with the *Commentaires* graph.
6. Tooltip wording: *Semaine du 15 sept. 2026 : 37* (per series on the stacked
   chart).

### 4.3 Edge paths

- No data at all for a metric: the graph shows its existing empty state in
  both modes.
- Stored preference unreadable or blocked by the browser: the page falls back
  to *Cumulé* silently.

## 5. Lifecycle

N/A — no data is created. The mode preference is a per-browser display
setting; clearing browser data resets it to *Cumulé*.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Unchanged: admin + tech-admin (existing route guard). |
| Visibility / privacy | N/A — aggregate counters only, admin-only page. |
| Settings | N/A — not a user setting; remembered in the browser only. |
| Notifications | N/A — display-only change. |
| Domain events | N/A — no state change. |
| Statistics | This *is* the statistics surface; no new metric, existing daily data reused. |
| Moderation | N/A. |
| Lifecycle / cascade | N/A — see §5. |
| Media | N/A. |
| Search | N/A. |
| i18n | French labels: *Cumulé*, *Par semaine*, *semaine en cours*, tooltip *Semaine du {date} : {valeur}*. |
| Mobile | Existing admin layout; no specific mobile work. |
| Accessibility | The switch is a keyboard-operable button group exposing its selected state. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | What does one point represent in non-cumulative mode? | Calendar weeks (Monday → Sunday). A finer range/grain control comes later. |
| 2 | Where does the toggle live? | One switch for the whole page, applies to all graphs. |
| 3 | Is the mode remembered? | Per browser; default *Cumulé* on first visit. |
| 4 | How is the current, partial week shown? | Shown, visually marked, labelled *semaine en cours*. |
| 5 | Chart form in weekly mode? | Bars. |
| 6 | Roots / replies chart in weekly mode? | Stacked bars. |
| 7 | Negative weekly values? | Net value, negatives allowed. |

## 8. Out of scope

- A time-range selector (e.g. *12 derniers mois* by default, *Depuis le début*
  to see everything) — planned as a follow-up, not in this task.
- Any other granularity (day, month) or an admin-chosen granularity.
- Per-graph toggles.
- Remembering the mode server-side / across devices.
- Changing the number tiles.
- Statistics outside the admin page (profile statistics is a separate task).

## 9. Open questions

None.
