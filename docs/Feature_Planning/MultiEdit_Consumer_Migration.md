# MultiEdit — Phase 2.5 Checkpoint: Consumer Migration Plan

Planning output (not code). Captures what the **FAQ pilot** (Phase 2) taught us and
sequences the remaining `ImageService` consumers onto the Media domain. These
migrations are **off the MultiEdit critical path** and can run in parallel with
Phases 3–4.

## What the FAQ pilot proved

The path-addressed model works and simplifies the consumer:

- **No schema change, no backfill.** FAQ reused its existing `image_path` column.
- **Controllers got simpler.** All `deleteWithVariants` calls were removed — the
  controller never deletes files; the Media GC reclaims a path once no provider
  reports it. Store is a one-liner: `MediaPublicApi::store('faq', $file)`.
- **The usage provider is tiny** (one class pluck-ing a column) and registered in
  the domain's ServiceProvider `boot()`.
- **Components dropped in cleanly.** `<x-media::image-field>` replaced the
  bespoke upload widget + separate alt field; `<x-media::image>` replaced
  hand-rolled `<picture>` markup. The field emits a nested payload
  `name[path] | name[file] | name[alt] (| name[caption])`, read server-side as
  `$request->file('name.file')` + `$data['name']['path']`.
- **Deptrac** needed `MediaPublic` added to `{Domain}Private` and `{Domain}Tests`.

### Surprises to carry forward

1. **Non-recursive scope vs. dated subfolders.** Today every consumer stores under
   `folder/Y/m/…`. The Media scope model is **flat and non-recursive**:
   `store('faq', …)` writes to `faq/` (no date), and the picker/GC only see files
   *directly* under the scope folder. Consequence:
   - New images land flat under the scope folder.
   - **Pre-existing dated-subfolder images still display** (`variantUrl` handles any
     path depth) but are **not reusable via the picker** and are **invisible to GC**
     (they persist). This is acceptable (safe, non-destructive) but means the
     library picker only lists images uploaded *after* migration. If a consumer
     wants old images reusable/GC-managed, it must flatten them (out of scope for
     now).
2. **`alt` policy is per-consumer.** FAQ kept alt optional (`alt-required=false`)
   to avoid changing behavior. MultiEdit image blocks will require alt. The field
   supports both via the `altRequired` prop.
3. **The 6th consumer is `NewsObserver`**, not a separate domain — it deletes the
   News header on delete. It must be migrated together with `NewsService`.
4. **`folderFor` scope map has real gaps** (below): Calendar's real folder is
   `activities/`, not `calendar/`; Profile is a non-variant special case. Fix
   `MediaService::folderFor` (and architecture §4.3) as each consumer lands.

## Per-consumer plan

All follow the FAQ recipe unless noted: swap `process`→`store`, drop
`deleteWithVariants`, register a `MediaUsageProvider`, adopt the components, add
`MediaPublic` to deptrac. **None require a schema change.**

### News header — `NewsService` + `NewsObserver`
- Scope `news`, folder `news/` (existing dated images grandfathered).
- `processHeaderImage` → `MediaPublicApi::store('news', $file)`; delete `header_image_path`
  clearing to `null` (no file delete). Remove `deleteHeaderImage` and the
  `NewsObserver` delete call.
- **Shares its provider with MultiEdit (Phase 4).** The News `MediaUsageProvider`
  must report **`header_image_path` *and* every `content_blocks` image path** — one
  provider, both sources. With paths (not owner rows) there is **no collision**.
- Display: `<x-media::image>` in `news/show.blade.php` (replaces the hand-rolled
  `<picture>`). Header form field → `<x-media::image-field scope="news">`.
- **Sequencing note:** do the header migration *with or after* Phase 4 so the single
  News provider covers both header and blocks from the start.

### StaticPage header — `StaticPageService`
- Scope `static-pages`, folder `static-pages/`. Identical shape to News header
  (header-image create/replace/remove/delete). One provider over `header_image_path`.
- Also relevant to Phase 5a (Static pages advanced mode) — same provider will later
  add `content_blocks` paths.

### Calendar — `ActivityController`
- **Fix scope first:** real folder is `activities/`. Add scope **`activities`** to
  `MediaService::folderFor` (→ `activities/`) and update architecture §4.3. (Do not
  reuse `calendar` — the folder on disk is `activities/`.)
- `store('activities', $file)`; drop the two `deleteWithVariants` calls; provider
  over `activities.image_path`. Display via `<x-media::image>`.

### Profile picture — `ProfileService` — **special case**
- Profile uses `ImageService::saveSquareJpg` (a single 200×200 JPEG, **no responsive
  variants**) and manages default avatars with direct `Storage` put/delete. It is
  **not** a responsive-`<picture>` image, so `<x-media::image>` (which assumes
  `-Nw` variants) does **not** apply.
- Options:
  - **(a) Minimal:** expose `saveSquareJpg` through `MediaPublicApi`, keep Profile's
    own display and its direct deletion (1:1 avatars, no reuse/GC benefit). Register
    a provider only if we want GC to know about the avatar folder; otherwise the GC
    guard already skips an unclaimed `profile` scope safely.
  - **(b) Defer:** leave Profile on `ImageService` until it physically moves into
    Media; migrate last.
- **Recommendation:** (b) defer, then (a) minimal — Profile gains little from the
  reuse/GC machinery and would only complicate the square-avatar flow.

## `ImageService` relocation (the finish line)
`ImageService` stays in `Shared/Services` until **all** consumers above are off
direct use of it. Once News, StaticPage, Calendar, and Profile call only
`MediaPublicApi` (Profile via an exposed `saveSquareJpg`), physically move
`ImageService` → `Media/Private/Services` and delete the Shared copy. Deptrac will
then show no `*Private → Shared\ImageService` edges except Media's.

## Suggested order
1. **Calendar** (self-contained controller, like FAQ; also fixes the `activities`
   scope gap early).
2. **StaticPage header** (clean service; sets up Phase 5a).
3. **News header** — with/after Phase 4 so the shared News provider covers header + blocks.
4. **Profile** — last, minimal `saveSquareJpg` exposure.
5. **Relocate `ImageService` into Media** once 1–4 are done.
