# Comment — reply/edit editor no longer displays

> WRAP output — the compact record of the finished feature. **This is the only
> file in the folder an agent should load by default.** The phase documents
> (`01`–`03`) remain as history; link to them when detail is needed.

**Status:** DONE — 2026-08-04 · **Domain(s):** Comment · **Spec:**
[functional](./01-functional.md) · [architecture](./02-architecture.md) ·
[plan](./03-plan.md) · [decisions](./DECISIONS.md)

## What it does

After Editor was extracted into its own domain, chapter comment **Répondre** and
**Éditer** composers could render as blank boxes when no root
`<x-editor::rich-text>` was on the page (`canCreateRoot=false`). The fix pushes
Editor Vite assets from the full-page comment list shell and calls
`initQuillEditor` when **Répondre** opens (edit already did). No policy or API
behaviour changed — only client asset loading and Quill init.

Only **chapter comments** are in scope (the sole shipped Comment host). Permanent
Playwright coverage lives in `e2e/tests/core/chapter-comments.spec.ts`.

## Key behaviour

- **Guest / not_allowed:** no Editor assets, no reply/edit composers (unchanged).
- **Authenticated, allowed:** list shell always includes `_assets`, whether or
  not the root composer is shown.
- **Lazy load:** fragments from `GET /comments/fragments` carry reply/edit
  editors but cannot `@push` assets — the full-page shell must load them first.
- **Répondre / Éditer:** double `requestAnimationFrame` then
  `initQuillEditor('reply-editor-{id}')` or `edit-editor-{id}`; idempotent via
  `dataset.quillInited`.
- **Root form present:** `@once` on `_assets` prevents double Vite entries.

## Where the code lives

| Concern | Path |
|---------|------|
| List shell + Alpine init | `app/Domains/Comment/Private/Resources/views/components/comment-list.blade.php` |
| Editor asset partial | `app/Domains/Editor/Private/Resources/views/components/_assets.blade.php` |
| Asset regression tests | `app/Domains/Comment/Tests/Feature/Views/CommentListEditorAssetsTest.php` |
| E2E seeder | `app/Domains/Comment/Database/Seeders/E2eCommentsSeeder.php` |
| E2E page object | `e2e/pages/ChapterCommentsPage.ts` |
| Permanent E2E | `e2e/tests/core/chapter-comments.spec.ts` |

## Extension points used

- **CommentPolicyRegistry** — unchanged; fix is rendering only.
- **Editor `_assets` partial** — consumed from Comment list shell per Editor
  domain docs.

## Decisions worth remembering

1. **Assets on list shell**, not on fragments — `@push` inside AJAX fragments is
   discarded; include `@include('editor::components._assets')` when
   `!$isGuest && !$error` (DECISIONS #3).
2. **Reply open re-init** mirrors edit — call `initQuillEditor` when
   `activeReplyId` becomes non-null (DECISIONS #4).
3. **E2E is permanent core** — covers `canCreateRoot=false` (author) and edit
   path; written directly under `core/`, not `features/` (DECISIONS #2).
4. **E2E seeder uses `DB::table`** — pinned id 1, avoids `CommentPosted` side
   effects and 140-char API validation (DECISIONS #6).
5. **Assumption (reversible):** chapter-only surface; E2E covers both Répondre
   and Éditer; no policy/API change (DECISIONS assumptions #1–4).

## Not done

- **News comments** — deliberate non-goal; see backlog `news-comments/`.
- **Optional third E2E case** (`canCreateRoot=true` reply) — skipped; phase-1
  feature tests already guard asset dedup when root form is present.
- **E2E spec retirement** — N/A; spec was written under `core/` from the start.
