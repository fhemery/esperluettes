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
- Expose a **public API** consumed by the Profile domain for the follow/unfollow button state and the Following tab rendering.
- Register its own **profile preference** through the settings system for the tab visibility toggle.

---

## Technical Architecture

### Domain Structure

```
Follow/
├── Public/
│   ├── Api/
│   │   └── FollowPublicApi.php          # isFollowing, follow, unfollow, getFollowing, getFollowerIds
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
│   │   └── StoryPublishedListener.php   # listens to Story events, fans out story notifications
│   ├── Notifications/
│   │   ├── NewFollowerNotification.php  # "X vous suit"
│   │   └── NewStoryNotification.php     # "X a créé une nouvelle histoire"
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

Two new notification content types registered with the Notification domain factory:

| Type key               | Content class            | Recipient       |
|------------------------|--------------------------|-----------------|
| `follow.new_follower`  | `NewFollowerNotification`| followed user   |
| `follow.new_story`     | `NewStoryNotification`   | each follower   |

### Story Event Integration

The Follow domain listens to one or two events from the Story domain:

- **StoryCreated** (or equivalent): if visibility is `public` or `community`, trigger story notifications.
- **StoryVisibilityChanged** (to be confirmed with Story domain): if new visibility is `public` or `community` and old was `private`, trigger story notifications.

If the Story domain does not yet dispatch a visibility change event, one should be added as part of this implementation.

### Profile Integration

- The Profile domain consumes `FollowPublicApi` to:
  - Render the follow/unfollow button (with correct state).
  - Render the Following tab content.
  - Check the privacy preference to conditionally show the tab.
- The privacy preference (`follow.hide_following_tab`) is registered through the existing settings system.

### Access Control for Story Notifications

When fanning out story notifications for a `community` story, the Follow domain must check that each follower is an authenticated community member. This check should be performed via an existing `Auth` or `Member` domain public API (e.g., `isMember(userId)`).
