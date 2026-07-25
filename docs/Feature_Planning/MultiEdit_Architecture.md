# MultiEdit — Technical Architecture

Companion to `MultiEdit.md` (functional spec). This document defines the technical design. Delivery sequencing lives in `MultiEdit_Planning.md`.

## 1. Scope recap

- **Functional v1 surface:** News only (Static pages + Chapters deferred).
- **Foundation decision:** introduce a `Media` domain that owns image **upload, responsive variants, the reuse picker, garbage collection, and the reusable upload/display components**. It is **path-addressed** — the identifier of an image is its storage path, exactly as `ImageService` already works today.
- **No reference table, no `asset_id`, no consumer migration.** Content (columns and `content_blocks`) already records which paths it uses; that content *is* the source of truth. Cleanup is a scheduled sweep that asks each domain which paths it still uses.
- **Media owns its Blade components** (`<x-media::image-field>`, `<x-media::image>`); the Shared multi-editor composes them for image blocks.

## 2. Decisions locked (architecture)

| # | Decision |
|---|----------|
| A | **Storage:** advanced content is an ordered JSON block array in a new nullable `content_blocks` column; **presence of `content_blocks` = advanced mode** (no separate mode flag). `content` is reused as a **rendered-HTML cache** for advanced docs. **No data migration** — existing rows stay simple. |
| B | **Path is the identity of an image.** Blocks and image columns store the **storage path string** (what `ImageService::process` already returns and every `image_path` column already holds). **No `asset_id`, no `media_assets`/`media_references` tables.** The same path may appear many times — across documents *and repeated within one owner* (e.g. a chapter using one separator image several times) — with no bookkeeping. |
| C | **New `Media` domain** owns all image handling: storage + responsive variants (absorbs `ImageService`), the reuse picker, garbage collection, and the reusable `<x-media::image-field>` / `<x-media::image>` components. Public entry point: `MediaPublicApi` + a `MediaUsageRegistry`. |
| D | **No up-front consumer migration.** The 6 current `ImageService` consumers keep their `image_path` columns and simply call `MediaPublicApi::store` instead of `ImageService::process` directly; `ImageService` moves into `Media` as an internal detail. Adopting the shared component + registering a usage provider is done per consumer, incrementally, and is **not** a prerequisite for News MultiEdit. |
| E | **Media owns the components:** the editable `<x-media::image-field>` and the readonly `<x-media::image>` live in `Media`. The Shared multi-editor composes `<x-media::image-field>` for image blocks (`Shared → MediaPublic`, the same allowed shape as `Shared → ConfigPublic`/`SettingsPublic`). |
| F | **Cleanup reads the truth, it does not cache it.** Each domain registers a `MediaUsageProvider` reporting the paths it currently references. A scheduled `media:gc` sweep unions those paths and deletes on-disk files (under managed scopes) that no provider claims. Media never joins into other domains' tables — every domain reports its own paths. |
| G | **GC is safe-by-design, deferred, never on-the-spot.** Removing an image from a document just drops the path from that document's content; no file is deleted at save time. `media:gc` deletes only files that (a) live under a managed scope, (b) are claimed by **no** provider, and (c) are older than a **7-day grace window**. The grace window covers upload-then-failed-save debris and a forgotten/buggy provider (recoverable before deletion). |

**Why path-addressed and not a reference registry.** `ImageService` is already entirely path-keyed (`process()` returns a path; `deleteWithVariants($disk, $path)` deletes by path; variants are a `name-<width>w.ext` naming convention in the same folder). Every current consumer stores an `image_path`. A `media_references` table would be a *denormalized cache* of what content already states, and caches drift — that drift is what produced the owner-collision and reference-completeness hazards of the earlier draft. Storing paths and reading usage directly from content removes the second source of truth entirely.

## 3. Domain topology & dependency direction

```
                 ┌─────────────┐
                 │   Media     │  (new)  — storage, variants, picker, GC, components
                 │  Public API │         — MediaPublicApi + MediaUsageRegistry
                 └──────▲──────┘
        depends on      │ depends on (Shared → MediaPublic)
      (Private→Shared)  │
   ┌───────────────┐    │        ┌──────────────────────────────────────┐
   │    Shared     │────┘        │ Consumers of MediaPublicApi:          │
   │  editor.blade │             │  News (MultiEdit v1 + header image)   │
   │  multi-editor │             │  FAQ, StaticPage, Calendar, Profile   │
   └───────▲───────┘             │  (each registers a MediaUsageProvider)│
           │ used by             └──────────────────────────────────────┘
   News / Static / Chapter / Comment / Profile … (Blade components)
```

**Deptrac:** add `MediaPublic` to the `Shared` layer's allowlist (alongside `SettingsPublic`, `ConfigPublic`). `Media` (Private) depends on `Shared` as usual. This `Shared → MediaPublic` / `MediaPrivate → Shared` shape already exists for Config/Settings and is accepted — no new cycle policy needed. Each consumer domain adds `MediaPublic` to its own allowlist and registers its usage provider in its ServiceProvider.

## 4. The `Media` domain

### 4.1 No tables

Media owns **no database tables**. An image is a file (plus its derived variants) under a scope folder on a disk. There is no id, no metadata row, no reference row. Descriptive data that used to justify a table:

- **alt text** is per-placement and lives with the owner (the image block's `alt`, FAQ's `image_alt_text`, etc.) — never global to a file.
- **dimensions / variants** are derived from the file by `ImageService` naming convention, not stored.
- **alt pre-fill on reuse** becomes best-effort: the picker may copy alt from an existing placement of the same path if one is cheaply available, otherwise the author types it. (Functional §5.5 already treats alt as per-block and editable.)

### 4.2 `MediaPublicApi`

```
store(string $scope, UploadedFile $file, array $widths = [400,800]): string   // → stored original path
listByScope(string $scope, int $page = 1, int $perPage = 40): MediaPathPageDto // picker listing
variantUrl(string $path, int $width, string $format = 'webp'): string
folderFor(string $scope): string
countUsages(string $path): int                                                // sum of occurrences across providers
```

- **`store`** delegates to the absorbed `ImageService::process($disk, folderFor($scope), $file, $widths)` and returns the stored **original path**. It does not track anything — the path only becomes "used" once the caller persists it in its own content.
- **`listByScope`** lists **original** images directly under `folderFor($scope)` for the reuse picker — **non-recursive** (subfolders excluded) — filtering out `-<width>w.(jpg|webp)` variant files. Backed by a disk listing (paginated); results are cache-friendly. Per-author scopes (`chapters/{userId}`) are naturally isolated because the scope string carries the user id.
- **`variantUrl`** builds a variant URL from a path by naming convention (`{name}-{width}w.{format}`), the one place that assembly lives.
- **`countUsages`** returns how many times a path is currently referenced across the whole app (sum over registered providers). Used by the component's "used in N places" indicator; computed on demand, not on every render.

**DTO** (Public/Contracts/Dto): `MediaPathPageDto { items: MediaPathDto[], page, hasMore }`, `MediaPathDto { path, url(width,format) }`.

There is **no** `getById`, `syncReferences`, or `releaseAll` — nothing to sync, because content is the reference.

### 4.3 Scope → path mapping

`folderFor(scope)`:

| Scope string | Folder | Picker sharing |
|--------------|--------|----------------|
| `news` | `news/` | shared among News editors |
| `faq` | `faq/` | shared |
| `static-pages` | `static-pages/` | shared |
| `profile` | `profile/…` | as today |
| `calendar` | `calendar/…` | as today |
| `chapters/{userId}` | `chapters/{userId}/` | per author |

The caller (surface) builds the scope string; Media resolves the folder. GC is scope-independent (a file lives while *any* provider claims its path).

### 4.4 Absorbing `ImageService`

`ImageService` is already path-based (`process` → path, `deleteWithVariants($disk, $path)`, variant naming). Media's `MediaService` **delegates** to it, and new callers reach it only through `MediaPublicApi`. To keep every phase non-breaking, `ImageService` **stays in `Shared/Services` while unmigrated consumers still reference it directly**; it is physically relocated into `Media/Private/Services` (and dropped from Shared) only once the last consumer is off it. `MediaService → Shared\ImageService` is an allowed `MediaPrivate → Shared` dependency in the meantime.

### 4.5 The usage registry & garbage collection

**`MediaUsageRegistry` (public).** Each domain that stores image paths registers a provider in its ServiceProvider:

```php
interface MediaUsageProvider {
    /**
     * Every managed image path this domain currently references,
     * one entry per occurrence (duplicates included, for accurate counts).
     * @return iterable<string>
     */
    public function usedPaths(): iterable;
}
```

- **News** yields its header `image_path` plus every image-block `path` in `content_blocks` (structured — no HTML parsing, because text blocks forbid `<img>`, §4.6).
- **FAQ / Profile / Calendar / StaticPage** yield their `image_path` column values.
- **Chapters (later)** yield image-block paths from `content_blocks`.

**`media:gc` (scheduled command).** Deletion is decoupled from every save:

1. Build the **live set** = union of `usedPaths()` across all registered providers.
2. List on-disk **original** files under each managed scope folder.
3. Delete (via `ImageService::deleteWithVariants`) every original that is **not** in the live set **and** whose file mtime is older than the **7-day grace window**. This also removes debris (uploaded-then-abandoned files) once past the window.

Scheduled **daily at 03:30** (off midnight to avoid the daily-job pile-up).

The grace window makes the sweep safe against the one real failure mode — a domain that stores paths without registering a provider: its files look unclaimed, but they are only *deletable* after 7 days, long enough for the gap to surface (e.g. a test asserting "every scope with stored paths has a provider"). `deleteWithVariants` is idempotent, so re-runs are harmless.

**`countUsages($path)`** is the same registry read, filtered: sum the occurrences of `$path` across providers. Providers back it with an indexed/targeted query where possible; exact counts (needed because "used in N places" must not over-count on path substrings) come from the same enumeration used for `usedPaths`.

### 4.6 Why text blocks forbid `<img>`

Keeping the News provider cheap and exact, image-block paths are the **only** image paths inside advanced News content. Multi-editor **text blocks are sanitized with a dedicated `multiedit-text` Purifier profile that drops `img`** (mirrors `admin-content` minus `img`; the existing `admin-content` profile *does* allow `img` — see `config/purifier.php`). So the provider enumerates paths from structured image blocks alone, never by parsing arbitrary HTML.

This is safe for the Simple→Advanced switch because the shared editor **cannot insert images** (`editor-bundle.js`: *"No image module registered, so user cannot insert images via toolbar"*). The only way an `<img>` reaches simple content is pasted external HTML pointing at a non-managed URL; the switch drops it (with a warning if present), losing no managed file.

## 5. Advanced-content storage (News, v1)

Migration on the News table:

```
add column content_blocks JSON NULL   -- source of truth for advanced docs
-- content (existing TEXT/LONGTEXT) reused as rendered-HTML cache for advanced docs
```

- **Mode is inferred:** `content_blocks IS NULL` ⇒ simple; non-null ⇒ advanced.
- **Simple docs unchanged:** `content` = author HTML, `content_blocks` = null. No backfill.
- **Advanced docs:** `content_blocks` authoritative; on every save the service renders blocks → sanitized HTML → writes `content`. All existing readers (`show.blade` `{!! $news->content !!}`, search, carousel summary) keep working with zero changes.
- **Down migration** drops `content_blocks` (advanced docs degrade to their cached `content` HTML — lossy for re-editing structure, acceptable for a rollback).

### 5.1 Block JSON schema (canonical)

```json
[
  { "type": "text",  "html": "<p>Intro…</p>" },
  { "type": "image", "path": "news/sep-abc123.jpg", "alt": "A map", "caption": "Fig. 1" },
  { "type": "text",  "html": "<p>More…</p>" }
]
```

- `path` is the canonical image reference (resolved to variant URLs via `MediaPublicApi::variantUrl`). `alt` is the effective per-block alt (required); `caption` optional. The **same `path` may appear in multiple image blocks** (reuse / repeated separator).
- Empty blocks are dropped before persistence (functional §4.5).
- Text `html` is sanitized per block with the **`multiedit-text`** profile — `admin-content` minus `img` (§4.6).

## 6. Frontend — the multi-editor (Shared)

### 6.1 Component

`<x-shared::multi-editor>` (anonymous Blade + Alpine), props:

| Prop | Purpose |
|------|---------|
| `name` | base field name (e.g. `content`) |
| `blocks` | initial block array (from `content_blocks`, or `[{type:text,html:content}]` when opting a simple doc in) |
| `mode` | `simple` \| `advanced` (initial) |
| `blockTypes` | allowed types, e.g. `['text','image']` |
| `scope` | Media scope string for uploads/picker |
| `toolbar` | the surface's existing editor toolbar config (passed through to each text block) |
| `min` / `max` | summed-text constraints |

### 6.2 Alpine state

The component owns `blocks: [{ uid, type, … }]` plus `mode`. Responsibilities:

- **Add/insert/reorder/delete** operate on the array; each block has a stable client `uid` for keying.
- **Text block:** renders `editor.blade.php` markup with a unique `id`/`name` derived from `uid`; after insertion calls `window.initQuillEditor(id)` (idempotent — guarded by `data-quill-inited`). The editor's hidden `<textarea>` feeds serialization.
- **Image block:** renders `<x-media::image-field>` (§8.2) — current image preview, upload, remove, "Choose existing" picker, alt/caption. Holds either a pending `File` (new upload) or a `path` (reuse).
- **Palette** (bottom) and **"+" insert affordances** (between blocks) driven by `blockTypes`.
- **Mode toggle:** Simple→Advanced wraps current HTML as text block #0 (warns if it contains `<img>`); Advanced→Simple enabled only when exactly one text block and zero image blocks.

### 6.3 Media picker

`<x-media::image-field>` opens the Media picker: a modal calling a Media web endpoint (`GET /media/library?scope=…&page=…`, auth-gated) backed by `MediaPublicApi::listByScope`. Selecting an image sets the block's `path`; alt is pre-filled best-effort (§4.1) and remains editable.

### 6.4 Serialization (multipart)

On submit the component emits, per block index `i` in visual order:

```
blocks[i][type]      = text | image
blocks[i][html]      = <sanitized-on-server> (text only)
blocks[i][alt]       = … (image only)
blocks[i][caption]   = … (image only, optional)
blocks[i][path]      = news/sep-abc123.jpg   (image reuse — existing path)
blocks[i][file]      = <UploadedFile>        (image new upload)
mode                 = simple | advanced
```

Order is the array index. Simple mode submits the plain single-editor field exactly as today (no `blocks[]`).

## 7. Server processing (News)

### 7.1 FormRequest

`NewsRequest` gains conditional rules when `mode = advanced`:

- `blocks` present, array, ≥1 surviving block.
- Each block: `type in [text,image]`; text ⇒ `html` string; image ⇒ (`path` xor `file`) present, `alt` required non-empty, `caption` nullable, `file` obeys image mime/size, `path` must resolve within `scope` (folder-prefix check via `MediaPublicApi::folderFor`).
- **Summed-text min/max** validated across text blocks' plain-text length.

### 7.2 NewsService

`create`/`update` branch on mode:

- **Simple:** unchanged (`sanitizeContent` → `content`).
- **Advanced:**
  1. For each image block with a `file`: `path = MediaPublicApi::store($scope, $file)`. For `path` blocks: reuse the path as-is.
  2. Build the normalized block array (drop empties, sanitize each text `html` via the `multiedit-text` profile — no `img`, §4.6).
  3. Persist `content_blocks = blocks`.
  4. `content = ContentBlocksRenderer::render($blocks)` (sanitized HTML cache).
- **Delete:** nothing special — the row goes away, its paths leave the News provider's `usedPaths()`, and `media:gc` reclaims any now-unclaimed files after the grace window.

`scope` for News = `"news"`. **News registers a `MediaUsageProvider`** yielding the header `image_path` plus all image-block `path`s across News rows.

## 8. Rendering & components

### 8.1 `ContentBlocksRenderer` (Shared)

A pure function `render(array $blocks): string` and/or `<x-shared::content-blocks :blocks="…">`:

- text → sanitized HTML passthrough.
- image → `<x-media::image>` (responsive `<figure><picture>` centered, `max-width:100%`) + optional `<figcaption>`, alt from block.

Used at **save time** to populate the `content` cache. Public views keep rendering `{!! $content !!}` — no view change for News in v1. (Chapters will later render from blocks directly for per-block annotation, see §10.)

### 8.2 Media components (owned by `Media`)

**`<x-media::image>` — readonly display.** Renders any managed image by **path** as responsive `<picture>` (webp `<source>` + jpg `<img>` fallback), resolving variants via `MediaPublicApi::variantUrl`:

```blade
<x-media::image path="news/sep-abc123.jpg" :alt="$altText"
    sizes="…" :widths="[400,800]" class="…" loading="lazy" />
```

This is the **one** place variant URLs are assembled. Reused by the `ContentBlocksRenderer` image branch, every consumer's display, and (later) News header image.

**`<x-media::image-field>` — editable.** The dedicated upload/manage control the multi-editor image block and single-image consumers both use:

```blade
<x-media::image-field name="…" :path="$currentPath" scope="news"
    :alt="$alt" :caption="$caption" :show-usage="true" />
```

Responsibilities:
- **Displays the current image** (via `<x-media::image>`) when a path is set.
- **Upload** a new file (drag/drop or pick; on-submit multipart).
- **Remove** — clears the field's path (file untouched; GC reclaims if it becomes unclaimed).
- **Choose existing** — opens the picker (§6.3), sets the path.
- alt (required) / caption (optional) fields.
- **"Used in N places"** indicator via `MediaPublicApi::countUsages($path)` when `show-usage` is set, so an author knows whether the image is shared before removing it.

Both components living in `Media` keeps all image UI in one cohesive domain; consumers depend on `MediaPublic`.

## 9. Existing `ImageService` consumers — **FAQ pilot first**

The 6 consumers (FAQ, News `NewsService` + `NewsObserver`, Calendar, StaticPage, Profile) **keep their `image_path` columns** — there is **no `asset_id` migration and no backfill**. Adopting Media is a light, per-consumer change; **FAQ is the pilot** (smallest, self-contained — one image per question) to validate the component + provider pattern end-to-end before the rest.

FAQ specifics (`FaqQuestion`: `image_path`, `image_alt_text`; `FaqQuestionController`):

- **Schema:** unchanged. Keep `image_path` and `image_alt_text`.
- **Controller:** inject `MediaPublicApi`; call `store('faq', $file)` on create/replace instead of `ImageService::process`. Remove no longer clears anything but the `image_path` column. Delete-question just clears the row.
- **Provider:** register a `MediaUsageProvider` yielding every non-null `faq_questions.image_path`.
- **Display:** replace hand-rolled markup with `<x-media::image :path="$q->image_path" :alt="$q->image_alt_text" />` (§8.2). Editing uses `<x-media::image-field scope="faq">`.

The remaining consumers (News header, StaticPage header, Calendar, Profile picture) follow the same recipe — swap `process`→`store`, adopt the components, register a provider — and are sequenced after the pilot.

## 10. Chapter-forward hooks (not built in v1)

Design constraints kept open so chapters aren't blocked later:

- `ContentBlocksRenderer` can emit **each text block as its own `[data-annotable]` region**; `canonical-text.js` then builds the canonical projection **per block**, satisfying "annotations constrained to a single text block."
- Word/character counting sums text blocks (already the validation model in §7.1).
- Chapter scope = `chapters/{userId}` (per-author picker); a chapter provider yields its `content_blocks` image paths. Repeated separator images within one chapter are naturally supported (same path many times).
- Image annotation remains out of scope (tracked in `Chapter_Annotations.md`).

## 11. Backward compatibility & risks

- **Simple docs:** byte-for-byte unchanged; zero migration.
- **No reference cache to drift.** The earlier owner-collision / reference-undercount hazards are gone by construction: content is the only record of usage.
- **Denormalization risk (content cache):** `content` cache must never drift from `content_blocks`. Mitigation: a single write path in the service always rewrites the cache from blocks; the cache is never edited independently.
- **GC correctness:** deletion is decoupled from save — a removed image just leaves the content; nothing is deleted synchronously. `media:gc` deletes only unclaimed files past a 7-day window, `deleteWithVariants` is idempotent. **Residual risk:** a domain that stores paths but forgets to register a provider — its files look unclaimed. Mitigations: the 7-day window (recoverable), and a test asserting every scope with stored paths has a registered provider.
- **GC / picker cost:** `usedPaths` fan-out and the picker's disk listing are the price of not caching. Both are batch/on-demand (scheduled sweep; picker open), paginated, and cache-friendly — far cheaper than per-file id resolution on every read.
- **`countUsages` precision:** must count real occurrences (not `LIKE %path%` substrings) so "used in N places" is exact; providers enumerate rather than pattern-match.

## 12. Testing strategy

- **Media:** `store` (original + variants written, returns path), `listByScope` (lists originals under scope, excludes `-Nw` variants, paginates, per-author isolation), `variantUrl` naming, `folderFor` mapping. `MediaUsageRegistry`/`countUsages` (sums occurrences across providers; exact, no substring over-count). `media:gc`: deletes only unclaimed files older than 7 days; spares claimed and recently-modified files; idempotent; sweeps rowless debris. `multiedit-text` sanitizer strips `<img>`.
- **Providers:** each consumer's provider yields exactly its stored paths (News: header + block paths; FAQ: `image_path`s).
- **News MultiEdit:** mode round-trip (simple↔advanced), block CRUD/reorder persistence, summed min/max validation, empty-block dropping, render-cache equals rendered blocks, image upload vs reuse, **same path repeated in one document**, two documents sharing one path, delete leaves file until GC then reclaims.
- **Components:** `<x-media::image>` path→srcset output; `<x-media::image-field>` shows current image / upload / remove / choose-existing / usage count.
- **Renderer:** blocks → expected sanitized HTML (text passthrough sanitization, image figure/srcset).
- **Deptrac:** passes with `MediaPublic` added to Shared + consumer allowlists.

## 13. Resolved items

- **Media picker route:** single `Media` web route `GET /media/library?scope=…&page=…`, auth-gated per scope, owned by Media (Phase 1) even though the modal UI lands with Phase 3. The scope resolves to **one base folder**; the listing is **non-recursive** — only images directly under that folder are returned, **subfolders excluded**.
- **Search indexing:** there is **no News search**, so nothing reads structured News fields — the `content` cache keeps all existing readers working with no change.
- **`media:gc`:** 7-day grace window; scheduled **daily at 03:30** (deliberately off midnight to avoid the daily job pile-up).

---

## Next step

`MultiEdit_Planning.md` sequences: **(1)** Media domain (absorb `ImageService`, `MediaPublicApi`, `MediaUsageRegistry`, `media:gc`, `<x-media::image>` + `<x-media::image-field>`, `/media/library` route) → **(2)** **FAQ pilot** (swap to `store`, adopt components, register provider) + checkpoint to plan the remaining consumers → **(3)** Shared multi-editor + `ContentBlocksRenderer` + `multiedit-text` profile → **(4)** News advanced mode (storage, form, service, provider, view, tests) → **(5)** later surfaces (Static pages, then Chapters).
