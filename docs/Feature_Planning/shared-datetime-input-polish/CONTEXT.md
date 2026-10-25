# Shared — datetime input polish — context

**Complexity:** 2 /5 · **Mode:** auto

## 0. Handoff

### REFINE (+ fast-path DESIGN+PLAN, code already applied) → BUILD/VERIFY (2026-10-25)

Zero questions asked. The fix was small enough that REFINE applied it and its test
(phase 1 is effectively built; BUILD only has to confirm the gate and VERIFY the
visual checklist). Likeliest to be reversed: min-widths (10rem / 7rem) — tune at
375 px. Trap: label ids are `{for}_label`; a caller whose label lacks `for`, or a
datetime input without `id`, gets no group name (dangling reference is skipped).

## 1. Functional spec

### 1.1 Overview
Every date + time field announces which field it belongs to to screen readers, and
on a 375 px phone the date text no longer touches the native picker icon.

### 1.2 Vocabulary
N/A — no new nouns.

### 1.3 Roles & visibility
Same for every role: component-level change, no visibility rule.

### 1.4 Functional requirements
1. The `role="group"` wrapper is named by the caller's label (`aria-labelledby`).
2. The time input is announced as « <label> Heure » (label then its own « Heure »).
3. The date input keeps a minimum width; when the pair no longer fits, the time input wraps below.
4. No call-site edit; submitted values unchanged.

### 1.5 Lifecycle
N/A.

### 1.6 Cross-cutting concerns
| Concern | Decision |
|---------|----------|
| Roles, privacy, settings, notifications, events, statistics, moderation, lifecycle, media, search | N/A — presentational |
| i18n | Reuses existing « Heure » string |
| Mobile | Min widths + existing flex-wrap |
| Accessibility | The point of the task |

### 1.7 Out of scope
Auth activation-code and Events-log raw `datetime-local` filters.

### 1.8 Open questions
None.

## 2. Architecture

Shared only, no new deptrac edge.
- `input-label` stamps `id="{for}_label"` when given `for` and no `id` (affects all labels with `for`; harmless, ids unique per field).
- `datetime-local-input` (when `id` set): group `aria-labelledby="{id}_label"`; time input `id="{id}_time"` and `aria-labelledby="{id}_label {id}_time"` (self-reference uses its `aria-label` « Heure »); date input `min-w-[10rem]`, time `min-w-[7rem]`, `min-w-0` dropped so `flex-wrap` triggers earlier.
- Tests: Blade render test in `DatetimeLocalInputTest`; layout/AT only in VERIFY.

## 3. Plan

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Label id + group/time naming + min widths | S | — | DONE (code+test; gate pending BUILD confirmation) |

### Phase 1
**Deliverables.** `input-label.blade.php`, `datetime-local-input.blade.php`, `DatetimeLocalInputTest.php`.
**Acceptance.** ✅ new test passes. ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist
| Surface | Check | OK? |
|---------|-------|-----|
| Calendar admin activity form, 375 px | Date text clear of the picker icon; no horizontal overflow; time wraps below if tight | |
| Same, desktop | Date and time side by side | |
| Accessibility tree (devtools) | Group name = « Début de l'activité »; time input name = « Début de l'activité Heure »; « Fin » distinct | |
| Story chapter form, Secret Gift, Quote Contest config | Same naming; mirror fields still read-only | |
| Other labels with `for` | Rendering unchanged | |

### 3.3 Open items
None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-25 | REFINE | Accessible name route | `input-label` id option (group `aria-labelledby` + time `aria-labelledby`), not building aria-label from label text | |
| 2 | 2026-10-25 | REFINE | 375 px | Min widths on inputs (earlier wrap), not date-only min-width | |

### Assumptions made without asking
| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Giving every `for`-bound label an id is acceptable | REFINE | Yes |
| 2 | 10rem / 7rem minimums are enough for the icon gap | REFINE | Yes, tune at VERIFY |

## 5. Build & verify log

_Not written yet._
