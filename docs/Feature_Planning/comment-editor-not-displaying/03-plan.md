# Comment editor not displaying — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Editor assets on list shell + reply init | S | — | DONE |
| 2 | Permanent E2E for reply and edit | M | 1 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (2/2)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Editor assets on list shell + reply init

**Goal.** Restore Quill on chapter comment reply/edit composers by pushing Editor
assets from the full-page list shell and initialising reply editors when
**Répondre** opens.

**Architecture sections.** `02-architecture.md` §4.1 (asset loading), §4.2 (reply
open init), §6 (testing strategy — feature layer).

**Deliverables.**

- `app/Domains/Comment/Private/Resources/views/components/comment-list.blade.php`
  - After the opening `<div … x-data="commentList(…">` (or equivalent top-level
    shell position before `@if($error === 'not_allowed')`), when
    `!$isGuest && !$error`, add `@include('editor::components._assets')`.
  - In the delegated click handler for `[data-action="reply"]`, mirror the
    existing edit path: when `activeReplyId` becomes non-null, call
    `window.initQuillEditor('reply-editor-${id}')` inside a double
    `requestAnimationFrame` (same pattern as `edit-editor-${id}` around lines
    180–187).
- `app/Domains/Comment/Tests/Feature/Views/CommentListEditorAssetsTest.php`
  (new) — asset regression net for the list shell.

**Tests.**

Write failing cases first, then implement. Reuse the counting helpers from
`app/Domains/Editor/Tests/Feature/EditorAssetsTest.php`
(`editorBundleScriptCount`, `editorCssLinkCount`) — copy the two functions into
the new file (they are file-local today). Every case renders
`<x-comment::comment-list-component … />@stack('scripts')`.

Named cases in `CommentListEditorAssetsTest.php`:

- `emits editor assets when canCreateRoot is false` — authenticated user,
  policy stub with `canCreateRoot(): false`, at least one seeded comment;
  expect CSS link count = 1 and bundle script count = 1.
- `emits editor assets exactly once when canCreateRoot is true` — default policy,
  root `<x-editor::rich-text>` also present; still expect each asset count = 1
  (`@once` on `_assets`).
- `emits no editor assets for guest` — logged out; expect bundle script count = 0
  and CSS link count = 0.
- `emits no editor assets when listing is not allowed` — unverified user
  (`alice(…, isVerified: false)`); expect no editor-bundle in HTML and CSS count
  = 0.

**Acceptance.**

- ✅ Authenticated, allowed list with `canCreateRoot=false` includes Editor Vite
  entries in `@stack('scripts')` (feature test).
- ✅ Authenticated, allowed list with `canCreateRoot=true` still loads each
  Editor asset exactly once (feature test).
- ✅ Guest and `not_allowed` viewers do not load Editor assets (feature tests).
- ✅ Opening **Répondre** calls `initQuillEditor` on `reply-editor-{id}` (code
  review — no PHP assertion possible; E2E covers in phase 2).
- ✅ `npm run gate` green.

---

## Phase 2 — Permanent E2E for reply and edit

**Goal.** Add Playwright coverage that proves reply and edit composers boot
Quill in a real browser, including the `canCreateRoot=false` path (chapter
author, no root form).

**Architecture sections.** `02-architecture.md` §6 (E2E layer), §4.2 (reply init
behaviour phase 1 left in place).

**Builds on phase 1.** List shell already includes `_assets`; reply open already
calls `initQuillEditor`. This phase only adds fixture data and browser specs.

**Deliverables.**

- `app/Domains/Comment/Database/Seeders/E2eCommentsSeeder.php` (new)
  - Seed one root comment on `E2eStorySeeder::CHAPTER_ID` (`chapitre-publie-1`),
    authored by `E2eAccountsSeeder::CONFIRMED_EMAIL`.
  - Body: short HTML with a unique plain-text marker (e.g.
    `Commentaire E2E pour éditeur`) so specs can wait for lazy-load without
    hard-coding comment ids in selectors.
  - Export constants mirrored in TypeScript: `ROOT_COMMENT_ID`, `BODY_MARKER`.
- `database/seeders/DatabaseSeeder.php` — register `E2eCommentsSeeder` in the
  `APP_ENV=e2e` block (after `E2eStorySeeder`, before domains that might consume
  comments).
- `e2e/support/fixtures.ts` — add a `COMMENTS` export (chapter slug, body
  marker, root comment id) kept in step with the seeder header comment.
- `e2e/pages/ChapterCommentsPage.ts` (new) — wraps chapter show `#comments`:
  `goto()` (hash `#comments`), `waitForCommentsLoaded()` (scroll sentinel /
  wait for body marker), `openReplyOnRoot()`, `openEditOnRoot()`,
  `replyEditor()` / `editEditor()` returning `RichTextEditor` instances keyed on
  `reply-editor-{id}` / `edit-editor-{id}` from fixtures.
- `e2e/tests/core/chapter-comments.spec.ts` (new, **permanent** — promoted to
  `core/` per DECISIONS #2; header comment explains it guards Quill init on
  inline comment composers app-wide).

**Tests.**

Named Playwright cases in `e2e/tests/core/chapter-comments.spec.ts`:

- `author without root form sees a Quill toolbar when opening Répondre` — fixture
  `author`, chapter `STORY.publishedChapter`, assert no root comment form,
  lazy-load seeded comment, click **Répondre**, `RichTextEditor.waitUntilReady()`
  on reply editor, toolbar visible, can type into `.ql-editor`.
- `comment author sees Quill with existing body when opening Éditer` — fixture
  `confirmed`, same chapter, open **Éditer** on seeded root comment, editor
  visible, body contains `COMMENTS.bodyMarker`, can append text.

Optional third case (only if cheap): `confirmed user with canCreateRoot true still
boots reply editor` — use a second chapter or a user who has not posted a root;
skip if it duplicates phase-1 feature coverage without adding browser signal.

Run locally: `npm run e2e:core -- chapter-comments` (or full `npm run e2e` at
VERIFY). Phase gate is `npm run gate` (E2E stays out of gate per project rules).

**Acceptance.**

- ✅ E2E seeder creates exactly one root comment on the published E2E chapter.
- ✅ Author session: root comment form absent, **Répondre** shows interactive
  `.ql-editor` and toolbar (Playwright).
- ✅ Confirmed session: **Éditer** shows interactive editor with seeded content
  (Playwright).
- ✅ Spec lives under `e2e/tests/core/` (not disposable `features/`).
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled for VERIFY. Chapter comments on a published chapter only (sole shipped
host).

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter — author, `canCreateRoot=false` | No root composer; open **Répondre** on an existing comment → toolbar + typing area visible, not a blank box | |
| Chapter — author, reply submit | Type in reply editor, submit → reply appears (behaviour unchanged) | |
| Chapter — comment owner | Open **Éditer** on own comment → toolbar + body loaded for editing | |
| Chapter — comment owner, save | Change text, **Sauvegarder** → updated body visible | |
| Chapter — reader with root form | User who may post a root: root editor still works; reply/edit still work; no double toolbar or missing assets | |
| Chapter — guest | Comment list visible per policy; no reply/edit composers or editor assets | |
| Chapter — mobile (375px) | Same reply/edit editor visibility on narrow viewport | |
| Chapter — lazy-loaded page | Initial render uses `page=0`; after scroll/load, reply on first loaded comment still boots Quill | |

---

## Open items

| Item | Phase | Notes |
|------|-------|-------|
| Lazy-load timing in E2E | 2 | Chapter show passes `page="0"`; comments arrive via `x-intersect` → `loadMore()`. `ChapterCommentsPage` must scroll `#comments` into view and wait for `COMMENTS.bodyMarker` before clicking actions — verify during BUILD, not assumed silent. |
| Seeder auth for chapter comments | 2 | Chapter policy requires 140-char root bodies via API; seeder may insert via `Comment` model / `DB::table` with `commentable_type = 'chapter'` to avoid fighting validation, or call `CommentPublicApi` under `actingAs(confirmed)`. Pick one in BUILD; both are legal. |
| CGU/terms on first login | 2 | E2E accounts may hit terms page once per run — `auth.setup.ts` / `LoginPage` already handle this; comment specs use pre-authed fixtures only. |
| `initQuillEditor` idempotency | 1 | Editor documents idempotency via `dataset.quillInited`. If reply open + lazy-load both init the same id, behaviour should be harmless — watch for double-toolbar in VERIFY; harden only if observed. |
| Permanent E2E vs WRAP promotion | 2 | DECISIONS #2 requires keeping E2E in suite; write directly under `core/`, not `features/`, so WRAP does not delete it. |
