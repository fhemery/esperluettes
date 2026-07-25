# Media Domain — Agent Instructions

- README: [app/Domains/Media/README.md](README.md)

## Public API

- [MediaPublicApi](Public/Api/MediaPublicApi.php) — the **only** entry point other domains use for images: `store`, `listByScope`, `variantUrl`, `originalUrl`, `folderFor`, `countUsages`, `hasVariants`. Images are addressed by **storage path** (string) — there are no ids.
- [MediaUsageRegistry](Public/Contracts/MediaUsageRegistry.php) + [MediaUsageProvider](Public/Contracts/MediaUsageProvider.php) — a consuming domain registers a provider so GC knows which files it still uses.
- Blade components (owned here): `<x-media::image>` (read-only), `<x-media::image-field>` (editable). Reuse picker endpoint: `GET /media/library` (`media.library`, auth-gated).

## Events emitted

None.

## Listens to

None. Integration is registry-based, not event-based: consumers push a `MediaUsageProvider` into `MediaUsageRegistry` at boot.

## Non-obvious invariants

**Every domain that stores an image path MUST register a `MediaUsageProvider`.** Register it in the domain's `ServiceProvider::boot()` via `app(MediaUsageRegistry::class)->register(...)`. A provider yields every path the domain currently references, **one entry per occurrence** (duplicates included, so `countUsages` is exact). If you add a new place that stores image paths and skip this, those files become invisible to GC (they accumulate) — and there is no test-time error, only silent leakage. Guard new scopes with a test that asserts the scope has a provider.

**Never delete an image file from a consumer.** Removing an image means clearing the path from your content and nothing more. `MediaPublicApi` has no delete method; `MediaService::gc` is the only code that deletes files, and only for paths **no** provider claims that are older than the 7-day grace window. Calling `ImageService::deleteWithVariants` yourself (e.g. in a controller or observer) is a bug — it defeats reference-by-content and can delete a file another document still uses.

**GC skips whole scopes with zero claimed paths.** `media:gc` never empties a folder that contains files but has no provider reporting any path under it — it treats that as a probable missing provider and skips it. Consequence: a scope that is *legitimately* fully unused won't be collected (mild, safe accumulation), and a scope whose provider you forgot won't be catastrophically wiped. Don't "fix" GC to delete unclaimed scopes.

**Do not reference `Shared\ImageService` from new code.** It still physically lives in `Shared/Services` (delegated to by `MediaService`) only until the last legacy consumer migrates. New callers go through `MediaPublicApi::store` / `variantUrl` / `originalUrl`. When you migrate a consumer, drop its `ImageService` usage and any direct file deletion.

**`listByScope` and the picker are non-recursive.** They list originals *directly* under the scope folder and exclude `-<width>w` variant files. Pre-migration images under dated subfolders (`news/2025/10/…`) still display and re-save correctly (path-agnostic), but they do not appear in the picker and are outside GC's reach — safe, but not reusable.

**"Keep original" images have no variants; render them raw.** When an image is stored keep-original, no `-400w`/`-800w` files exist, so `<x-media::image>` must render with `:raw="true"` (serve `originalUrl`, no `<picture>`/srcset). For content blocks this is driven by the block's `keep_original` flag. When an image is **reused** (picked, not uploaded), determine raw-ness from reality with `hasVariants($path)` and force raw if variants are absent — otherwise the srcset points at files that don't exist.

**`content` is a derived cache for advanced documents.** Consumers that store blocks (currently News `content_blocks`) also write a rendered-HTML `content` via `ContentBlocksRenderer` (in `Shared`) so existing readers keep working. The block array is the source of truth; the cache is rewritten from it on every save and never edited independently.
