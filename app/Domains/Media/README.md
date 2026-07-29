# Media Domain

## Purpose and scope

The Media domain owns **image handling** for the whole application: uploading, generating responsive variants, the reuse picker, garbage-collecting unused files, and the reusable Blade components that display and edit images. Every other domain reaches it through `MediaPublicApi`; none touch the filesystem or `ImageService` directly.

Its defining choice is that **an image is identified by its storage path** — there is no asset id, no reference table, and the domain owns **no database tables**. The content that uses an image (a column like `image_path`, or an image block inside `content_blocks`) *is* the record of that usage. This keeps a single source of truth and avoids a denormalized reference cache that could drift.

This domain grew out of the **MultiEdit** feature, which introduced block-based content (`<x-shared::multi-editor>` and the block renderer) alongside it. Media handles the images; the `Editor` domain owns the block renderer.

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
| `activities` | `activities/` | shared among Calendar admins |
| `chapters/{userId}` | `chapters/{userId}/` | per author |

The caller builds the scope string; `folderFor()` resolves the folder and rejects unknown scopes. The reuse picker (`listByScope`) lists originals **directly under** the scope folder — it is **non-recursive**, so it never descends into dated subfolders left by pre-migration uploads.

### Responsive variants vs. "keep original"

`store()` delegates to `ImageService`, which writes the original plus `-400w`/`-800w` JPEG and WebP variants (named by convention, no metadata stored). The display component assembles a responsive `<picture>` from those variant paths.

An image can also be stored **without variants** ("keep original width"): the original is saved as-is and displayed at its natural size. Because the domain is tableless, whether a path has variants is discovered by checking the disk (`hasVariants()`), and the "keep original" intent for a content-block image travels in the **block JSON** (`keep_original: true`), not in a Media table.

### Usage tracking and garbage collection

Media never learns which files are in use by scanning other domains' tables. Instead, each consuming domain registers a `MediaUsageProvider` that reports the paths **it** currently references. The scheduled `media:gc` command unions those paths and deletes on-disk originals (with variants) that **no** provider claims and that are older than a **7-day grace window**.

Deletion is therefore always **deferred and swept**, never synchronous: removing an image from a document merely stops the content from referencing its path; the file is reclaimed later, if still unused. A guard makes this safe against a forgotten provider — a whole scope folder that holds files but has *zero* claimed paths is treated as an unclaimed scope and **skipped**, not emptied.

### Components

- `<x-media::image>` — read-only responsive display by path, with a `raw` mode that serves the original at natural size (used by keep-original images and Editor's block renderer).
- `<x-media::image-field>` — the editable control: upload, remove, "Choose existing" picker, alt/caption, optional usage count, optional "keep original" checkbox.

The reuse picker is backed by the authenticated `GET /media/library?scope=…` endpoint.

## Architecture decisions

**No tables, path as identity.** `ImageService` was already entirely path-keyed (`process()` returns a path; `deleteWithVariants($disk, $path)` deletes by path; variants are a naming convention). Adding asset ids and a `media_references` table would have been a denormalized cache of what content already states. Storing paths and reading usage directly from content removes that second source of truth — and removed the owner-collision and reference-completeness hazards an id-based design would have carried.

**Cleanup reads the truth, it doesn't cache it.** Usage is computed on demand by fanning out over registered `MediaUsageProvider`s, not maintained on every save. The cost moves from every write to a scheduled batch sweep, which is the right place to pay it. The residual risk — a domain that stores paths but forgets to register a provider — is contained by the 7-day grace window and the unclaimed-scope skip guard.

**Media owns its Blade components.** Display and upload components live in this domain rather than in `Shared`, so all image UI is cohesive. Consumers (including the `Shared` multi-editor) depend on `MediaPublic` for them — the same `Shared → MediaPublic` shape already accepted for Config/Settings.

## Cross-domain delegation map

| Concern | Delegated to / mechanism |
|---------|--------------------------|
| Knowing which files are still used | Each consumer registers a `MediaUsageProvider` in its `ServiceProvider::boot()` |
| Rendering variant URLs | Assembled only inside `<x-media::image>` / `MediaPublicApi::variantUrl` |

Every consumer now goes through `MediaPublicApi`: **FAQ** (question image), **News** (header image + advanced content blocks), **Calendar** (activity image), **StaticPage** (header image) and **Profile**. Image processing itself is internal — `ImageService` lives in `Media/Private/Services` and `MediaService` is its only caller.

Profile is a deliberate special case: its avatar is a single 200×200 JPEG with no responsive variants, so `<x-media::image>` does not apply and only `saveSquareJpg` is exposed. It registers no usage provider and gets no scope — avatars stay outside the sweep, and Profile keeps deleting its own files synchronously.
