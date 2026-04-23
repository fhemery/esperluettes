# Message Domain

Handles private messaging between users. Messages are dispatched by privileged users (admins, tech-admins, moderators) and delivered to one or more recipients. The domain tracks per-recipient delivery state (read/unread) independently of the message itself, so each recipient can delete their own copy without affecting others.

The domain is currently **incomplete**: compose is restricted to privileged roles; regular-user-to-user messaging is not yet implemented. See "Known Gaps" below.

---

## Key Concepts

### Message vs. Delivery

A `Message` record holds the shared content (title, body, sender, timestamp). A `MessageDelivery` record ties one message to one recipient and tracks that recipient's read state. This split allows:

- A single message to be sent to many recipients with one write.
- Each recipient to delete their own copy independently.
- Unread counts to be computed per user without scanning message content.

### Delivery ownership

Authorization is enforced at the delivery level, not the message level. A user can only view or delete deliveries where `user_id` matches their own ID. There are no shared inboxes.

### Unread count cache

`UnreadCounterService` caches each user's unread count under the key `message_unread_count_{userId}` with a 5-minute TTL. The cache is explicitly invalidated whenever a delivery is created (on dispatch) or marked as read (on show/destroy). Do not read `MessageDelivery` counts directly in views — always go through `UnreadCounterService`.

### Feature toggle

The entire messaging UI (icon in the navigation bar) is gated by the `messageactive` feature toggle (domain `message`, toggle name `active`). When the toggle is off, `MessageIconComponent` renders nothing. Routes themselves are not gated by the toggle — only the UI entry point is hidden.

### Compose access

Composing and sending messages is restricted to `admin`, `tech-admin`, and `moderator` roles. Regular authenticated users can only read and delete their own deliveries.

### Content sanitization

Message content is run through `Purifier::clean($content, 'strict')` before persistence. This strips script tags and other unsafe HTML. The `content` column stores the sanitized HTML.

### Reply threading

`Message` has a self-referencing `reply_to_id` that points to a parent message. Replies and reply trees can be fetched via `replyTo()` / `replies()`. The reply UI is declared in the language file but not yet fully implemented in the controller.

---

## Architecture Decisions

**No FK from `messages.sent_by_id` or `message_deliveries.user_id` to `users`.** Cross-domain foreign keys to the Auth domain's `users` table are prohibited by architecture rules. Sender and recipient user IDs are stored as plain `unsignedBigInteger` columns and resolved via `AuthPublicApi` when needed.

**Batch delivery insert.** `MessageDispatchService::dispatch()` builds an array of delivery rows and inserts them in a single `MessageDelivery::insert()` call for performance, rather than saving each delivery individually. The entire operation runs in a DB transaction.

**Recipient deduplication before insert.** `resolveRecipients()` applies `array_unique()` to merge explicit user IDs and role-based user IDs. Duplicates are removed before the batch insert to respect the `UNIQUE(message_id, user_id)` constraint on `message_deliveries`.

**`MessageIconComponent` is self-contained.** The Blade component resolves unread counts and display visibility in its own constructor. Do not pass these values from a controller — the component is intentionally autonomous so it can be embedded in any layout.

---

## Routes

All routes require `web`, `auth`, and `compliant` middleware. Prefix: `/messages`.

| Method | URI | Name | Access |
|--------|-----|------|--------|
| GET | `/messages` | `messages.index` | All authenticated users |
| GET | `/messages/{delivery}` | `messages.show` | All authenticated users |
| DELETE | `/messages/{delivery}` | `messages.destroy` | All authenticated users |
| GET | `/messages/compose` | `messages.compose` | Admin, Tech-Admin, Moderator |
| POST | `/messages` | `messages.store` | Admin, Tech-Admin, Moderator |

---

## Services

| Service | Responsibility |
|---------|---------------|
| `MessageDispatchService` | Create a message and batch-insert deliveries; resolve recipient IDs from explicit user IDs or role slugs |
| `MessageQueryService` | Paginated delivery listing for a user; find or delete a specific delivery |
| `UnreadCounterService` | Cached unread count per user; cache invalidation on state change |

---

## Database Schema

### `messages`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `title` | varchar(150) | Indexed |
| `content` | text | HTML, sanitized via Purifier strict profile |
| `sent_by_id` | unsignedBigInteger | No FK (cross-domain); sender's user ID |
| `sent_at` | timestamp | Nullable; set to `now()` on dispatch |
| `reply_to_id` | unsignedBigInteger | FK to `messages.id` ON DELETE CASCADE; nullable |
| `created_at` / `updated_at` | timestamps | Standard |
| `deleted_at` | timestamp | Soft deletes |

### `message_deliveries`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `message_id` | unsignedBigInteger | FK to `messages.id` ON DELETE CASCADE |
| `user_id` | unsignedBigInteger | No FK (cross-domain); recipient's user ID |
| `is_read` | boolean | Default `false` |
| `read_at` | timestamp | Nullable; set when `markAsRead()` is called |
| `created_at` / `updated_at` | timestamps | Standard |

Constraints: `UNIQUE(message_id, user_id)`, composite index on `(user_id, is_read)`.

---

## Cross-Domain Dependencies

| Domain | How it is used |
|--------|---------------|
| **Auth** | `AuthPublicApi::getUserIdsByRoles()` to resolve role-based recipients; `AuthPublicApi::hasAnyRole()` to check compose/display permissions; `Roles` constants for role names |
| **Config** | `ConfigPublicApi::isToggleEnabled('active', 'message')` to gate the nav icon |

---

## Known Gaps

The domain is explicitly described as incomplete. The following features are declared in the UI but not yet implemented:

- Regular-user-to-user messaging (compose is admin-only in v1).
- Reply workflow (the `reply_to_id` column and relationships exist; the controller has no reply action).
- Sender display name resolution (no cross-domain FK; the sender's name must be fetched separately via Profile or Auth APIs — no utility currently does this).
- No Filament admin panel resources for message moderation.
