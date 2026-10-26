# Collapse Toute l'oeuvre fanart area by default — context

**Complexity:** 1 /5 · **Mode:** auto

## 0. Handoff

### DESIGN+PLAN (written by the refiner, fast path) → BUILD (2026-10-26)

### BUILD phase 1 → VERIFY (2026-10-26)

- Shipped as planned; nothing left out. The profile tab was not touched.

### VERIFY → WRAP (2026-10-26)

- PASS. New temporary spec `e2e/tests/features/fanart-toute-loeuvre-collapsed.spec.ts` (delete at WRAP; do not promote). Stale expectations in `fanart-artwork-module.spec.ts` (work group assumed open) updated to click « Montrer tout » first; that older spec is also still in `features/` and is due for deletion at its own WRAP.
- Nothing flaky or manual.

### (earlier) DESIGN+PLAN notes

- 0 blocking questions asked (auto mode).
- Likeliest to be reversed: A2 (profile tab left untouched) and A3 (no spoiler notice on the « Toute l'œuvre » group).
- Existing tests assert `data-group-key="work" data-collapsed="false"` in `FanartFragmentsTest` (~l.69) and `FanartProfileTabTest` (~l.154, profile tab: stays `false`). Only the first changes. The reader spoiler-notice count (`toBe(1)`) in `FanartFragmentsTest` stays 1 because of A3.

## 1. Functional spec

### 1.1 Overview
On the story's Fanarts tab, the « Toute l'œuvre » group (fanart not tied to a chapter) starts collapsed, like unread chapter groups. The viewer expands it with the existing « Tout afficher » button.

### 1.2 Vocabulary
| Term | Meaning |
|------|---------|
| Toute l'œuvre | The `work` group of the story Fanarts tab: fanart with no chapter |

### 1.3 Roles & visibility
Unchanged. Applies to every viewer who sees the tab, authors included (the group is not read-state dependent). Collapsed content is still lazily fetched on first expansion.

### 1.4 Functional requirements
1. Loading the Fanarts tab: the « Toute l'œuvre » group is closed, button reads « Tout afficher », nothing fetched yet.
2. Click: expands and loads the group as chapter groups already do; click again collapses.
3. Chapter groups behave as before.

### 1.5 Lifecycle
N/A — presentation only.

### 1.6 Cross-cutting concerns
| Concern | Decision |
|---------|----------|
| Roles | N/A — same for all viewers |
| Visibility / privacy | Unchanged |
| Settings | None (no per-user toggle) |
| Notifications / events / statistics / moderation / lifecycle / media / search | N/A |
| i18n | No new string |
| Mobile | Same collapse control |
| Accessibility | Existing `aria-expanded` button |

### 1.7 Out of scope
Profile-tab fanart groups; a user setting; changing chapter-group collapse rules; remembering open state.

### 1.8 Open questions
None blocking.

## 2. Architecture

Fanart domain only, no new edge. `FanartService::tabSummary()` returns the `work` group with `'collapsed' => true` (was `false`); `tab.blade.php` already derives `data-collapsed`, initial `open` and lazy load from `collapsed`. The spoiler notice in `tab.blade.php` is shown only when `collapsed` and the group key is not `work` (A3). Profile tab (`profileTab`/`profile-group.blade.php`) untouched.

Tests: update the existing assertion; add one for the notice absence. Visual: VERIFY can skip (covered by feature tests; Alpine path unchanged).

## 3. Plan

### 3.1 Phase index
| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Collapse the work group on the story tab | S | — | DONE |

### Phase 1 — Collapse the work group

**Goal.** « Toute l'œuvre » starts collapsed on the story Fanarts tab. **Reads.** §2.

**Deliverables.**
- `app/Domains/Fanart/Private/Services/FanartService.php` (`tabSummary`, `'work'` entry: `collapsed => true`; adjust docblock if needed).
- `app/Domains/Fanart/Private/Resources/views/fragments/tab.blade.php` (`@if ($group['collapsed'] && $group['key'] !== 'work')` around the spoiler notice).

**Tests.** In `app/Domains/Fanart/Tests/Feature/FanartFragmentsTest.php`: change `work` expectation to `data-collapsed="true"` in the reader test; add `it('collapses Toute l\'œuvre by default for every viewer without a spoiler notice')` covering a reader and the story author (work group `true`, spoiler notice count stays at chapter groups only). `FanartProfileTabTest` unchanged.

**Acceptance.**
- ✅ Tab HTML has `data-group-key="work" data-collapsed="true"` for reader and author.
- ✅ No spoiler notice for the work group.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist
| Surface | Check | OK? |
|---------|-------|-----|
| Story Fanarts tab | « Toute l'œuvre » closed on load; click loads and shows thumbnails | ✅ (confirmed + author, no spoiler notice, collapses again) |
| Other groups / profile tab | unchanged | ✅ chapter group spec still green; profile tab covered by `FanartProfileTabTest` (untouched code) |

### 3.3 Open items
None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-26 | REFINE | Collapse the work group on the story tab | Yes, as requested | |

### Assumptions made without asking
| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Applies to everyone, authors included | REFINE | Yes |
| A2 | "Fanart tab" means the story tab only; profile tab groups unchanged | REFINE | Yes |
| A3 | No spoiler notice on the work group (it is not read-gated) | REFINE | Yes |

## 5. Build & verify log

- BUILD phase 1 (2026-10-26): `tabSummary` work group `collapsed => true`; spoiler notice skipped for key `work`. Tests in `FanartFragmentsTest` (updated assertion + new author/reader test). Gate green. No deviation.
- VERIFY (2026-10-26): browser spec green for confirmed reader and author; PASS.
