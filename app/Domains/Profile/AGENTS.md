# Profile Domain — Agent Instructions

- README: [app/Domains/Profile/README.md](README.md)

## Public API

- `ProfilePublicApi` contract (defined in `Shared`), implemented by `ProfileApi` — resolves profile data (DTO, slug, avatar URL, roles, search) for other domains.

## Events emitted

| Event | Event name | When |
|-------|------------|------|
| `AvatarChanged` | `Profile.AvatarChanged` | Avatar uploaded or removed by the profile owner |
| `BioUpdated` | `Profile.BioUpdated` | Description or any social handle changed |
| `ProfileDisplayNameChanged` | `Profile.DisplayNameChanged` | Display name (and therefore slug) changed |
| `AvatarModerated` | `Profile.AvatarModerated` | Moderator removed the avatar |
| `AboutModerated` | `Profile.AboutModerated` | Moderator cleared the bio |
| `SocialModerated` | `Profile.SocialModerated` | Moderator cleared all social handles |

## Listens to

| Event | Action |
|-------|--------|
| `Auth::UserRegistered` | Create profile row and generate default SVG avatar |
| `Auth::EmailVerified` | Invalidate profile cache (role changes become visible) |
| `Auth::UserDeleted` | Hard-delete profile and avatar files |
| `Auth::UserDeactivated` | Soft-delete profile |
| `Auth::UserReactivated` | Restore soft-deleted profile |

## Non-obvious invariants

**No FK to `users`.** The `user_id` primary key has no database foreign key to the `users` table. Cross-domain FKs to Auth are prohibited. All lifecycle actions are driven by domain events.

**Social handles, not full URLs.** Columns store only the handle/username (e.g. `myuser`). Full profile URLs are built at render time via `Profile::socialUrl(string $network)`. Never store a full URL in a handle column.

**Mastodon handle format.** Expected as `user@instance` or `@user@instance`. The URL is built as `https://{instance}/@{user}`. Handles with fewer than two `@`-separated parts resolve to `null`.

**Avatar fallback path.** If `profile_picture_path` is `null`, `ProfileAvatarUrlService` falls back to `profile_pictures/{user_id}.svg` on the `public` disk. This SVG is generated at registration by `AvatarGenerator` and stored once. Deleting a custom picture resets the column to `null` — it does not delete the fallback SVG.

**Description sanitization happens in the service, not the form request.** `ProfileService::updateProfileWithPicture()` runs `clean($data['description'], 'strict')` before saving. Do not add sanitization logic in the request class.

**Slug changes on every display name change.** `ProfileService::applyDisplayName()` always regenerates the slug via `SimpleSlug::normalize()`. URLs using the old slug will break. There is currently no redirect table.

**Cache key format.** Profile cache uses the key `profile_by_user_id:{user_id}` with a 10-minute TTL (600 s). `ProfileCacheService::getByUserId()` returns `false` on a cache miss (no entry) and `null` when a null was explicitly cached (known missing profile). Check for `instanceof Profile` or `=== null` accordingly.

**Soft delete vs. hard delete.** `UserDeactivated` triggers a soft delete (`SoftDeleteProfileOnUserDeactivated`). `UserDeleted` triggers a hard delete plus avatar file cleanup (`RemoveProfileOnUserDeleted`). These are separate listeners for separate events — do not merge them.

**`ProfileSnapshotFormatter::capture()` uses `user_id` as `$entityId`.** When registering a moderation report for a profile, pass the `user_id`, not an auto-increment ID (the model has no auto-increment PK).

**Profile does not own its tabs.** The public profile page renders whatever is in `ProfileTabRegistry`; only `about` is registered by Profile itself. Do not add a tab, a route or a controller method here for another domain's content — that domain registers a `ProfileTabDefinition` from its own service provider (see "Tab registry" below).

## Tab registry

`ProfileTabRegistry` (`Public/Api`) is a container singleton, bound in `ProfileServiceProvider::register()`. Any domain registers a `ProfileTabDefinition` from its `boot()`, and owns that tab's label, visibility rule and component. Currently: `about` (Profile), `stories` + `comments` (Story), `following` (Follow), `quotes` (Quote).

Things to know before touching it:

- **One catch-all route serves every tab**: `/profile/{slug}/{tab}` → `ProfileController::showTab()`. It must stay declared last in the group so `/edit`, `/lookup`, `/search` and the moderation routes match first. There is no per-tab route and no per-tab middleware; `compliant` applies to the whole group and per-tab access is decided by the registry.
- **Access is binary and lives in one place.** `visibleFor()` drives both the tab strip and the route guard. A tab the viewer cannot see is absent *and* unreachable — a denied guest is redirected to login, a denied authenticated user to the default tab. Never add a "you may not see this" placeholder inside a tab; hide the tab instead.
- **Visibility must not count content.** It runs for every tab on every profile render. An empty tab is still shown and renders its own empty state.
- **Tab components get exactly one prop, `ownerUserId`,** and hydrate themselves (including `?page` and `isOwn`). Class components only.
- **`isDefault` marks the landing tab** (`stories`), and the registry throws if a second tab claims it.
- **`ProfileTabPrivacy` is optional** and purely informative: it renders the owner-facing eye icon in the strip and the popover above the content. It does not affect visibility, and nothing enforces that a setting-gated tab declares it.

## Registry integrations

- **ModerationRegistry** (`Moderation` domain) — registers the `'profile'` topic with `ProfileSnapshotFormatter`.
- **SettingsPublicApi** (`Settings` domain) — registers a `Profile` tab (order 30), a `Privacy` section, and the `hide-comments-section` boolean parameter (default `false`). Constants `TAB_PROFILE`, `SECTION_PRIVACY`, and `KEY_HIDE_COMMENTS_SECTION` live on `ProfileServiceProvider`.
