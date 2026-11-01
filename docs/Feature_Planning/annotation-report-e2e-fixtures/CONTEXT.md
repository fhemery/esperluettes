# Chapter annotations — e2e fixtures for reaction and co-author reply reports — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).

**Complexity:** 2 /5 · **Mode:** interactive

## 0. Handoff

### REFINE → BUILD (2026-11-01)

Fast path: §1, §2, §3 written together; no blocking question asked. Test-support
only (one seeder, one fixtures file, two specs); no production code, no PHP test.
Trap: the overlay spec asserts the **exact** tint list of chapter 11, so any new
annotation there must be added to `AUTHOR_TINT` and to the user-filter exclusion
in `chapter-annotation-overlay.spec.ts`, or that spec breaks. The `confirmed`
popover test asserts exactly one « Signaler » on the fox popover — hence the new
rows go on `user`'s root (102), never on `confirmed`'s. Most likely reversed
assumption: reaction on root 102 rather than on a new chapter. E2E needs the app
running (see `e2e/README.md`); the gate does not run Playwright, so BUILD must
run `pnpm run e2e` on the three annotation specs by hand.

## 1. Functional spec

### 1.1 Overview

Seed data so a browser spec can see « Signaler » on (a) a reaction (annotation
whose body is a single emoji) in the « N annotations » pop-up, author view, and
(b) a reply written by a co-author of the story, seen by another author. Closes
the rendering gap left by `annotations-v4` VERIFY; the rules themselves stay
pinned by PHP tests.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Réaction | Annotation whose body is `<p>EMOJI</p>`, created by one click in the selection toolbar |
| Co-auteur | Collaborator of story 6 with role `author`: `tech_admin` (the story owner is `author`) |

### 1.3 Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| `author` (story owner) | Root 102's pop-up: `user`'s reaction and the co-author's reply, each with « Signaler » | Report them |
| `tech_admin` (co-author) | Their own reply, without « Signaler » | — |
| `confirmed` | Unchanged: nothing of root 102 | Unchanged |

### 1.4 Functional requirements

1. Seed on chapter 11, root 102 (`user`): a reaction `<p>❤️</p>` on a free passage,
   and a reply by `tech_admin` to an existing annotation of that root.
2. Mirror both in `ANNOTATED` of `e2e/support/fixtures.ts`.
3. Extend `chapter-annotation-report.spec.ts`: the author opens root 102's
   pop-up; the reaction row and the co-author reply each carry « Signaler »
   (`annotation-report`), and one is reported end to end.

### 1.5 Lifecycle

N/A — seed data, wiped with the e2e database.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Uses existing accounts only |
| Visibility / privacy | N/A — test data |
| Settings, Notifications, Events, Statistics, Search, Media, i18n, Mobile | N/A — no production behaviour changes |
| Moderation | Reports created by the spec land in the e2e DB only (same as the existing report spec) |
| Lifecycle / cascade | N/A |
| Accessibility | Assert the button's accessible name « Signaler » as the existing spec does |

### 1.7 Out of scope

- Re-testing the report rules in the browser (PHP tests own them).
- New accounts, a new chapter, a new story, or any production code.
- Reaction/co-author cases for `confirmed`'s root 101 (its counts are pinned by existing specs).

### 1.8 Open questions

None blocking.

## 2. Architecture

### 2.1 Domain placement

Test support only. `app/Domains/Comment/Database/Seeders/E2eCommentsSeeder.php`
(`seedInChapterAnnotations()`) gets the rows; `e2e/support/fixtures.ts` mirrors
them; no deptrac change.

### 2.2 Data model

No schema change. Insert into `comment_annotations` via the existing `$annotation`
closure:

- Reaction: root 102, author `user`, body `<p>❤️</p>`, `highlighted_text` `Une chouette observe` (free in chapter 11, outside the `depuis la branche la plus haute` anchor).
- Reply: root 102, author `tech_admin`, `parent_annotation_id` = the id of `user`'s `traverse la clairière` annotation (capture its id as `$clearing`), body e.g. `<p>Précision de la coautrice.</p>`.
- `tech_admin` id: add `tech_admin@e2e.test` to the `whereIn` lookup; the `count() < 4` guard becomes 5.

### 2.3 PHP architecture

None.

### 2.4 Frontend

None. Spec side: `ANNOTATED.user.owl = 'Une chouette observe'`,
`ANNOTATED.bodies.reaction = '❤️'`, `ANNOTATED.bodies.coauthorReply`; spec reuses
`ChapterAnnotations` page object (`serverRows`, `replies`).

### 2.5 Deptrac

No new edge.

### 2.6 Testing strategy

Playwright only: extend `chapter-annotation-report.spec.ts` with one test (author,
root 102: reaction row has the button; co-author reply has the button; own-reply
absence is already covered for `author`). Update `AUTHOR_TINT` in
`chapter-annotation-overlay.spec.ts` (+ the user-filter exclusion) for the new
tinted passage. Run `pnpm run e2e` on the annotation specs.

### 2.7 Tradeoffs locked

| # | Question | Options considered | Chosen | Why / what would flip it |
|---|----------|--------------------|--------|--------------------------|
| 1 | Where to add rows | root 101 (`confirmed`); root 102 (`user`); new chapter | Root 102 | Root 101 rows change the `confirmed` popover button count and the « mine » assertions; a new chapter is heavier than needed |

### 2.8 Risks acknowledged

The overlay spec's exact tint lists and the author's avatar/gutter grouping
could shift with a new passage on the shared line; revisit if the overlay spec
fails after the seed change.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Seed, fixtures, specs | S | — | TODO |

Working agreement: one phase = one commit; failing test first; no phase N+1
before N's acceptance holds; re-ordering is surfaced, not taken silently.

### Phase 1 — Seed, fixtures, specs

**Goal.** The author sees « Signaler » on a reaction and on a co-author's reply in a browser spec. **Reads.** §2.2, §2.6.

**Deliverables.**
- `app/Domains/Comment/Database/Seeders/E2eCommentsSeeder.php`: rows of §2.2 (and docblock bullet for root 102).
- `e2e/support/fixtures.ts`: `ANNOTATED.user.owl`, `bodies.reaction`, `bodies.coauthorReply`.
- `e2e/tests/core/chapter-annotation-report.spec.ts`: new test (written first, red before the seed).
- `e2e/tests/core/chapter-annotation-overlay.spec.ts`: tint lists updated.

**Tests.** `author reports a reaction and a co-author reply from the pop-up` (Playwright).

**Acceptance.**
- New spec green after re-seeding; the three annotation specs (`report`, `overlay`, `round-trip`/`chapter-annotations`) green.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Pop-up root 102, author | Reaction row and co-author reply each show « Signaler » | |
| Pop-up root 101, `confirmed` popover | Still exactly one « Signaler » | |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Rows go on `user`'s root 102 of chapter 11, not on a new chapter | REFINE | Yes |
| 2 | `tech_admin` is the "other author" (co-author of story 6) | REFINE | Yes |
| 3 | Overlay spec tint lists are updated rather than avoiding chapter 11 | REFINE | Yes |

## 5. Build & verify log

_Not written yet._
