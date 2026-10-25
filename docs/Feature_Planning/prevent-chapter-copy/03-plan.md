# Prevent copying chapter text — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Copy guard on the reader chapter body | S | — | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

No checkpoint row: the phase adds three attributes to one element and reshapes
no existing code; regressions on selection / annotation / quote toolbars are
covered by the visual QA checklist below.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- Branch: stay on `fix/misc-small-fixes` (DECISIONS assumption 4).

---

## Phase 1 — Copy guard on the reader chapter body

**Goal.** When the viewer is not an author/co-author of the story, the chapter
body `<article data-quote-article>` swallows `copy`, `cut` and `dragstart`
events; authors are unaffected (architecture §3, §4).

**Context (verified during PLAN).**
- `ChapterViewModel::$isAuthor` is computed in `ChapterController` (line ~133)
  from `Story::isAuthor($userId)`, which checks `story_collaborators` with
  `role = 'author'` — so co-authors are `true`, beta readers and other
  collaborator roles are `false`, guests are `false`.
- The article lives at
  `app/Domains/Story/Private/Resources/views/chapters/show.blade.php` line ~283,
  inside `x-data="chapterPage()"` (line ~21) and the unconditional `x-data` of
  `<x-comment::annotable>` — Alpine listeners on it work without a new scope.
- No other `x-on:copy` / `x-on:cut` exists in the views, so string assertions
  on the rendered page are unambiguous.

**Deliverables.**
- `app/Domains/Story/Private/Resources/views/chapters/show.blade.php` — on the
  `<article data-quote-article …>` element add, only when `!$vm->isAuthor`:
  ```blade
  @unless($vm->isAuthor) x-on:copy.prevent x-on:cut.prevent x-on:dragstart.prevent @endunless
  ```
  Use the `x-on:` long form (not `@copy`) so Blade never mistakes it for a
  directive. Do not touch `user-select`, `contextmenu`, or any other element
  (title, author note, comments stay copyable — functional §4.1.3).
- No PHP, JS, CSS, translation or route change.

**Tests.**
New file `app/Domains/Story/Tests/Feature/Chapters/ChapterCopyGuardTest.php`
(Pest, `uses(TestCase::class, RefreshDatabase::class)`; reuse `alice`, `bob`,
`moderator`, `admin` from `app/Domains/Auth/Tests/helpers.php` and
`publicStory`, `createPublishedChapter`, `addCollaborator` from
`app/Domains/Story/Tests/helpers.php` — no file-local helpers). Each test GETs
`route('chapters.show', ['storySlug' => …, 'chapterSlug' => …])` and asserts
with `assertSee('x-on:copy.prevent', false)` / `assertDontSee(…, false)` (also
check `x-on:cut.prevent` and `x-on:dragstart.prevent`):
- `it('guards the chapter body against copy for a guest')`
- `it('guards the chapter body against copy for a confirmed reader')`
- `it('guards the chapter body against copy for a beta reader collaborator')` —
  `addCollaborator($story->id, $bob->id, 'beta-reader')` (role string verified
  in existing tests, e.g. `StoryShowTest`).
- `it('guards the chapter body against copy for a moderator')`
- `it('guards the chapter body against copy for an admin')`
- `it('does not guard the chapter body for the author')`
- `it('does not guard the chapter body for a co-author')` —
  `addCollaborator($story->id, $bob->id, 'author')`.

**Acceptance.**
- ✅ Guest, confirmed reader, beta reader, moderator and admin get a chapter page
  whose `data-quote-article` carries `x-on:copy.prevent`,
  `x-on:cut.prevent`, `x-on:dragstart.prevent`.
- ✅ The author and a co-author get the page without any of the three.
- ✅ Existing `ViewChapterTest`, `ChapterAnnotateButtonViewTest`,
  `ChapterAnnotationOverlayViewTest` and Quote domain tests still pass unchanged.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh. Use a published chapter of a public story
with at least a few paragraphs.

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter page, confirmed reader, desktop | Selecting text in the body works; the quote / annotate / reaction toolbar appears as before | |
| Chapter page, confirmed reader, desktop | Ctrl/Cmd+C on a body selection copies nothing (paste into a text field shows the previous clipboard); no message shown | |
| Chapter page, confirmed reader, desktop | Ctrl/Cmd+X and right-click → Copy on a body selection copy nothing | |
| Chapter page, confirmed reader, desktop | Dragging a body selection into a text field drops nothing | |
| Chapter page, confirmed reader, desktop | Saving a quote and creating an annotation from a selection still succeed end to end | |
| Chapter page, confirmed reader, desktop | Copying the chapter title, the author note and a comment still works | |
| Chapter page, guest | Selection works; Ctrl/Cmd+C on the body copies nothing | |
| Chapter page, moderator / admin | Ctrl/Cmd+C on the body copies nothing | |
| Chapter page, author | Ctrl/Cmd+C on the body copies the text; author heat map / annotations unaffected | |
| Chapter page, co-author | Ctrl/Cmd+C on the body copies the text | |
| Chapter page, confirmed reader, mobile viewport (touch emulation) | Long-press selection still works and the selection toolbar appears; native Copy from the selection menu copies nothing | |
| Chapter page, Advanced (block) content chapter, reader | Same copy block applies inside `.ce-block` content | |
| Chapter page, keyboard only (reader) | Shift+arrow keyboard selection still works | |

## Open items

- **Phase 1 / VERIFY** — the tests only prove the attributes are rendered;
  actual event blocking is verified by hand in VERIFY (architecture §6). Adding
  an e2e spec under `e2e/tests/features/` is optional and not planned (clipboard
  assertions in Playwright need permissions; deemed not worth it for a deterrent).
- **VERIFY** — whether the mobile native selection menu's "Copy" fires a `copy`
  event on the article in every browser is not guaranteed; record the observed
  behaviour rather than fixing it (functional §8: deterrent only).

## VERIFY result (2026-10-25) — PASS

Run against the e2e instance with a temporary feature spec (deleted afterwards; no spec kept).
Cancelable synthetic `copy` / `cut` / `dragstart` dispatched on the chapter body:

- ✅ confirmed reader, guest, moderator: all three default-prevented.
- ✅ author and co-author: none prevented.
- ✅ confirmed reader: selection works, selection toolbar appears.
- ✅ Existing `chapter-annotation*` core specs (14 tests: annotate, quote overlap, heat map, round trip) still pass.
- n/a (not automatable, deterrent only): real clipboard contents after Ctrl+C, right-click Copy, drag into a field,
  mobile native selection menu Copy, keyboard Shift+arrow selection, Advanced-mode `.ce-block` (the handler sits on the
  enclosing `data-quote-article`, covered by the PHP test), title / author note / comment copy (outside the article).
  Real mobile behaviour not observed; left as an eyeball item.
