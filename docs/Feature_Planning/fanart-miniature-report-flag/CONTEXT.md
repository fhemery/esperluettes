# Report flag on fanart miniature — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).

**Complexity:** 2 /5 · **Mode:** auto

## 0. Handoff

### BUILD phase 1 → VERIFY (2026-10-26)

No deviation from the plan. Still unchecked in a browser: modal clipping in profile-tab groups, flag tap size on mobile, flag click not opening the viewer.

### REFINE (+ fast-path DESIGN+PLAN) → BUILD (2026-10-26)

No blocking question (auto mode, none needed). Riskiest point: the report modal is
loaded into a container inside the thumbnail wrapper; check in VERIFY it is not
clipped by `overflow-hidden`/`x-show` ancestors (profile tab groups) and that a
click on the flag never opens the viewer. Assumption likeliest to be reversed: no flag
on the reporter's own fanart and none on audio rows of the profile tab (see §4).
Verified in code: viewer fragment uses `canReport = $fanart->user_id !== $userId`
and `<x-moderation::report-button topic-key="fanart" :entity-id :compact size="sm">`;
`x-fanart::thumbnail` is used by `fragments/group`, `fragments/tab`, `fragments/profile-group`.

## 1. Functional spec

### 1.1 Overview

A small flag button sits in the lower right corner of every fanart miniature
(story Fanarts tab, chapter images, profile Fanarts tab). Clicking it opens the
existing report form directly, without opening the fanart full screen. Value:
reporting straight from the grid.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Miniature | The 160x160 clickable thumbnail of a fanart (`x-fanart::thumbnail`) |
| Drapeau | The flag icon button that opens the report form |

### 1.3 Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Nothing (fanarts are confirmed-only) | — |
| `user` (non-confirmed) | Nothing (fanart feature hidden) | — |
| `user-confirmed` | Flag on every miniature except their own fanart | Report, same form/rules as in the viewer |
| Author / co-author of the story | Same as confirmed | Same (reporting a pending fanart declines it, existing rule) |
| Moderator / Admin | Same as confirmed | Same |

### 1.4 Functional requirements

1. A confirmed viewer sees a flag icon over the lower right corner of each
   miniature of a fanart they did not submit.
2. Clicking the flag opens the existing report modal for topic `fanart` and that
   fanart's id. It does not open the viewer.
3. Submitting follows the unchanged existing report flow (reason, snapshot, moderation).
4. The flag is reachable by keyboard and has an accessible label « Signaler ».

### 1.5 Lifecycle

No data of its own. Follows the miniature: if the fanart is removed or no longer
listed, the flag goes with it. Deactivated/deleted users: nothing new.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Miniatures are only rendered for confirmed viewers; flag hidden on own fanart (same rule as the viewer's `canReport`) |
| Visibility / privacy | Unchanged |
| Settings | N/A |
| Notifications | N/A — unchanged (existing report flow) |
| Domain events | N/A — unchanged `Moderation.ReportSubmitted` |
| Statistics | N/A |
| Moderation | Reuses topic `fanart` and its reasons |
| Lifecycle / cascade | N/A — no data |
| Media | N/A |
| Search | N/A |
| i18n | Reuses `moderation::report.button` label (French) |
| Mobile | Flag always visible (no hover reveal), touch-sized, does not shrink the miniature's tap target below usability |
| Accessibility | Real button, `title`/label, focus ring |

### 1.7 Out of scope

- Audio rows (title + player) on the profile tab, and the chapter audio player (it already has its own report button).
- Moderator delete / review actions on the miniature.
- Any change to the report form, reasons or moderation flow.
- Flag on the fanart creation/edit pages.

### 1.8 Open questions

None blocking.

## 2. Architecture

### 2.1 Domain placement

Fanart only; one Blade component change. Moderation's `<x-moderation::report-button>` is reused as-is (already consumed by Fanart's viewer, so no new coupling).

### 2.2 Data model

None.

### 2.3 PHP architecture

None. Existing `POST /moderation/report` is unchanged.

### 2.4 Frontend

`components/thumbnail.blade.php` becomes a `relative` wrapper `div` (keeps `w-40 h-40`) containing the existing thumbnail button (still `overflow-hidden`, now filling the wrapper) and, when the viewer is authenticated and `$fanart->user_id !== auth()->id()`, an `absolute bottom-1 right-1` `<x-moderation::report-button topic-key="fanart" :entity-id="$fanart->id" :compact="true" size="sm" />` placed as a sibling of (not inside) the button, so no nested interactive elements and the modal is not clipped by the button's `overflow-hidden`. The wrapper must not be inside any `@click` ancestor (checked: group/tab/profile-group have none around thumbnails).

### 2.5 Deptrac

No new edge (Fanart already uses Moderation views in the viewer).

### 2.6 Testing strategy

Pest feature tests on the rendered fragments (existing `FanartFragmentsTest` style): flag markup `reportButton` present for another user's fanart, absent for own fanart. Modal clipping and click-not-opening-viewer are VERIFY-only.

### 2.7 Tradeoffs locked

| # | Question | Options considered | Chosen | Why / what would flip it |
|---|----------|--------------------|--------|--------------------------|
| 1 | Where the flag lives | Inside the button / sibling in wrapper | Sibling in wrapper | Avoids nested buttons and clipping |
| 2 | Own fanart | Show anyway / hide | Hide | Mirrors viewer `canReport` |

### 2.8 Risks acknowledged

`report-button` renders a modal container inside the wrapper; if it clips in some context (profile tab `x-show`), revisit by teleporting.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Flag on thumbnail | S | — | DONE |

Working agreement: one phase = one commit; failing test first.

### Phase 1 — Flag on thumbnail

**Goal.** Show the report flag on miniatures of other users' fanarts. **Reads.** §2.4, §2.6.

**Deliverables.**
- Modify `app/Domains/Fanart/Private/Resources/views/components/thumbnail.blade.php` as in §2.4.
- Add a line to `app/Domains/Fanart/AGENTS.md` / `README.md` only if they describe the thumbnail (check).

**Tests.** In `app/Domains/Fanart/Tests/Feature/FanartFragmentsTest.php`:
- `it shows a report flag on a miniature of another user's fanart`
- `it hides the report flag on the viewer's own fanart miniature`

**Acceptance.**
- Tests above pass (written first, failing).
- Thumbnail click still dispatches `fanart-open`; flag is not nested in the thumbnail button.
- `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Story Fanarts tab | Flag bottom-right on others' fanart; click opens report modal, not viewer | |
| Chapter images | Same | |
| Profile Fanarts tab (other's profile / own) | Flag on others'; none on own; modal not clipped in collapsed/expanded groups | |
| Mobile width | Flag visible and tappable | |
| Keyboard | Tab reaches flag, Enter opens form | |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Flag hidden on the viewer's own fanart (mirrors viewer `canReport`) | REFINE | Yes |
| 2 | Audio rows on the profile tab (no miniature) and chapter audio get no new flag | REFINE | Yes |
| 3 | Flag always visible, not hover-only (mobile) | REFINE | Yes |
| 4 | Flag also shown on pending fanarts (same as viewer) | REFINE | Yes |

## 5. Build & verify log

- **BUILD phase 1 (2026-10-26) — DONE.** `components/thumbnail.blade.php` now a `relative w-40 h-40` wrapper; button fills it; `@auth` + `user_id !== auth()->id()` gate a sibling `absolute bottom-1 right-1` `<x-moderation::report-button>`. Two tests added to `FanartFragmentsTest` (failed first). `pnpm run gate` green. Fanart README/AGENTS needed no change. Visual QA (§3.2: modal clipping, click not opening viewer) still for VERIFY.
