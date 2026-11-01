# Label the chapter form publish_at field — context

**Complexity:** 1 /5 · **Mode:** auto

## 0. Handoff

### REFINE → BUILD (2026-11-01)

Fast path (rated 1): §1–§3 written by the refiner. No question asked. The key
`story::chapters.form.publish_at.label` ("Publication planifiée") already exists
and is unused: reuse it. Likeliest reversal: label is visually hidden (`sr-only`)
rather than visible, to keep the inline toggle + picker row unchanged.

### BUILD phase 1 → WRAP (2026-11-01)

No plan deviation. `input-label` auto-gives the label `id="publish_at_label"`, which the datetime component's `aria-labelledby` already pointed at (so group and time input are named). Visual QA of the sr-only row not done (no browser).

### VERIFY → WRAP (2026-11-01)

PASS. Driven on the e2e instance as author@e2e.test (`histoire-e2e-1` chapter create), no spec written (markup-only, covered by the feature test). Nothing flaky or manual; nothing to fold beyond the record.

## 1. Functional spec

### 1.1 Overview

On the chapter create/edit form, the scheduled-publication date/time field has no
accessible name: screen readers announce the group unnamed and the time input as
« Heure ». Give it the name « Publication planifiée ». No visual change.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Publication planifiée | The date/time at which an unpublished chapter goes live. |

### 1.3 Roles & visibility

Unchanged: only authors/co-authors see the form; the field shows when « Publié » is off.

### 1.4 Functional requirements

1. The `publish_at` field is associated with a label « Publication planifiée »
   (`for="publish_at"`), visually hidden because the toggle row already conveys context.
2. The datetime component derives its group and time names from that label.

### 1.5 Lifecycle

N/A — markup only.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — unchanged |
| Visibility / privacy | N/A |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A |
| i18n | Reuse existing French key `story::chapters.form.publish_at.label` |
| Mobile | No layout change (sr-only) |
| Accessibility | The point of the task |

### 1.7 Out of scope

Visible label, restyling the row, other datetime fields (Calendar forms already labelled).

### 1.8 Open questions

None.

## 2. Architecture

### 2.1 Domain placement

Story, one Blade partial. No new key, no PHP, no deptrac edge.

### 2.2–2.5

N/A. The label is `<x-shared::input-label for="publish_at" class="sr-only">` placed
before the `<x-shared::datetime-local-input>` in
`app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php`
(inside the `x-show="!published"` row, so hidden together with the field).

### 2.6 Testing strategy

One integration test in the chapter create/edit feature tests: the rendered form
contains a `<label ... for="publish_at">` with « Publication planifiée ». Check
how the component derives names (read it) before asserting extra attributes.

### 2.7 Tradeoffs locked

| # | Question | Options | Chosen | Why |
|---|----------|---------|--------|-----|
| 1 | Visible vs hidden label | visible / sr-only | sr-only | Inline row stays unchanged; flip if the user wants it shown |

### 2.8 Risks acknowledged

The `<span>` icon and flex layout: an `sr-only` label is absolutely positioned, so
it must not disturb the `gap-2` flex row (verify visually).

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Add sr-only label + test | S | — | DONE |

### Phase 1 — Add the label

**Goal.** Name the publish_at field. **Reads.** §2.6.

**Deliverables.** Edit `chapters/partials/form.blade.php`; test in
`app/Domains/Story/Tests/Feature/Chapters/CreateChapterTest.php`.

**Tests.** `create form labels the scheduled publication field` (fails first).

**Acceptance.**
- ✅ Label present with `for="publish_at"` and the French text.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter create/edit form, « Publié » off | Row looks identical; field named « Publication planifiée » in the accessibility tree | ✅ row box 722x38.69 at same position before/after (HEAD~1 vs HEAD); group and both textboxes named « Publication planifiée » |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Label is sr-only, not visible | REFINE | Yes |
| 2 | Reuse existing key `form.publish_at.label` | REFINE | Yes |

## 5. Build & verify log

- Phase 1 DONE (2026-11-01): sr-only label added in `chapters/partials/form.blade.php`; test `create form labels the scheduled publication field` in `CreateChapterTest.php`. Visual QA (§3.2) not run.
- VERIFY PASS (2026-11-01): toggle off reveals the row; bounding box identical to pre-change; ariaSnapshot shows group « Publication planifiée » with its textboxes named; screenshot opened, row renders normally.
