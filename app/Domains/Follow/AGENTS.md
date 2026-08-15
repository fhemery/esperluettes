# Follow — agent notes

- README: [app/Domains/Follow/README.md](README.md)

## Public API

- [FollowPublicApi](Public/Api/FollowPublicApi.php) — the only entry point other domains may call. Exposes `getFollowerIds()` (for callers that need follower lists) and `canViewFollowingTab()` (used by the profile tab visibility contract). Do not reach into `Private/`.

## Events emitted

- `UserFollowed` (event name `Follow.UserFollowed`) — emitted by `FollowService::follow()` after the repository call. Payload: `followerId`, `followedId`.

## Listens to (wired in FollowServiceProvider)

- `Story::StoryCreated` → `StoryCreatedListener` — notifies followers when a new story is created with `public` or `community` visibility.
- `Story::StoryVisibilityChanged` → `StoryVisibilityChangedListener` — notifies followers when visibility transitions from `private` to `public` or `community` (covers the "publish later" path).
- `Auth::UserDeleted` → `RemoveFollowsOnUserDeleted` — deletes all rows where the deleted user is follower or followed.

## Registry integrations

- **ProfileTabRegistry** — registers tab key `following` (order 40), component `follow::following-tab`, visibility class `FollowingTabVisibility`, and optional `ProfileTabPrivacy` pointing at the hide setting.
- **NotificationFactory** — registers group `follow` (sort order 50) and types `NewFollowerNotification`, `NewStoryNotification`.
- **SettingsPublicApi** — registers `hide-following-tab` boolean (default `false`, i.e. tab visible) under Profile tab / Privacy section, inside `app->booted()` so Profile's tab exists first. Read/write with `FollowServiceProvider::TAB_PROFILE` + `::KEY_HIDE_FOLLOWING_TAB`. **True means hidden** from non-owner viewers.

## Non-obvious invariants

- **No FK to `users`.** `follower_id` and `followed_id` have no database foreign key. All cleanup is event-driven (`UserDeleted` removes both directions). Never add a cross-domain constraint.
- **`canViewFollowingTab()` requires a confirmed viewer for other people's profiles.** The owner always passes. Guests (`viewerUserId === null`) and users without `user-confirmed` never see another user's Following tab — even if the tab setting is public. This is separate from who may *follow* (both `user` and `user-confirmed` can follow via the routes).
- **New-story notifications only for readable visibilities.** Both listeners bail unless visibility is `public` or `community`. Private and beta-only stories never trigger follower notifications at creation or on visibility change unless they become public/community.
- **Community stories notify confirmed followers only.** `FollowNotificationService::notifyFollowersOfNewStory()` filters follower IDs to `user-confirmed` when `visibility === community`. Public stories notify all followers regardless of role.
- **Following tab is registry-owned, not Profile-owned.** Do not add a Following route or controller method in Profile — Follow registers `ProfileTabDefinition` from its own service provider. The tab component receives only `ownerUserId` and hydrates the following list itself.
- **Settings registration is idempotent.** `registerSettings()` returns early if the parameter already exists — safe on repeated boots / tests.
