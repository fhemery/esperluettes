# Fanart — agent notes

- README: [app/Domains/Fanart/README.md](README.md)

## Public API

None yet.

## Events emitted

- `Fanart.DeletedByModeration` (`FanartDeletedByModeration`, auditable): `fanartId`, `storyId`, `userId`, `moderatorId`. Emitted by `FanartService::deleteByModerator`.

## Listens to

- `Auth.UserDeleted` (hard-delete the user's fanarts, fanartist rows, role requests), `Auth.UserDeactivated` / `Auth.UserReactivated` (soft-delete / restore their fanarts).
- `Story.Deleted` (wipe the story's fanarts, fanartists, requests; notify each non-author fanartist) and `Chapter.Deleted` (delete the chapter's fanarts; notify their submitters except the authors). Both notify `fanart.removed_with_content`.

- `Moderation.ReportSubmitted` (topic `fanart` only): a pending fanart reported by one of its story's authors is declined (hard-deleted, `fanart.declined` without comment). Any other reporter or a published fanart: nothing.

## Registrations

- **Moderation**: topic `fanart` with `Public/Moderation/FanartSnapshotFormatter` (keeps a copy of the file via `_media`). Approving a report never deletes the fanart; moderators use the delete route. Reasons are admin-created; `ModerationSeeder` seeds an « Other » for dev.

- **Media**: `FanartServiceProvider::boot()` registers `FanartMediaUsageProvider` on `MediaUsageRegistry`.

- **Settings**: `booted` callback adds section `fanart` (params `fanart-images-mode` default `yes`, `fanart-audio-mode` default `designated`) to Story's `stories` tab, and `hide-fanart-tab` (bool, default `true`) to the `profile` tab's `privacy` section; each guarded on its own key. `ModeService::effectiveMode` takes the most restrictive value across the story's authors.

- **Notification**: group `fanart` + ten `fanart.*` types (`Public/Notifications`, shared payload in `FanartNotificationContent`). Sent only through `FanartNotifier`, which drops the actor and skips empty recipient sets.

## Invariants

- **Web routes are loaded in `FanartServiceProvider::register()`**, not `boot()`: Story's `/stories/{slug}` catch-all (`.*`) would otherwise swallow `/stories/{slug}/fanarts/…`. Keep every `/stories/{slug}/…` Fanart route in `web.routes.php`, resolve the slug with `StoryResolver`.

- **Review routes (`/fanarts/{fanartId}/accept|decline`) take a raw id, not a model binding**, so a fanart declined by a co-author answers « already decided » (redirect back with `error` flash) instead of 404. `FanartService::replacePublishedAudio` is the single place that enforces one published audio per chapter.

- **One store route, two paths.** `POST /stories/{slug}/fanarts` calls `uploadArtwork` (published at once, one chapter credit, same transaction) for an author, `submit` (pending) for anyone else. `editArtwork` is the uploader only; an edit is free and leaves the old file to `media:gc`.

- **Fanartiste grant / revoke** (`POST /stories/{slug}/fanartistes`, `DELETE …/{userId}`) is authorised by `ManageFanartistsRequest` → `FanartPolicy::manageFanartists` (403). `FanartistService::grant` requires a confirmed target, is idempotent, deletes the pair's pending role request and notifies only on a new grant. The UI is `<x-fanart::collaborators-section>`, used by Story's collaborators page; it renders nothing unless the viewer may manage fanartistes.

- **Role requests** (`POST /stories/{slug}/fanartiste-requests`, `DELETE …/{request}`, `POST …/{request}/accept|decline`) are authorised by `RoleRequestRequest` (403): `FanartPolicy::requestRole` (adds no-pending + 30-day cooldown from the latest decline), the requester, or an author. Accept = `grant` (row deleted); decline keeps the row (`declined`) for the cooldown. `<x-fanart::request-role-button>` is for the story page.

- **Lazy fragments** (`FanartFragmentController`: `fanart.tab`, `fanart.group` with `{group}` = chapter id or `work`, `fanart.show` = viewer / review viewer) return HTML, never JSON. Every listing goes through `Fanart::visibleTo(FanartViewer)` (built by `FanartService::viewerFor`), so readers never load pending items or fanarts of unpublished chapters; authors and staff see everything. The pending block is shown to those who see everything only; `includeOwnPending` is for the owner's own profile tab. Thumbnails click-dispatch a `fanart-open` event carrying the viewer URL.

- **Story-page audio**: `<x-fanart::story-player>` (one `<audio>`, queue in `data-entries`) seeds `Alpine.store('fanartPlayer')` (`fanart-player.js`); `<x-fanart::chapter-audio-icon>` on each Story chapter row calls `play(id)`. Both read `FanartService::audioPlaylist` (memoised per request, visibility via `visibleTo`). On `ended` the store POSTs Story's `chapters.read.mark` (failure ignored) then plays the next entry.

- **Chapter page**: `<x-fanart::chapter-audio>` (under the title, native player + report button) and `<x-fanart::chapter-images>` (view-only thumbnails + viewer modal, before the prev/next bar) sit in Story's `chapters/show.blade.php` **outside** `<article data-quote-article>`; both render nothing unless the viewer passes `seeFeature` and a published item exists.

- **Never delete a fanart file from this domain.** Clear rows; `media:gc` collects files.
- **The usage provider must read `withTrashed()`.** Soft-deleted rows (deactivated users) still own their files; dropping that makes GC delete files a reactivation would need.
- **No FK to `users`, `stories` or `chapters`** — cross-domain by rule. Resolve references through `StoryPublicApi`.
- **`SoftDeletes` on `Fanart` is for user deactivation only.** Every other removal (decline, withdraw, moderator or author delete, chapter/story/user deletion) is a hard delete.
- **Route middleware** follows the Quote precedent: `['web', 'auth', 'compliant', 'role:user-confirmed,moderator,admin,tech-admin']`. Never `Route::patch`.
