# Media Domain

## Purpose and scope

The Media domain owns **image handling** for the whole application: uploading, generating responsive variants, the reuse picker, garbage-collecting unused files, and the reusable Blade components that display and edit images. Every other domain reaches it through `MediaPublicApi`; none touch the filesystem or `ImageService` directly.

Its defining choice is that **an image is identified by its storage path** — there is no asset id, no reference table, and the domain owns **no database tables**. The content that uses an image (a column like `image_path`, or an image block inside `content_blocks`) *is* the record of that usage. This keeps a single source of truth and avoids a denormalized reference cache that could drift.

This domain grew out of the **MultiEdit** feature. See [docs/Feature_Planning/multiedit/README.md](../../../docs/Feature_Planning/multiedit/README.md) for the feature record, and [docs/Feature_Planning/media-consumer-migration/](../../../docs/Feature_Planning/media-consumer-migration/) for the remaining `ImageService` consumers.

## Key concepts

### Path as identity

`MediaPublicApi::store($scope, $file)` saves the original and returns its **storage path** (e.g. `news/01K7E6…​.jpg`). Callers persist that string in their own content. There are no ids to resolve and no join to perform — a path is everything the domain needs to store, list, display, and (eventually) delete a file.

### Scopes and folders

A **scope** is a logical bucket that maps to a folder on the `public` disk:

| Scope | Folder | Sharing |
|-------|--------|---------|
| `news` | `news/` | shared among News editors |
| `faq` | `faq/` | shared |
| `static-pages` | `static-pages/` | shared |
| `profile`, `calendar` | same-named folders | as today |
| `chapters/{userId}` | `chapters/{userId}/` | per author |

The caller builds the scope string; `folderFor()` resolves the folder and rejects unknown scopes. The reuse picker (`listByScope`) lists originals **directly under** the scope folder — it is **non-recursive**, so it never descends into dated subfolders left by pre-migration uploads.

### Responsive variants vs. "keep original"

`store()` delegates to `ImageService`, which writes the original plus `-400w`/`-800w` JPEG and WebP variants (named by convention, no metadata stored). The display component assembles a responsive `<picture>` from those variant paths.

An image can also be stored **without variants** ("keep original width"): the original is saved as-is and displayed at its natural size. Because the domain is tableless, whether a path has variants is discovered by checking the disk (`hasVariants()`), and the "keep original" intent for a content-block image travels in the **block JSON** (`keep_original: true`), not in a Media table.

### Usage tracking and garbage collection

Media never learns which files are in use by scanning other domains' tables. Instead, each consuming domain registers a `MediaUsageProvider` that reports the paths **it** currently references. The scheduled `media:gc` command unions those paths and deletes on-disk originals (with variants) that **no** provider claims and that are older than a **7-day grace window**.

Deletion is therefore always **deferred and swept**, never synchronous: removing an image from a document merely stops the content from referencing its path; the file is reclaimed later, if still unused. A guard makes this safe against a forgotten provider — a whole scope folder that holds files but has *zero* claimed paths is treated as an unclaimed scope and **skipped**, not emptied.

### Components

- `<x-media::image>` — read-only responsive display by path, with a `raw` mode that serves the original at natural size (used by keep-original images and the shared `ContentBlocksRenderer`).
- `<x-media::image-field>` — the editable control: upload, remove, "Choose existing" picker, alt/caption, optional usage count, optional "keep original" checkbox.

The reuse picker is backed by the authenticated `GET /media/library?scope=…` endpoint.

## Architecture decisions

**No tables, path as identity.** `ImageService` was already entirely path-keyed (`process()` returns a path; `deleteWithVariants($disk, $path)` deletes by path; variants are a naming convention). Adding asset ids and a `media_references` table would have been a denormalized cache of what content already states. Storing paths and reading usage directly from content removes that second source of truth — and removed the owner-collision and reference-completeness hazards an id-based design would have carried.

**Cleanup reads the truth, it doesn't cache it.** Usage is computed on demand by fanning out over registered `MediaUsageProvider`s, not maintained on every save. The cost moves from every write to a scheduled batch sweep, which is the right place to pay it. The residual risk — a domain that stores paths but forgets to register a provider — is contained by the 7-day grace window and the unclaimed-scope skip guard.

**`ImageService` stays in `Shared` for now.** To keep every migration step non-breaking, `ImageService` remains in `Shared/Services` and `MediaService` delegates to it while unmigrated consumers still reference it directly. It will be relocated into `Media/Private/Services` only once the last consumer calls `MediaPublicApi` exclusively (see `docs/Feature_Planning/media-consumer-migration/`).

**Media owns its Blade components.** Display and upload components live in this domain rather than in `Shared`, so all image UI is cohesive. Consumers (including the `Shared` multi-editor) depend on `MediaPublic` for them — the same `Shared → MediaPublic` shape already accepted for Config/Settings.

## Cross-domain delegation map

| Concern | Delegated to / mechanism |
|---------|--------------------------|
| Image processing, variant generation, file deletion | `Shared::ImageService` (delegated by `MediaService`) |
| Knowing which files are still used | Each consumer registers a `MediaUsageProvider` in its `ServiceProvider::boot()` |
| Rendering variant URLs | Assembled only inside `<x-media::image>` / `MediaPublicApi::variantUrl` |

Consumers currently on Media: **FAQ** (question image) and **News** (header image + advanced content blocks). Remaining `ImageService` consumers (StaticPage header, Calendar, Profile) are scheduled in `docs/Feature_Planning/media-consumer-migration/`.
