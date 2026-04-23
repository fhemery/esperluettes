# Profile Domain

Manages the public-facing user profile: display name, avatar/picture, bio description, social network handles, and privacy settings. Each authenticated user has exactly one profile row, created automatically on registration.

## Responsibilities

- Profile creation on user registration (generates a deterministic SVG avatar)
- Profile editing: display name, bio, profile picture upload/removal, social links
- Profile display: tab-based public page (stories, about, comments tabs)
- Avatar generation and URL resolution (custom upload or generated SVG fallback)
- Profile slug management (derived from display name, used in URLs)
- Profile caching (10-minute TTL, invalidated on every mutation)
- Privacy settings: profile owners can hide their comments section via Settings
- Moderation actions: moderators can remove avatar, clear bio, or clear social handles
- User lifecycle: soft-delete on deactivation, restore on reactivation, hard-delete on account deletion
- Public API contract (`ProfilePublicApi`) used by other domains to resolve profile data by user ID or slug

## Table

`profile_profiles` — one row per user.

| Column | Type | Notes |
|---|---|---|
| `user_id` | unsigned big int | Primary key (no auto-increment), no FK (cross-domain FK prohibited) |
| `slug` | string | URL-safe identifier derived from display name |
| `display_name` | string | Not null; backfilled on migration |
| `profile_picture_path` | string | Nullable; relative path on `public` disk |
| `description` | text | Nullable; HTML-sanitized via `clean()` on save |
| `facebook_handle` | string | Nullable; stored as handle only, URL constructed at render time |
| `x_handle` | string | Nullable |
| `instagram_handle` | string | Nullable |
| `youtube_handle` | string | Nullable |
| `tiktok_handle` | string | Nullable |
| `bluesky_handle` | string | Nullable |
| `mastodon_handle` | string | Nullable; expects `user@instance` format |
| `deleted_at` | timestamp | Soft deletes (added 2025-10-17) |
| `created_at` / `updated_at` | timestamps | Standard Laravel timestamps |

## Directory Structure

```
Profile/
  Public/
    Events/
      AvatarChanged.php              # Emitted when avatar is uploaded or removed
      AvatarModerated.php            # Emitted when moderator removes avatar
      AboutModerated.php             # Emitted when moderator clears bio
      BioUpdated.php                 # Emitted when bio or social handles change
      ProfileDisplayNameChanged.php  # Emitted when display name is changed
      SocialModerated.php            # Emitted when moderator clears social handles
    Providers/
      ProfileServiceProvider.php     # Main service provider; registers all bindings
  Private/
    Api/
      ProfileApi.php                 # Implements ProfilePublicApi contract
    Controllers/
      ProfileController.php          # Show / edit / update own profile; tab routing
      ProfileLookupController.php    # JSON endpoints for UI component profile search
      ProfileModerationController.php# Moderation actions (remove image, empty bio/social)
    Listeners/
      CreateProfileOnUserRegistered.php
      ClearProfileCacheOnEmailVerified.php
      RemoveProfileOnUserDeleted.php
      SoftDeleteProfileOnUserDeactivated.php
      RestoreProfileOnUserReactivated.php
    Models/
      Profile.php                    # Eloquent model; primary key = user_id; uses SoftDeletes
    Requests/
      UpdateProfileRequest.php       # Validates profile edit form
      UploadProfilePictureRequest.php
    Resources/
      lang/fr/                       # French translations (show, edit, fields, settings, ...)
      views/
        components/
          about-panel.blade.php
          inline-names.blade.php
          profile-and-role-picker.blade.php
        pages/
          show.blade.php             # Public profile page (tabs: stories, about, comments)
          edit.blade.php             # Profile edit form
    Services/
      ProfileService.php             # Core business logic (CRUD, avatar, slug, caching)
      ProfileAvatarUrlService.php    # Resolves public avatar URL with SVG fallback
      ProfileCacheService.php        # Cache layer keyed by user_id (TTL: 10 min)
      ProfilePrivacyService.php      # Comments tab visibility logic
    Support/
      AvatarGenerator.php            # Deterministic SVG avatar generation from initials
      Moderation/
        ProfileSnapshotFormatter.php # Moderation snapshot capture and render
    routes.php
  Database/
    Migrations/
      2025_08_08_153258_create_profile_profiles_table.php
      2025_08_09_205300_add_slug_to_profile_profiles_table.php
      2025_08_18_000000_add_display_name_to_profile_profiles.php
      2025_08_18_000001_backfill_display_name_and_make_not_null.php
      2025_08_26_081455_backfill_profile_profile_slugs.php
      2025_10_17_153500_add_soft_deletes_to_profile_profiles_table.php
      2026_03_01_000001_rename_social_url_to_handle_and_add_new_networks.php
      2026_03_01_000002_backfill_strip_base_urls_from_social_handles.php
  Tests/
    Feature/
      ProfileShowTest.php
      ProfileEditTest.php
      ProfileLookupTest.php
      ProfileModerationEmptyImageTest.php
      ProfileModerationEmptyAboutTest.php
      ProfileModerationEmptySocialTest.php
      ProfilePrivacySettingsTest.php
      ProfileTabsRoutingTest.php
      ProfilePublicApiTest.php
      CreateProfileOnRegistrationTest.php
      UserDeactivatedSoftDeleteTest.php
      UserReactivatedRestoreTest.php
      UserDeletedCleanupTest.php
    Unit/
      ProfileSnapshotFormatterTest.php
    helpers.php
```

## Public API

The domain exposes `ProfilePublicApi` (contract in `Shared`), bound in `ProfileServiceProvider`. Implemented by `ProfileApi`.

| Method | Description |
|---|---|
| `getPublicProfile(int $userId): ?ProfileDto` | Returns slim profile DTO (id, display name, slug, avatar URL) |
| `getPublicProfileBySlug(string $slug): ?ProfileDto` | Looks up by slug |
| `getPublicProfiles(array $userIds): array` | Batch lookup; returns `[user_id => ProfileDto\|null]` |
| `getFullProfile(int $userId): ?FullProfileDto` | Returns full profile including join date and roles |
| `searchDisplayNames(string $query, int $limit): array` | Returns `[user_id => display_name]` map |
| `searchPublicProfiles(string $query, int $limit): array` | Returns `['items' => ProfileSearchResultDto[], 'total' => int]` |
| `canViewComments(int $profileUserId, ?int $viewerUserId): bool` | Privacy gate for comments tab |

## Events Emitted

| Event | Event name | When |
|---|---|---|
| `AvatarChanged` | `Profile.AvatarChanged` | Avatar uploaded or removed by the profile owner |
| `BioUpdated` | `Profile.BioUpdated` | Description or any social handle changed |
| `ProfileDisplayNameChanged` | `Profile.DisplayNameChanged` | Display name (and slug) changed |
| `AvatarModerated` | `Profile.AvatarModerated` | Moderator removed the avatar |
| `AboutModerated` | `Profile.AboutModerated` | Moderator cleared the bio |
| `SocialModerated` | `Profile.SocialModerated` | Moderator cleared social handles |

## Listens To

| Event | Listener | Action |
|---|---|---|
| `Auth::UserRegistered` | `CreateProfileOnUserRegistered` | Creates profile row and generates default SVG avatar |
| `Auth::EmailVerified` | `ClearProfileCacheOnEmailVerified` | Invalidates the profile cache so role changes are reflected |
| `Auth::UserDeleted` | `RemoveProfileOnUserDeleted` | Hard-deletes profile row and cleans up avatar files |
| `Auth::UserDeactivated` | `SoftDeleteProfileOnUserDeactivated` | Soft-deletes the profile |
| `Auth::UserReactivated` | `RestoreProfileOnUserReactivated` | Restores the soft-deleted profile |

## Routes

All routes are prefixed `/profile`.

| Method | URI | Name | Access |
|---|---|---|---|
| GET | `/profile/` | `profile.show.own` | Authenticated users (`user`, `user-confirmed`) |
| GET | `/profile/edit` | `profile.edit` | Authenticated users (`user`, `user-confirmed`) |
| PUT | `/profile/` | `profile.update` | Authenticated users (`user`, `user-confirmed`) |
| GET | `/profile/{slug}` | `profile.show` | Public |
| GET | `/profile/{slug}/stories` | `profile.show.stories` | Public |
| GET | `/profile/{slug}/about` | `profile.show.about` | Authenticated + compliant |
| GET | `/profile/{slug}/comments` | `profile.show.comments` | `user-confirmed` + compliant |
| POST | `/profile/{slug}/moderation/remove-image` | `profile.moderation.remove-image` | Moderator / Admin |
| POST | `/profile/{slug}/moderation/empty-about` | `profile.moderation.empty-about` | Moderator / Admin |
| POST | `/profile/{slug}/moderation/empty-social` | `profile.moderation.empty-social` | Moderator / Admin |
| GET | `/profile/lookup` | `profiles.lookup` | Authenticated + compliant (throttle 60/min) |
| GET | `/profile/lookup/by-ids` | `profiles.lookup.by_ids` | Authenticated + compliant (throttle 60/min) |

## Registry Integrations

- **ModerationRegistry** (`Moderation` domain) — registers the `'profile'` topic with `ProfileSnapshotFormatter`.
- **SettingsPublicApi** (`Settings` domain) — registers a `Profile` tab (order 30) with a `Privacy` section and the `hide-comments-section` boolean parameter (default `false`).

## Key Design Decisions

- **No FK to `users`.** Cross-domain foreign keys to the Auth domain's `users` table are prohibited. User lifecycle is managed entirely via domain events.
- **Social handles, not full URLs.** Since March 2026, columns store only the handle/username. Full profile URLs are constructed at render time by `Profile::socialUrl()`.
- **Mastodon handle format.** Expected as `user@instance` (with or without leading `@`). The URL is built as `https://{instance}/@{user}`.
- **Avatar fallback.** If `profile_picture_path` is null, the URL falls back to `profile_pictures/{user_id}.svg`, a deterministic SVG generated at registration and stored on the `public` disk.
- **Description sanitization.** The `description` field is sanitized with `clean($value, 'strict')` in `ProfileService` before saving, not in the form request.
- **Slug derivation.** Slugs are generated from the display name via `Shared\Support\SimpleSlug::normalize()`. They change whenever the display name changes.
- **Cache key.** Profile cache is keyed as `profile_by_user_id:{user_id}` with a 10-minute TTL. The cache distinguishes a cache miss (`false`) from an explicitly cached null (known missing profile).
