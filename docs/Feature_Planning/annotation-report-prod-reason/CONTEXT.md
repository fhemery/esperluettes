# Seed the « Autre » reason for the annotation report topic in production — context

**Complexity:** 2 /5 · **Mode:** interactive

## 0. Handoff

### REFINE → BUILD (2026-11-01)

Fast path: §1–§3 written by the refiner. Zero blocking questions asked.
- Most likely reversed: choosing a data migration over an ops/release-note step.
  Reason: release notes are generated from commits and have no ops-action slot,
  so a manual step is exactly what gets forgotten (that is the bug).
- Trap: the migration also runs in every test DB. It only inserts when the
  `annotation` topic has no reason at all, so tests calling `defaultReason()`
  (label `Other`) or `createReason()` are unaffected, but run the Moderation and
  Comment annotation suites to confirm.
- Not in scope: the `fanart` topic has the same dev-seeder-only situation; flag
  it in the final summary, do not fix here.

### BUILD phase 1 → WRAP (2026-11-01)

No deviation. `fanart` topic still has the dev-seeder-only gap (not fixed, out of scope).

## 1. Functional spec

### 1.1 Overview

The `annotation` moderation topic must have at least one reason in production,
« Autre », otherwise the report modal opened from « Signaler » on an annotation
has nothing to select and no report can be filed. Today the reason only exists
through the dev `ModerationSeeder`.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Raison de signalement | A `moderation_reasons` row (topic key, label, sort order, active flag). |
| Sujet `annotation` | The moderation topic used when reporting a chapter annotation. |

### 1.3 Roles & visibility

No change. Confirmed users see active reasons of the topic in the report modal;
moderators/admins manage reasons in the existing reasons admin.

### 1.4 Functional requirements

1. After deploy, the `annotation` topic has an active reason labelled « Autre ».
2. If the topic already has any reason (admin created some, or « Autre » already
   exists), nothing is inserted or changed.
3. Rolling the change back must not delete reasons (reports may reference them).

### 1.5 Lifecycle

Once inserted, the reason is a normal admin-managed row: it can be renamed,
deactivated or deleted by moderators. The migration never re-creates it.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — no behaviour change. |
| Visibility / privacy | N/A. |
| Settings | N/A. |
| Notifications | N/A. |
| Domain events | N/A. |
| Statistics | N/A. |
| Moderation | This is the moderation data itself; see §1.4. |
| Lifecycle / cascade | §1.5. |
| Media | N/A. |
| Search | N/A. |
| i18n | Label is plain French text « Autre » (the column holds plain text, the admin UI is French). |
| Mobile | N/A. |
| Accessibility | N/A. |

### 1.7 Out of scope

- Any reason beyond « Autre ».
- Other topics (`fanart` has the same dev-seeder-only gap; separate task if wanted).
- Changing the dev seeder's `Other` label.

### 1.8 Open questions

None.

## 2. Architecture

### 2.1 Domain placement

Moderation domain only. One new data migration in
`app/Domains/Moderation/Database/Migrations/`.

### 2.2 Data model

No schema change. Migration `2026_11_01_000001_seed_annotation_other_reason.php`:
`up()` inserts one `moderation_reasons` row
(`topic_key=annotation`, `label=Autre`, `sort_order=0`, `is_active=true`,
timestamps) via `DB::table`, only if no row exists for `topic_key=annotation`.
`down()` is a deliberate no-op (comment explains: reports may reference the row).

### 2.3 PHP architecture

None beyond the migration. `DB::table` rather than the model, so the migration
does not depend on a model that may change.

### 2.4 Frontend

None.

### 2.5 Deptrac

No new edge.

### 2.6 Testing strategy

Pest feature test in `app/Domains/Moderation/Tests/Feature/` that `require`s the
migration file and runs `up()`: (a) empty topic gets one active « Autre »;
(b) topic with an existing reason is left untouched; (c) second run does not
duplicate. Run Moderation + Comment annotation suites to check no pollution.

### 2.7 Tradeoffs locked

| # | Question | Options considered | Chosen | Why / what would flip it |
|---|----------|--------------------|--------|--------------------------|
| 1 | Migration or ops step | Data migration; release-note action | Data migration | Ops steps get forgotten, which caused the bug. Flips if the team wants reasons strictly admin-owned. |
| 2 | Guard | Skip if « Autre » exists; skip if topic has any reason | Topic has any reason | Respects a moderator who already created their own set. |

### 2.8 Risks acknowledged

Test DBs get an extra `annotation` « Autre » row; revisit if a test counts
reasons for that topic.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Data migration + test | S | — | DONE |

Working agreement: one phase = one commit; failing test first.

### Phase 1 — Seed « Autre » for the annotation topic

**Goal.** Production gets the reason on deploy. **Reads.** §2.2, §2.6.

**Deliverables.** The migration in §2.2; the test in §2.6
(`SeedAnnotationOtherReasonMigrationTest.php`).

**Tests.** `seeds Autre when the annotation topic has no reason`,
`leaves existing reasons untouched`, `is idempotent`.

**Acceptance.**
- ✅ Three tests pass; Moderation and Comment annotation suites still pass.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Annotation « Signaler » modal (fresh migrated DB) | « Autre » is listed and selectable | |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Data migration rather than a release-note/ops step. | REFINE | Yes — delete the migration, add an ops note. |
| 2 | Insert only when the topic has no reason at all. | REFINE | Yes. |
| 3 | Label « Autre » (French, per request), not the seeder's `Other`. | REFINE | Yes. |
| 4 | `down()` is a no-op. | REFINE | Yes. |

## 5. Build & verify log

- Phase 1 DONE: migration + 3 tests; Moderation and Comment suites pass; gate green.
- VERIFY (2026-11-01): checklist row is data-only (the modal renders `moderation_reasons` rows, already covered by `core/chapter-annotation-report.spec.ts`, which selects a reason in `#reason_id`). Ran `migrate` on the dev DB: the `annotation` topic now has one active « Autre » (id 3, sort 0). Modal not driven in the browser (no annotated chapter in dev data; cost not justified). PASS.
