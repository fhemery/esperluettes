# Media Domain — Agent Instructions

- README: [app/Domains/Media/README.md](README.md)

## Public API

- [MediaPublicApi](Public/Api/MediaPublicApi.php) — the **only** entry point other domains use for images: `store`, `storePrivate`, `stream`, `exists`, `listByScope`, `variantUrl`, `originalUrl`, `folderFor`, `countUsages`, `hasVariants`. Images are addressed by **storage path** (string) — there are no ids.
- [MediaUsageRegistry](Public/Contracts/MediaUsageRegistry.php) + [MediaUsageProvider](Public/Contracts/MediaUsageProvider.php) — a consuming domain registers a provider so GC knows which files it still uses.
- Blade components (owned here): `<x-media::image>` (read-only), `<x-media::image-field>` (editable). Reuse picker endpoint: `GET /media/library` (`media.library`, auth-gated).

## Events emitted

None.

## Listens to

None. Integration is registry-based, not event-based: consumers push a `MediaUsageProvider` into `MediaUsageRegistry` at boot.

## Non-obvious invariants

**Every domain that stores an image path MUST register a `MediaUsageProvider`.** Register it in the domain's `ServiceProvider::boot()` via `app(MediaUsageRegistry::class)->register(...)`. A provider yields every path the domain currently references, **one entry per occurrence** (duplicates included, so `countUsages` is exact). If you add a new place that stores image paths and skip this, those files become invisible to GC (they accumulate) — and there is no test-time error, only silent leakage. Guard new scopes with a test that asserts the scope has a provider.

**Never delete an image file from a consumer.** Removing an image means clearing the path from your content and nothing more. `MediaPublicApi` has no delete method; `MediaService::gc` is the only code that deletes files, and only for paths **no** provider claims that are older than the 7-day grace window. Calling `ImageService::deleteWithVariants` yourself (e.g. in a controller or observer) is a bug — it defeats reference-by-content and can delete a file another document still uses.

**Never call `originalUrl` / `variantUrl` / `listByScope` on a private path — they throw by design.** A path whose first segment is a private scope root (`secret-gift/…`) lives on the `private` disk and has no `/storage/` URL and no `-400w`/`-800w` variants. Building one would yield a broken image, or a URL to somebody else's file; refusing loudly is the point. Store with `storePrivate` (never `store`, which rejects a private scope), and serve by calling `stream($path, $headers)` **after** your own authorization check — `stream` performs none. Anything that needs a preview URL for a private image must supply its own gated route.

**The private GC guard is applied at the scope root, not per subfolder.** `media:gc` treats `secret-gift/` as one managed folder and sweeps it recursively, so a private root still needs a registered `MediaUsageProvider` — with none, *nothing* under it is ever collected, across every activity. Do not "improve" this by guarding each `secret-gift/{activityId}` folder: an activity whose gifts were all removed would then have zero claimed paths, be skipped forever, and its orphans would leak permanently.

**GC skips whole scopes with zero claimed paths.** `media:gc` never empties a folder that contains files but has no provider reporting any path under it — it treats that as a probable missing provider and skips it. Consequence: a scope that is *legitimately* fully unused won't be collected (mild, safe accumulation), and a scope whose provider you forgot won't be catastrophically wiped. Don't "fix" GC to delete unclaimed scopes.

**`ImageService` is Media-internal and `MediaService` is its only caller.** It lives in `Media/Private/Services`; deptrac makes it unreachable from any other domain. Its `$disk` parameters are an internal detail — every call passes `MediaService::DISK` or `MediaService::PRIVATE_DISK`, resolved from the scope or path. All callers outside Media go through `MediaPublicApi::store` / `variantUrl` / `originalUrl` / `saveSquareJpg`.

**`listByScope` and the picker are non-recursive.** They list originals *directly* under the scope folder and exclude `-<width>w` variant files. Pre-migration images under dated subfolders (`news/2025/10/…`) still display and re-save correctly (path-agnostic), but they do not appear in the picker and are outside GC's reach — safe, but not reusable.

**"Keep original" images have no variants; render them raw.** When an image is stored keep-original, no `-400w`/`-800w` files exist, so `<x-media::image>` must render with `:raw="true"` (serve `originalUrl`, no `<picture>`/srcset). For content blocks this is driven by the block's `keep_original` flag. When an image is **reused** (picked, not uploaded), determine raw-ness from reality with `hasVariants($path)` and force raw if variants are absent — otherwise the srcset points at files that don't exist. The same rule applies to `listByScope`: picker thumb URLs must use the original when variants are missing, or the library grid shows blank boxes.

**`content` is a derived cache for advanced documents.** Consumers that store blocks (currently News `content_blocks`) also write a rendered-HTML `content` via `EditorPublicApi::render()` (in `Editor`) so existing readers keep working. The block array is the source of truth; the cache is rewritten from it on every save and never edited independently.
