# Member Follow - Feature Planning

## Overview

The Member Follow feature allows logged-in users to follow other members. Following serves one purpose: being notified when a followed author publishes a new story. The feature includes a follow button on profiles, two notification types, and a public "Following" tab on the user profile page.

**Target users**: Logged-in users only  
**Status**: Planning phase

---

## Functional Summary

### 1. Follow / Unfollow

- The follow button is shown on **other users' profiles only** (never on one's own profile).
- **Logged-out users**: no button displayed.
- **Initial state (not following)**: button labeled "Suivre".
- **Following state**: button labeled "Suivi" with a checkmark icon. Clicking it **unfollows** the user silently (no notification sent).
- Following is **always allowed** — there is no blocking mechanism.

### 2. Notifications

#### "New follower" notification
- Triggered each time a user follows another member, including if they previously unfollowed and re-follow.
- Sent to the **followed user**.
- Content: `"<xxx> vous suit"` with the follower's avatar as the notification icon.
- No notification is sent on unfollow.

#### "New story" notification
- Triggered when a followed author:
  - Creates a new story with visibility `public` or `community`, **or**
  - Changes an existing story's visibility from `private` to `public` or `community`.
- Sent to **all followers of the author who have access to the story**:
  - `public` stories → notify all followers.
  - `community` stories → notify only followers who are **members** (authenticated community members).
- Content: `"<xxx> a créé une nouvelle histoire"` followed by a link to the story with its title.
- One notification per story creation/publication event (not per chapter).
- No opt-out mechanism in this phase (deferred to future notification preferences work).

### 3. "Following" Tab on Profile

- A new tab is added to every user's profile page showing the list of users they follow.
- **Visibility rules**:
  - Not visible to logged-out users.
  - Not visible if the profile owner has enabled the privacy preference to hide it.
  - Visible to any logged-in user otherwise.
- **Content**: avatar + display name only, each entry is a clickable link to the followed user's profile.
- No follower/following counts displayed anywhere on the profile.

### 4. Privacy Preference

- A new toggle is added in the **Profile settings tab**: "Masquer ma liste d'abonnements" (or similar).
- Default: **off** (tab is visible to logged-in users).
- When enabled, the Following tab is hidden from all other users (including the profile owner when viewing their own profile via another user's session — though it remains accessible from their own profile settings).

---

## Out of Scope (Future Phases)

- **Followers tab**: showing who follows you. Identified as a likely future feature.
- **Follower/following counts** in the profile header.
- **Notification opt-out** by type (deferred to the dedicated notification preferences work).
- **Blocking** a follower.
- **Email digests** or external notifications (Discord, etc.).

---

## Domain Architecture Recommendation

This feature should live in a **dedicated `Follow` domain**, for the following reasons:

1. **Its own data model**: The follow relationship (`follower_id` → `followed_id`) belongs to neither Profile nor Story.
2. **Cross-domain logic**: It must react to Story events and send notifications via the Notification domain, while also adding UI to Profile. Putting it in either Profile or Story would create an undesirable coupling between the two.
3. **Future growth**: A dedicated domain cleanly accommodates the followers tab, opt-outs, and other future follow-related features without polluting other domains.

The Follow domain will:
- Own the `follow` table and its repository.
- Listen to **Story domain events** (story created / visibility changed) to dispatch story notifications.
- Use the **Notification domain's public API** to create notifications.
- Expose two **PHP Blade components** (`FollowButton`, `FollowingTab`) registered under the `follow` component namespace, consumed directly by Profile domain views. Each component fetches its own data internally via Follow domain services.
- Register its own **profile preference** through the settings system for the tab visibility toggle, under the existing `profile` tab and `privacy` section (using hardcoded string IDs to avoid a code dependency on the Profile domain).

---

## Technical Architecture

### Domain Structure

```
Follow/
├── Public/
│   ├── Api/
│   │   └── FollowPublicApi.php          # getFollowerIds (minimal public surface)
│   ├── Events/
│   │   └── UserFollowed.php             # dispatched after a follow action
│   └── Providers/
│       └── FollowServiceProvider.php
├── Private/
│   ├── Controllers/
│   │   └── FollowController.php         # POST/DELETE follow endpoints
│   ├── Models/
│   │   └── Follow.php                   # follower_id, followed_id, created_at
│   ├── Services/
│   │   ├── FollowService.php            # follow/unfollow logic, checks
│   │   └── FollowNotificationService.php# builds and dispatches notifications
│   ├── Listeners/
│   │   ├── StoryCreatedListener.php     # fans out story notifications on creation
│   │   └── StoryVisibilityChangedListener.php # fans out on private→public/community
│   ├── Notifications/
│   │   ├── NewFollowerNotification.php  # "Une Esperluette vous suit"
│   │   └── NewStoryNotification.php     # "Une Esperluette que vous suivez a publié..."
│   ├── Views/
│   │   └── Components/
│   │       ├── FollowButton.php         # follow/unfollow button; fetches own state
│   │       └── FollowingTab.php         # following list tab; fetches own data, checks privacy pref
│   └── routes.php
├── Database/
│   └── Migrations/
│       └── xxxx_create_follow_follows_table.php
└── Tests/
    └── Feature/
        ├── FollowTest.php
        ├── UnfollowTest.php
        ├── NewFollowerNotificationTest.php
        ├── NewStoryNotificationTest.php
        └── FollowingTabTest.php
```

### Database

**Table: `follow_follows`**

| Column        | Type        | Notes                    |
|---------------|-------------|--------------------------|
| `id`          | bigint PK   |                          |
| `follower_id` | bigint FK   | → `users.id`             |
| `followed_id` | bigint FK   | → `users.id`             |
| `created_at`  | timestamp   |                          |

- Unique constraint on `(follower_id, followed_id)`.
- Indexed on `followed_id` (for fan-out on story creation) and `follower_id` (for the Following tab).

### Notification Types

One new notification group and two content types, registered with the Notification domain factory in `FollowServiceProvider::boot()`:

**Group:**

| Group key | Display name |
|-----------|-------------|
| `follow`  | "Suivi"      |

**Types:**

| Type key               | Content class            | Recipient       | Content |
|------------------------|--------------------------|-----------------|---------|
| `follow.new_follower`  | `NewFollowerNotification`| followed user   | "Une Esperluette vous suit" |
| `follow.new_story`     | `NewStoryNotification`   | each follower   | "Une Esperluette que vous suivez a publié une nouvelle histoire" |

### Story Event Integration

The Follow domain listens to two confirmed events from the Story domain (both carry a `StorySnapshot` which includes the `visibility` field):

- **`StoryCreated`**: if `StorySnapshot::visibility` is `public` or `community`, fan out story notifications.
- **`StoryVisibilityChanged`**: carries `oldVisibility` and `newVisibility`; trigger fan-out only when `newVisibility` is `public` or `community` and `oldVisibility` was `private`.

### Profile Integration

The Profile domain does **not** call `FollowPublicApi` directly. Instead, it embeds two PHP Blade components exposed by the Follow domain:

- `<x-follow::follow-button :userId="$profileUser->id" />` — renders the follow/unfollow button with correct state for the authenticated user.
- `<x-follow::following-tab :userId="$profileUser->id" />` — renders the Following tab list; internally checks the privacy preference and conditionally renders or hides content.

The components access Follow domain services directly (same domain), keeping the Profile domain free of Follow business logic.

The privacy preference (`follow.hide_following_tab`) is registered in `FollowServiceProvider::boot()` via `SettingsPublicApi`, under tab `'profile'` and section `'privacy'` (hardcoded string IDs matching `ProfileServiceProvider::TAB_PROFILE` and `SECTION_PRIVACY`, without importing Profile domain classes).

### Access Control for Story Notifications

When fanning out story notifications for a `community` story, the Follow domain must filter followers to confirmed members only. Use `AuthPublicApi::getRolesByUserIds(array $followerIds)` and keep only those whose roles include `Roles::USER_CONFIRMED`.
