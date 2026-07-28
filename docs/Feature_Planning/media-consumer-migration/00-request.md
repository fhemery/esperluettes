# MultiEdit — migrate the remaining ImageService consumers — request

Migrated from `MultiEdit_Consumer_Migration.md` (the Phase 2.5 checkpoint) and
`MultiEdit_Planning.md`. The Media domain and MultiEdit v1 are live — see
[`../multiedit/README.md`](../multiedit/README.md).

## What I want

Get the last three consumers off `Shared\Services\ImageService` and onto
`MediaPublicApi`, then physically move `ImageService` into the Media domain and
delete the Shared copy.

## Why

The migration stopped after the FAQ pilot and News. **Calendar, StaticPage and
Profile still call `Shared\Services\ImageService` directly**, which blocks the
finish line: `ImageService` cannot move into Media while Shared consumers exist,
so the Media domain is not yet the sole entry point its own documentation claims
it is.

This is the item actively accruing cost — every new image consumer added
meanwhile is one more to migrate later.

## The plan as it stands

Recipe per consumer: swap `process` → `store`, drop `deleteWithVariants`,
register a `MediaUsageProvider`, adopt `<x-media::image>` /
`<x-media::image-field>`, add `MediaPublic` to deptrac. **None require a schema
change.**

Order, from the checkpoint document:

1. **Calendar** (`ActivityController`) — self-contained, like FAQ. **Fix the
   scope first:** the folder on disk is `activities/`, so add an `activities`
   scope to `MediaService::folderFor`. Do not reuse `calendar`.
2. **StaticPage header** (`StaticPageService`) — identical shape to the News
   header; one provider over `header_image_path`. Sets up
   `multiedit-static-pages/`, whose provider will later add `content_blocks`
   paths to the same provider.
3. **Profile picture** (`ProfileService`) — the special case. It uses
   `saveSquareJpg` (a single 200×200 JPEG, **no responsive variants**) and
   manages default avatars with direct `Storage` calls, so `<x-media::image>`
   does not apply. Recommendation carried over: migrate it **last** and
   **minimally** — expose `saveSquareJpg` through `MediaPublicApi`, keep
   Profile's own display and deletion.
4. **Relocate `ImageService`** into `Media/Private/Services` and delete the
   Shared copy. Deptrac should then show no `*Private → Shared\ImageService`
   edges except Media's.

## Constraints

- A domain that stores image paths **must** register a `MediaUsageProvider`, or
  its files become invisible to `media:gc`. Guard each one with a test.
- `media:gc` runs daily at 03:30 with a 7-day grace window — a missing provider
  is recoverable within that window, not after.
- News header images and other existing dated files are grandfathered; the
  migration must not orphan them.

## Explicitly out of scope

- Advanced (block) mode for static pages and chapters — separate backlog tasks.
