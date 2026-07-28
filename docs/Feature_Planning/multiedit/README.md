# MultiEdit and the Media domain

**Status:** v1 DONE — 2026-07-27, adoption unfinished · **Domain(s):** `Media`,
`Shared`, `News`, `FAQ`

Read this before touching image handling anywhere, or before adding block
editing to a new surface.

## What it does

Two things shipped together:

- **`Media`** — a path-addressed image domain. An image is a file plus derived
  variants under a scope folder. There is **no table, no id, no reference row**:
  content *is* the reference. A swept garbage collector deletes files no
  registered provider claims.
- **`<x-shared::multi-editor>`** — an "advanced mode" editor where content is an
  ordered list of text and image blocks, serialised to a `content_blocks` JSON
  column and rendered to an HTML `content` cache.

News has advanced mode. FAQ was the pilot for Media adoption.

## Key behaviour

- **Paths, not ids.** `MediaPublicApi::store($scope, $file)` returns a path; the
  caller persists it in its own content. Nothing is tracked at store time.
- **A domain that stores an image path MUST register a `MediaUsageProvider`**,
  or `media:gc` cannot see its files. Forgetting this means either accumulating
  orphans or — if a scope is swept before its provider exists — premature
  deletion. The 7-day grace window is the safety net.
- **`media:gc` runs daily at 03:30** (`bootstrap/app.php`) with a 7-day grace
  window. `--dry-run` reports without deleting.
- **One provider covers all of a domain's paths.** News' provider yields the
  header `image_path` *and* every `content_blocks` image path. Repeating the
  same path in one article is fine — paths do not collide.
- **Alt text is per-placement**, owned by the consumer, never global to a file.
- **Text blocks forbid `<img>`** — images are their own block type.
- **`content_blocks` is the source of truth; `content` is a rendered cache**
  written by `ContentBlocksRenderer` on save. The public view is unchanged
  (`{!! $content !!}`).

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | `app/Domains/Media/Public/Api/MediaPublicApi.php` |
| Usage contracts | `app/Domains/Media/Public/Contracts/MediaUsage{Provider,Registry}.php` |
| Scope → folder, store, listing | `app/Domains/Media/Private/Services/MediaService.php` |
| GC | `app/Domains/Media/Private/Console/MediaGcCommand.php` |
| Components | `<x-media::image>`, `<x-media::image-field>` |
| Editor | `app/Domains/Shared/Resources/views/components/multi-editor.blade.php` |
| Renderer | `app/Domains/Shared/Support/ContentBlocksRenderer.php` |
| Adopters | `FaqMediaUsageProvider`, `NewsMediaUsageProvider` |

## Scopes

Flat scopes are declared in `MediaService::FLAT_SCOPES`:
`news`, `faq`, `static-pages`, `profile`, `calendar`. Per-author chapter scopes
match `chapters/{userId}`. An unknown scope throws.

⚠️ **`calendar` is declared but wrong.** Calendar's real folder on disk is
`activities/`. The migration task must add an `activities` scope rather than
reuse `calendar` — see `../media-consumer-migration/00-request.md`.

## Decisions worth remembering

- **Media owns no tables.** Dimensions are derived by naming convention; alt is
  per-placement; there is no `getById`, no `syncReferences`, no `releaseAll`.
- **`ImageService` deliberately stayed in `Shared/Services`** so every phase
  could be non-breaking. `MediaService` delegates to it. It moves into
  `Media/Private/Services` only when the last direct consumer is gone.
- **The FAQ pilot came before News** specifically to test the adoption recipe on
  something cheap.

## Not done

- **Three consumers still call `Shared\Services\ImageService` directly**:
  `Calendar\ActivityController`, `StaticPage\StaticPageService`,
  `Profile\ProfileService`. Until they are migrated, `ImageService` cannot move
  into Media and the domain is not the sole entry point it claims to be.
  → [`../media-consumer-migration/`](../media-consumer-migration/)
- **Advanced mode for static pages** (planned Phase 5a).
  → [`../multiedit-static-pages/`](../multiedit-static-pages/)
- **Advanced mode for chapters** (planned Phase 5b), including per-block
  `[data-annotable]` regions and summed word counts.
  → [`../chapters-multi-edit/`](../chapters-multi-edit/)
- Profile's avatar is a deliberate special case: a single 200×200 JPEG with no
  responsive variants, so `<x-media::image>` does not apply. The recommendation
  carried forward is to migrate it last and minimally.

The pre-loop documents (functional spec, architecture, delivery planning,
consumer-migration checkpoint) are in git history:
`git show f1d50704:docs/Feature_Planning/MultiEdit_Architecture.md`.
