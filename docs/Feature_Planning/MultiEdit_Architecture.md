# MultiEdit — Technical Architecture

Companion to `MultiEdit.md` (functional spec). This document defines the technical design. Delivery sequencing lives in `MultiEdit_Planning.md`.

## 1. Scope recap

- **Functional v1 surface:** News only (Static pages + Chapters deferred).
- **Foundation decision:** before building the editing feature, we **extract a `Media` domain** (full extraction of image handling, all 6 current `ImageService` consumers migrated) so MultiEdit builds on a clean asset layer.
- **Editor UI stays in `Shared`** and composes the `Media` picker for image blocks.

## 2. Decisions locked (architecture)

| # | Decision |
|---|----------|
| A | **Storage:** advanced content is an ordered JSON block array in a new nullable `content_blocks` column; **presence of `content_blocks` = advanced mode** (no separate mode flag). `content` is reused as a **rendered-HTML cache** for advanced docs. **No data migration** — existing rows stay simple. |
| B | **No polymorphic side table** for blocks (rejected: breaks modularity, join-per-read, ordering bookkeeping). |
| C | **New `Media` domain** owns all image/binary assets: storage, responsive variants (absorbs `ImageService`), reference tracking, reference-counted GC, reuse-picker backend. Sole entry point: `MediaPublicApi`. |
| D | **Full extraction up front:** all 6 current `ImageService` consumers (FAQ, News, Calendar, StaticPage, Profile) migrate to `MediaPublicApi`; `ImageService` moves into `Media` as an internal detail. |
| E | **Editor UI stays in `Shared`:** `editor.blade.php`, `editor-bundle.js`, quill-emoji, the new **multi-editor** component, and the **content-blocks renderer** all live in Shared. Shared depends on `MediaPublic` for the picker. |
| F | **Reference counting is global per asset**; scope governs only storage path + picker listing, not GC. |
| G | **GC is safe-by-design, not immediate:** text blocks forbid `<img>` (dedicated `multiedit-text` sanitizer) so references are complete by construction; refs→0 marks `orphaned_at` (never deletes on save); a scheduled `media:gc` sweep deletes assets orphaned **> 7 days** still at 0 refs. Resolves accumulation-vs-safety. |

## 3. Domain topology & dependency direction

```
                 ┌─────────────┐
                 │   Media     │  (new)  — assets, variants, references, GC, picker API
                 │  Public API │
                 └──────▲──────┘
        depends on      │ depends on (Shared → MediaPublic)
      (Private→Shared)  │
   ┌───────────────┐    │        ┌──────────────────────────────────────┐
   │    Shared     │────┘        │ Consumers of MediaPublicApi:          │
   │  editor.blade │             │  News (MultiEdit v1 + header image)   │
   │  multi-editor │             │  StaticPage, FAQ, Calendar, Profile   │
   │  blocks render│             │  (migrated from ImageService)         │
   └───────▲───────┘             └──────────────────────────────────────┘
           │ used by
   News / Static / Chapter / Comment / Profile … (Blade components)
```

**Deptrac:** add `MediaPublic` to the `Shared` layer's allowlist (alongside the existing `SettingsPublic`, `ConfigPublic`). `Media` (Private) depends on `Shared` as usual. This `Shared → MediaPublic` / `MediaPrivate → Shared` shape already exists for Config/Settings and is accepted — no new cycle policy needed. Each consumer domain adds `MediaPublic` to its own allowlist.

## 4. The `Media` domain

### 4.1 Tables (owned by Media)

**`media_assets`** — one row per stored original file.

| Column | Notes |
|--------|-------|
| `id` | PK |
| `disk` | e.g. `public` |
| `path` | relative path of the original within the disk (variants derived by `ImageService` naming) |
| `scope` | storage/listing scope string, e.g. `news`, `static-pages`, `chapters/{userId}` |
| `mime`, `width`, `height`, `size` | metadata (best-effort) |
| `alt_default` | nullable; pre-fills alt on reuse (functional §5.5) |
| `created_by` | user id (nullable, like News) |
| `orphaned_at` | nullable; set when references reach 0, cleared on re-use; drives grace-period GC (§4.5) |
| timestamps | |

Index on (`scope`) for picker listing; unique on (`disk`,`path`).

**`media_references`** — who uses an asset.

| Column | Notes |
|--------|-------|
| `id` | PK |
| `asset_id` | FK → `media_assets` (same-domain FK, allowed) |
| `owner_type` | plain string, e.g. `news`, `static-page`, `chapter`, `profile` — **no cross-domain FK** |
| `owner_id` | integer id of the owning entity |
| timestamps | |

Unique on (`asset_id`,`owner_type`,`owner_id`). Index on (`owner_type`,`owner_id`) for `syncReferences`/`releaseAll`, and on `asset_id` for GC checks.

> `owner_type`/`owner_id` is a soft reference by design — Media never joins into other domains' tables, honoring "FK only within a domain."

### 4.2 `MediaPublicApi`

```
storeUpload(string $scope, UploadedFile $file, ?string $altDefault = null): MediaAssetDto
getById(int $assetId): ?MediaAssetDto
listByScope(string $scope, int $page = 1, int $perPage = 40): MediaAssetPageDto
syncReferences(string $ownerType, int $ownerId, int[] $assetIds): void
releaseAll(string $ownerType, int $ownerId): void
variantUrl(int $assetId, int $width, string $format = 'webp'): string
```

- **`storeUpload`** delegates to the absorbed `ImageService::process($disk, folderFor($scope), $file, widths)`, inserts a `media_assets` row, returns the DTO. Does **not** create a reference (the caller does that on save via `syncReferences`).
- **`syncReferences`** is the save-time diff: computes current reference rows for (`ownerType`,`ownerId`), inserts new asset ids, deletes removed ones. It **never hard-deletes files**: any asset whose reference count reaches **0** is marked `orphaned_at = now()` (and hidden from pickers); any asset that regains a reference has `orphaned_at` cleared. Actual file deletion happens later, in the grace-period sweep (§4.5). Runs in a transaction.
- **`releaseAll`** = `syncReferences(ownerType, ownerId, [])` — used on document delete.
- **`listByScope`** returns distinct assets for the picker (clean list — no disk scan, no `-400w`/`-800w` variant noise). Paginated for large libraries.

**DTOs** (Public/Contracts/Dto): `MediaAssetDto { id, disk, path, scope, altDefault, url(width,format) }`, `MediaAssetPageDto { items[], page, hasMore }`.

### 4.3 Scope → path mapping

`folderFor(scope)`:

| Scope string | Folder | Picker sharing |
|--------------|--------|----------------|
| `news` | `news/` | shared among News editors |
| `static-pages` | `static-pages/` | shared |
| `chapters/{userId}` | `chapters/{userId}/` | per author |

The caller (surface) builds the scope string; Media resolves the folder. GC is scope-independent (an asset lives while *any* reference exists).

### 4.4 Absorbing `ImageService`

`ImageService` moves `Shared/Services → Media/Private/Services`, unchanged in behavior. Its public capabilities are only reachable through `MediaPublicApi`. Header-image style single-file features (News/Static/FAQ/Calendar/Profile) migrate to `storeUpload` + `syncReferences`/`releaseAll` (see §9).

### 4.5 Reference completeness & garbage collection

The naïve "delete the file the instant its reference count hits 0" is **unsafe**: reference tracking runs on save, and if any content path is used without a registered reference, we would delete a live file. Two independent guards address this.

**Guard 1 — references complete by construction.** A media asset can only ever be *used* via an `asset_id`, never via a raw path:

- Multi-editor **text blocks forbid `<img>`** — they are sanitized with a dedicated Purifier profile (`multiedit-text`) that drops `img`. Images exist *only* as image blocks (`asset_id`). (Note: the existing `admin-content` profile *does* allow `img` — see `config/purifier.php` — which is exactly the hole; the new text profile closes it.)
- All other asset owners (the 6 migrated consumers) reference by `asset_id` too.

So there is no in-app way to reference an asset that bypasses `syncReferences`; the registry cannot undercount from application content. (A raw path could only re-enter via direct DB editing, which is out of scope.)

**Guard 2 — deferred, swept deletion (never delete on the spot).** Even with Guard 1, a *wiring bug* (an owner we forgot to migrate) could undercount. So deletion is deferred:

- `syncReferences` marks `orphaned_at` when refs reach 0 (Guard against accumulation: they are queued for collection). Re-use clears it.
- A scheduled command **`media:gc`** deletes assets where `orphaned_at < now() − 7 days` **and** still at 0 references (`deleteWithVariants` + remove the `media_assets` row). The **7-day grace window** gives a recovery margin: a transient/buggy undercount can be noticed and corrected before any file is destroyed.
- The same command optionally sweeps **on-disk files with no `media_assets` row** (failed-upload debris) past the same window.

This resolves the accumulation-vs-safety tension: orphans *are* collected (scheduled, so no unbounded growth) but never deleted immediately (so a missed reference is recoverable, not catastrophic).

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
  { "type": "image", "asset_id": 123, "alt": "A map", "caption": "Fig. 1" },
  { "type": "text",  "html": "<p>More…</p>" }
]
```

- `asset_id` is the canonical image reference (resolved to URLs via Media). `alt` is the effective per-block alt (required); `caption` optional.
- Empty blocks are dropped before persistence (functional §4.5).
- Text `html` is sanitized per block with a dedicated **`multiedit-text`** Purifier profile — mirrors `admin-content` **minus `img`** (images belong to image blocks only; see §4.5 Guard 1). This is what keeps the reference registry complete.

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
- **Image block:** renders `image-upload.blade.php` for new uploads, plus alt/caption fields and a **"Choose existing"** button that opens the Media picker (§6.3). Holds either a pending `File` (new) or an `asset_id` (reuse).
- **Palette** (bottom) and **"+" insert affordances** (between blocks) driven by `blockTypes`.
- **Mode toggle:** Simple→Advanced wraps current HTML as text block #0; Advanced→Simple enabled only when exactly one text block and zero image blocks.

### 6.3 Media picker

A Shared Alpine modal that calls a Media web endpoint (`GET /media/library?scope=…&page=…`, auth-gated) backed by `MediaPublicApi::listByScope`. Selecting an asset sets the block's `asset_id` and pre-fills `alt` from `alt_default`.

### 6.4 Serialization (multipart)

On submit the component emits, per block index `i` in visual order:

```
blocks[i][type]      = text | image
blocks[i][html]      = <sanitized-on-server> (text only)
blocks[i][alt]       = … (image only)
blocks[i][caption]   = … (image only, optional)
blocks[i][asset_id]  = 123           (image reuse)
blocks[i][file]      = <UploadedFile> (image new upload)
mode                 = simple | advanced
```

Order is the array index. Simple mode submits the plain single-editor field exactly as today (no `blocks[]`).

## 7. Server processing (News)

### 7.1 FormRequest

`NewsRequest` gains conditional rules when `mode = advanced`:

- `blocks` present, array, ≥1 surviving block.
- Each block: `type in [text,image]`; text ⇒ `html` string; image ⇒ (`asset_id` xor `file`) present, `alt` required non-empty, `caption` nullable, `file` obeys image mime/size, `asset_id` must resolve within `scope` (ownership/scope check via Media).
- **Summed-text min/max** validated across text blocks' plain-text length.

### 7.2 NewsService

`create`/`update` branch on mode:

- **Simple:** unchanged (`sanitizeContent` → `content`).
- **Advanced:**
  1. For each image block with a `file`: `assetId = MediaPublicApi::storeUpload($scope, $file, $alt)`. For `asset_id` blocks: reuse as-is.
  2. Build the normalized block array (drop empties, sanitize each text `html` via the `multiedit-text` Purifier profile — no `img`, §4.5).
  3. Persist `content_blocks = blocks`.
  4. `content = ContentBlocksRenderer::render($blocks)` (sanitized HTML cache).
  5. `MediaPublicApi::syncReferences('news', $news->id, $distinctAssetIds)` — creates/removes references and GCs orphans.
- **Delete:** `MediaPublicApi::releaseAll('news', $news->id)` in `NewsService::delete`.

`scope` for News = `"news"`.

## 8. Rendering — `ContentBlocksRenderer` (Shared)

A pure function `render(array $blocks): string` and/or `<x-shared::content-blocks :blocks="…">`:

- text → sanitized HTML passthrough.
- image → responsive `<figure><picture>…</picture><figcaption?></figure>` using Media `variantUrl` (webp+jpg srcset, centered, `max-width:100%`), alt from block.

Used at **save time** to populate the `content` cache. Public views keep rendering `{!! $content !!}` — no view change for News in v1. (Chapters will later render from blocks directly for per-block annotation, see §10.)

### 8.1 `<x-shared::media-image>` — the asset display component

A single Shared component renders any Media asset by id (or DTO) as responsive `<picture>` markup, resolving variants via `MediaPublicApi::variantUrl`:

```blade
<x-shared::media-image :asset="$dto" {{-- or :asset-id="123" --}}
    :alt="$altText" sizes="…" :widths="[400,800]" class="…" loading="lazy" />
```

It emits `<source type="image/webp" srcset>` + `<img>` jpg fallback (the same shape hand-rolled today in `news/show.blade.php`). This is the **one** place variant URLs are assembled, and it is reused by:

- the `ContentBlocksRenderer` image branch (MultiEdit image blocks),
- every migrated consumer's display (FAQ pilot first — §9),
- later, News header image and the rest.

Keeping it in Shared (not Media) leaves `Media` a headless API/backend domain; all Blade rendering lives in Shared, consistent with decision E.

## 9. Migrating existing ImageService consumers — **FAQ pilot first**

There are 6 consumers (FAQ, News, Calendar, StaticPage, Profile — plus News's MultiEdit use). Rather than convert all at once, **FAQ is the pilot**: the smallest, self-contained case (one image per question). We migrate FAQ, validate the pattern end-to-end, then plan the remaining consumers from that experience (see planning doc).

**Chosen shape: reference by `asset_id`** (not the interim keep-the-path approach).

FAQ specifics (`FaqQuestion`: `image_path`, `image_alt_text`; `FaqQuestionController` handles create/update/delete):

- **Schema:** add nullable `image_asset_id` (plain integer, **no FK** — `media_assets` is another domain). Keep `image_alt_text` on `FaqQuestion` (alt is per-placement, owner-held — same model as MultiEdit image blocks). Drop `image_path` after backfill.
- **Backfill migration:** for each row with `image_path`, `MediaPublicApi` (or a one-off) inserts a `media_assets` row (`scope='faq'`, `path=image_path`, `alt_default=image_alt_text`, `created_by=created_by_user_id`) + a `media_references` row (`owner_type='faq-question'`, `owner_id=id`), and sets `image_asset_id`. Then drop `image_path`.
- **Controller:** inject `MediaPublicApi` instead of `ImageService`.
  - create with file → `storeUpload('faq', $file, $alt)` → asset id; `syncReferences('faq-question', $id, [$assetId])`.
  - replace/remove → `syncReferences('faq-question', $id, $newAssetIds)` (`[]` to release).
  - delete question → `releaseAll('faq-question', $id)`.
- **Display:** replace hand-rolled markup with `<x-shared::media-image :asset-id="$q->image_asset_id" :alt="$q->image_alt_text" />` (§8.1).

The remaining consumers (News header, StaticPage header, Calendar, Profile picture) follow the same `asset_id` recipe and are sequenced after the pilot.

## 10. Chapter-forward hooks (not built in v1)

Design constraints kept open so chapters aren't blocked later:

- `ContentBlocksRenderer` can emit **each text block as its own `[data-annotable]` region**; `canonical-text.js` then builds the canonical projection **per block**, satisfying "annotations constrained to a single text block."
- Word/character counting sums text blocks (already the validation model in §7.1).
- Chapter scope = `chapters/{userId}` (per-author picker).
- Image annotation remains out of scope (tracked in `Chapter_Annotations.md`).

## 11. Backward compatibility & risks

- **Simple docs:** byte-for-byte unchanged; zero migration.
- **Denormalization risk:** `content` cache must never drift from `content_blocks`. Mitigation: a single write path in the service always rewrites the cache from blocks; the cache is never edited independently.
- **Media as a new hard dependency** for 6 domains at once (full extraction) is the largest risk to schedule — isolated to the migration phase, before MultiEdit editing work.
- **GC correctness:** deletion is decoupled from save (§4.5) — `syncReferences` only flips `orphaned_at` inside the save transaction and never touches files, so a crash mid-save cannot destroy data. Actual deletion is a separate, idempotent `media:gc` sweep with a 7-day grace window, guarded by the `multiedit-text` no-`img` rule that makes references complete. Residual risk: a consumer that references an asset **without** calling `syncReferences` (wiring bug) — caught within the grace window before the file is removed.
- **Picker scale:** `listByScope` paginated; shared `news`/`static-pages` pools could grow — pagination + optional search later.

## 12. Testing strategy

- **Media:** unit/integration for `storeUpload` (asset row + variants), `syncReferences` (add/remove; refs→0 sets `orphaned_at`; re-use clears it; **no file deleted on save**), `releaseAll`, `listByScope` scoping (orphaned assets excluded), scope→path mapping. `media:gc`: deletes only assets orphaned > 7 days at 0 refs; spares recently-orphaned and re-referenced ones; sweeps rowless disk debris. `multiedit-text` sanitizer strips `<img>`. Regression tests for each migrated consumer (header image create/replace/delete still works).
- **News MultiEdit:** mode round-trip (simple↔advanced), block CRUD/reorder persistence, summed min/max validation, empty-block dropping, render-cache equals rendered blocks, image upload vs reuse, reference counting across two documents sharing one asset, delete releases references.
- **Renderer:** blocks → expected sanitized HTML (text passthrough sanitization, image figure/srcset).
- **Deptrac:** passes with `MediaPublic` added to Shared + consumer allowlists.

## 13. Open items for the planning doc

- ~~Per-domain migration shape~~ — **settled: `asset_id`, FAQ piloted first**, others sequenced after (§9).
- Whether the Media picker is a shared route under `Media` (`/media/library`) or per-surface — proposed: single `Media` route, scope as query param, authorization per scope.
- Search indexing: confirm News search reads `content` (cache) — if it reads structured fields, no change needed either way.

---

## Next step

`MultiEdit_Planning.md` sequences: **(1)** Media domain (tables, `MediaPublicApi`, absorb `ImageService`, `media:gc`, `<x-shared::media-image>`) → **(2)** **FAQ pilot** migration to `asset_id` + checkpoint to plan the remaining consumers → **(3)** Shared multi-editor + `ContentBlocksRenderer` + `multiedit-text` profile → **(4)** News advanced mode (storage, form, service, view, tests) → **(5)** later surfaces (Static pages, then Chapters).
