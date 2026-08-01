# Chapters — MultiEdit content

> WRAP output — the compact record of the finished feature. **This is the only
> file in the folder an agent should load by default.** The phase documents
> (`01`–`03`) remain as history; link to them from here when detail is needed.

**Status:** DONE — 2026-07-31 · **Domain(s):** `Story`, `Editor` (+ `Media`,
`Shared` CSS) · **Spec:** [functional](./01-functional.md) ·
[architecture](./02-architecture.md) · [plan](./03-plan.md) ·
[decisions](./DECISIONS.md)

## What it does

A chapter can now be written either as one rich-text field (Simple, the
default) or as an ordered list of text and image blocks (Advanced), using the
same `<x-editor::multi>` component News uses. Advanced chapters store their
blocks in the new `story_chapters.content_blocks` json column and their
*rendered* HTML in the existing `content` column — the reading page and the
moderation snapshot were not touched and still print `content`. No existing
chapter was migrated: a chapter becomes Advanced only when its author toggles
it, so no already-published text changed on deploy.

## Key behaviour

- **Mode is derived, not stored:** `content_blocks IS NULL` ⇒ Simple. There is
  no `mode` column; the form posts a `mode` field, the DB does not keep one.
- **Single writer for `content`.** `ChapterContentResolver` recomputes it from
  the blocks on every save; nothing else may write it, or the display cache
  desyncs from the blocks.
- **Counts come from text blocks only** — image alt/caption never count.
  `ChapterObserver` counts `EditorPublicApi::plainText($blocks)` (byte-identical
  concatenation) with the existing `WordCounter`/`CharacterCounter`, so a
  no-op conversion does not move `word_count`/`character_count`.
- `ChapterSnapshot::fromModel()` now **reads the persisted count columns**
  instead of recomputing from `content` (see A7 — `charCount` shifts slightly on
  entity-heavy chapters because the column decodes entities).
- **Uploads are scoped to the acting user**, `chapters/{userId}`, derived
  server-side and never from the request. Co-authors upload to their own folder.
- **Moderation "empty content" clears both** `content` and `content_blocks`,
  otherwise the next ordinary save would render the moderated text back.
- Converting or reordering blocks **may silently detach readers' quotes** — no
  warning, no notification, by decision 4.
- Text blocks are sanitized with `multiedit-narrative`, then Story strips
  external links (a content policy, not a sanitizer capability).
- A text block that sanitizes to empty, or an image block with no path, is
  dropped; if nothing survives, the save fails with a validation error.

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | `app/Domains/Editor/Public/Api/EditorPublicApi.php` (`render`/`sanitizeText` take a `$profile`; new `plainText()`) |
| Renderer | `app/Domains/Editor/Private/Support/ContentBlocksRenderer.php` |
| Content resolution | `app/Domains/Story/Private/Support/ChapterContentResolver.php` |
| Media GC | `app/Domains/Story/Private/Support/ChapterMediaUsageProvider.php` (registered in `Public/Providers/StoryServiceProvider.php`) |
| Counts | `app/Domains/Story/Private/Observers/ChapterObserver.php` |
| Request | `app/Domains/Story/Private/Http/Requests/ChapterRequest.php` (rules branch on `mode`) |
| Service | `app/Domains/Story/Private/Services/ChapterService.php` (create/update/`emptyContentBySlug`) |
| Form | `app/Domains/Story/Private/Resources/views/chapters/partials/form.blade.php`, `create/edit.blade.php` (`enctype="multipart/form-data"`) |
| Component | `app/Domains/Editor/Private/Resources/views/components/multi.blade.php` + `multi/_text-block.blade.php` |
| Sanitizer profile | `config/purifier.php` → `multiedit-narrative` |
| Read CSS | `app/Domains/Shared/Resources/css/app.css` (`.rich-content > p:last-of-type`, `.rich-content .media-image`) |
| Tests | `app/Domains/Story/Tests/Feature/Chapters/Chapter{AdvancedMode,ConversionCounts,MediaUsageProvider,SnapshotCounts}Test.php`, `Tests/Feature/ChapterModerationEmptyContentTest.php`, `app/Domains/Editor/Tests/Feature/*` |
| Migration | `app/Domains/Story/Database/Migrations/2026_07_31_100000_add_content_blocks_to_story_chapters_table.php` |

## Extension points used

- **MediaUsageRegistry** — `ChapterMediaUsageProvider` reports every image path
  in `content_blocks`, `withTrashed()`. Registering it makes `chapters/{userId}`
  a *claimed* folder: any future place storing a chapter image path must be
  added to `usedPaths()` in the same commit or the GC deletes those files.
- **Purifier profiles** (`config/purifier.php`) — profiles are named after the
  capability, never the consumer; `render()` defaults to `multiedit-text`, so
  News is untouched by construction.
- New deptrac edges: `StoryPrivate → EditorPublic, MediaPublic` and
  `StoryPublic → MediaPublic` (see `02-architecture.md` §5).

## Decisions worth remembering

Full list in `DECISIONS.md`.

- **#3** No data migration; `content_blocks` NULL = Simple. Opt-in per chapter.
- **#8** The rendered HTML is *stored*, not recomputed on read — rendering at
  read would make every chapter's output a moving target and stale every quote
  at once on any future sanitizer change.
- **#9** Editor is profile-aware rather than widening `multiedit-text`, which
  would silently widen News and static pages.
- **#7 / #10** Counts from text blocks only, via `plainText()`, which must stay
  byte-identical — do not "simplify" it into `plainTextLength()`.
- **#4** Quote detachment on conversion is accepted silently.

## Not done

Deliberate non-goals (`01-functional.md` §8): any bulk migration; `author_note`
stays simple rich-text; per-block `[data-annotable]` regions; image annotation
and image quoting; stale-quote warnings or server-side re-anchoring; story
covers; static pages and the `narrative` preset; search indexing; block-level
moderation.

Cut mid-build / open:

- **D4 — `blocks.*.file` prints raw `validation.image`** and its `max:2048`
  prints nothing; the app publishes no `lang/*/validation.php`, and
  `NewsRequest` has the identical pre-existing gap. Pushed to the
  `validation-messages/` backlog row (decision #11), not fixed here.
- **e2e specs retired** (decision #12): both VERIFY specs are deleted; a trimmed
  block-editor case (add / insert + reorder / delete with the state resync that
  D2 broke) was promoted to `e2e/tests/core/multi-editor.spec.ts`, kept alive by
  `e2e/pages/MultiEditor.ts`. The seeded advanced/simple chapters in
  `E2eStorySeeder` and `e2e/support/fixtures.ts` were kept — `quotes-author-view/`
  and `annotations/` will need a block-rendered chapter to test against.

Assumptions taken without asking (`DECISIONS.md` A7–A13) are reversible except
A9 (derived mode) — see that table before re-litigating any of them.
