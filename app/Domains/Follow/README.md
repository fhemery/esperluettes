# Follow Domain

## Purpose

The Follow domain lets authenticated users follow other users and see who they follow on a profile **Following** tab. Following is a one-way social link: the follower sees new public or community stories from authors they follow via notifications, and the followed user gets a new-follower notification.

Out of scope: follower counts as a public metric, mutual-follow detection, blocking, and feed aggregation beyond new-story notifications.

## Key concepts

- **Follow relationship** — a row linking `follower_id` → `followed_id`. A user cannot follow themselves. Duplicate follows are ignored at the repository layer (no second row).
- **Follow button** — `<x-follow::follow-button>` on another user's profile. Shown only when the viewer is logged in and viewing someone else's profile. Toggles via POST/DELETE on `/follow/{userId}`.
- **Following tab** — a profile tab (key `following`, order 40) listing the profiles the owner follows. Registered through `ProfileTabRegistry`; rendered by `<x-follow::following-tab>` with a single `ownerUserId` prop.
- **Tab visibility** — the owner always sees their own Following tab. Other viewers see it only when they are **confirmed** (`user-confirmed`) and the profile owner has not hidden the tab via Settings (`hide-following-tab` = true means hidden from others). Guests and unconfirmed users never see another user's Following tab.

## Architecture decisions

- **Standalone domain with one table.** Follow owns `follow_follows` and all follow/unfollow logic. Profile renders the tab shell; Follow owns the tab content and visibility rule.
- **No FK to `users`.** `follower_id` and `followed_id` are plain integers. Cross-domain FKs to Auth are prohibited; cleanup on account deletion is driven by the `UserDeleted` event listener.
- **Event-driven new-story notifications.** Follow does not poll Story. It listens to `StoryCreated` and `StoryVisibilityChanged` and notifies followers when a story becomes readable (public or community visibility).
- **Community stories filter followers by role.** When notifying about a community-visibility story, only followers with the `user-confirmed` role receive the notification — unconfirmed followers are excluded even though they may follow the author.

## Cross-domain delegation map

| Concern | Delegated to |
|---------|--------------|
| Profile tab registration, catch-all tab routing, tab strip visibility | `ProfileTabRegistry` (Profile domain) |
| Tab visibility rule implementation | `FollowingTabVisibility` → `FollowPublicApi::canViewFollowingTab` |
| `hide-following-tab` preference storage and settings UI section | `SettingsPublicApi` (registered under Profile tab, Privacy section) |
| New-follower and new-story notification delivery | `NotificationPublicApi` |
| Author/quoter display names, avatars, profile slugs for tab list and notifications | `ProfilePublicApi` (Shared contract) |
| Story visibility constants, story metadata for notifications | `StoryPublicApi` / `StoryVisibility` |
| Confirmed-user role checks for tab visibility and community-story notifications | `AuthPublicApi` |
| Domain event bus (emit `UserFollowed`, subscribe to Story/Auth events) | `EventBus` (Events domain) |
