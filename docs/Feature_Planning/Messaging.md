# Messaging Domain - Feature Planning

Status: Ready for Implementation (updated 2025-10-02)
Owner: TBD
Last updated: 2025-10-02

## Purpose
Allow admins to send messages to individual users, multiple users by role selection. All users can read messages, see unread counts, mark as read (by opening), and delete their messages.

## Glossary
- Message: An item with `title`, `content` (rich text), and metadata, delivered to one or more users.
- Delivery: The association of a Message to a specific recipient user, tracking read/deleted state and timestamps.

## Scope
### Core Features
- Web UI for admins to compose messages (title + rich text content with purifier profile: `strict`).
- Admin can target:
  - A specific user (through a searchable file on profile display_names)
  - A role
- Logged users see a mail icon in topbar (visible only if they have messages OR can send messages i.e., admins).
- Unread badge shows count on topbar icon (hidden when count=0).
- Messages list page showing all messages with unread styling (bold + red dot).
- Clicking a message navigates to `GET /messages/{delivery}`, reloads page showing list + selected message, marks as read.
- Logged users can delete their message deliveries (hard delete, per-recipient). Does not affect other recipients.
- Users can reply to the recipient, that will be the only one able to see the reply (apart from the person who sent it)

## Out of Scope (for now)
- Users can compose and send messages to friends only => No Networking/Friendship domain ready yet
- Real-time push (WebSockets) for counts; initial version can use polling or simple page load updates.
- Attachments and images in messages.
- Message reactions, threading, or replies (unless defined later).
- Moderation workflows beyond the admin sender.

---

## Architecture & Domain Placement
- New domain: `Message` (singular) under `app/Domains/Message/` following the Domain Oriented Architecture with the new Public/Private split (see `docs/Domain_Structure.md`).
  - Controllers should not access the database directly; use Services.
  - Database Migrations must live under `app/Domains/Message/Database/Migrations/`.
  - Use Policies for authorization and middleware for route protection where needed.

### Proposed Directory Structure (aligned with Public/Private split)
- `app/Domains/Message/`
  - `Database/`
    - `Migrations/`
    - `Seeders/`
  - `Private/`                       (internal to domain)
    - `Controllers/`                 (HTTP controllers)
    - `Models/`                      (`Message`, `MessageDelivery`)
    - `Policies/`
    - `Requests/`                    (Form Requests validation)
    - `Resources/`                   (domain assets and Blade)
      - `views/`                     (Blade templates, components/pages)
    - `Services/`                    (`MessageDispatchService`, `UnreadCounterService`, etc.)
    - `Views/`
      - `Components/`                (PHP class-based components)
    - `routes.php`                   (or `web.routes.php`/`api.routes.php` if split)
  - `Public/`                        (exposed to other domains)
    - `Contracts/`
      - `Dto/`
    - `Events/`
  - `Tests/`
    - `Unit/`
    - `Feature/`

---

## Data Model (v1)
- Model: `Message`
  - id (bigint)
  - title (string, max 150; indexed for search)
  - content (text; max 1000 chars; purified using `strict` profile)
  - sent_by_id (foreign key -> users.id)
  - sent_at (datetime)
  - reply_to_id (foreign key -> messages.id)
  - timestamps
  - soft deletes (optional; likely yes for moderation)

- Model: `MessageDelivery` (per-recipient state)
  - id (bigint)
  - message_id (fk -> messages.id, indexed)
  - user_id
  - is_read (boolean, default false)
  - read_at (datetime, nullable)
  - timestamps
  - Unique index on (message_id, user_id)

Notes:
- Broadcasting to “everyone” can be implemented either by:
  1) Creating a `Message` then creating `MessageDelivery` rows for all target users at dispatch time; or
  2) Lazy materialization on first read coupled with a global marker. For simplicity and unread counts correctness, start with eager creation (1) and optimize later if needed.

Indexes:
- `message_deliveries.user_id, message_deliveries.is_read (composite)` for unread queries
- Foreign key constraints on all fks internal to the domain (e.g. not sent_by_id, not user_id)

---

## Validation & Security
- Message content uses existing Purifier profile `strict` (same as for Stories).
- Use Form Requests for message creation and user deletion actions.
- Authorization:
  - v1: Only admins (role/permission) can create messages.
  - Users can only view/delete their own deliveries.
  - Users can reply to the message
- CSRF protection for forms; ensure authorization policy coverage.
 - Validation constraints:
   - `title`: required, string, max:150
   - `content`: required, string, max:1000 (stored as TEXT), purified with `strict` profile

---

## User Experience
### Topbar Unread Indicator
- Display a mail icon with unread count (e.g., red badge). Count = number of `MessageDelivery` for current user where `is_read=false`.
- Placement: inside logged navigation, before profile.
- Badge hidden when count=0 (do not display zero).
- Update count on page transitions; real-time updates are out of scope for v1.

### Messages List Page
- Route: e.g., `GET /messages` -> `MessageController@index`
- Shows list of `MessageDelivery` for current user, ordered by `created_at` or `sent_at` desc.
- Unread messages styled bold with a red dot indicator.
- The list page shows a default placeholder like “Select a message”.

### Message View
- Clicking a list item navigates to `GET /messages/{delivery}` and the page reload shows both the list and the selected message.
- Viewing marks it read (`is_read=true`, `read_at=now()`).

### Delete
- Users can delete a message delivery:
  - Permanent delete of the delivery record for that user (hard delete). Does not affect other recipients.

---

## Services
- `MessageDispatchService`
  - Validates target set
  - Creates `Message`
  - Resolves recipients from roles and explicit users
  - Creates `MessageDelivery` rows for recipients (eager creation, batch inserts)
  - Sets `sent_at`

- `UnreadCounterService`
  - Returns unread count for current user
  - Could be cached per user with invalidation on read/delete

- (Optional) `MessageReadService` and `MessageDeleteService` for clear separation.

---

## Routes & Policies
- User routes (web):
  - `GET /messages` -> `MessageController@index`
  - `GET /messages/{delivery}` -> `MessageController@show` (marks as read, reloads page with list + message)
  - `DELETE /messages/{delivery}` -> `MessageController@destroy`
- Admin routes (Filament handles admin UI). Ensure policies restrict creation to admins.
- Apply middleware: `auth` for all routes; possibly `verified` if required elsewhere.

---

## Performance Considerations
- Broadcast can be heavy: batch inserts for `MessageDelivery`.
- Add indexes as specified for fast unread count and list queries.
- Consider pagination for list view.
- Future: optimize broadcast using chunked jobs/queues.
 - Role targeting: resolving large roles should be chunked; consider queueing dispatch job if recipient set is large.

---

## Testing Strategy
- Feature tests:
  - Admin can create a message (title, content, targets) and deliveries are created.
  - User sees unread count and list shows unread styling.
  - Viewing marks as read; count decreases.
  - Deleting hides the message from the list and does not affect others.
  - "Everyone" broadcast creates deliveries for all users.
- Model tests:
  - Relationships: `Message` hasMany `MessageDelivery`, `MessageDelivery` belongsTo `Message` and `User`.
  - Constraints: unique (message_id, user_id).
- Policy tests for access control.

---

## Migration Plan (v1)
- Create `messages` table.
- Create `message_deliveries` table with unique (message_id, user_id).
- Seeders: none required initially.

---

## Integration Points
- Purifier config: use `strict` profile for all message content.
- Topbar integration: add unread counter in a shared layout/view (likely in Shared domain views/components). Keep Alpine.js simple per our Frontend Guidelines. Icon visible for: users with any messages OR admins (even with 0 messages). Badge shows count only when > 0.
- Notifications: no additional user notifications in v1 beyond the topbar badge.

---

## Implementation Notes Aligned with Project Rules
- Controllers thin; use services.
- Models singular with explicit relationships and `$fillable`.
- Migrations in domain path with indexes and FKs; include `down()` methods.
- Routes named and grouped appropriately; use route model binding for `MessageDelivery` where safe.
- Blade templates minimal logic, Tailwind classes, Alpine for interactivity.
- Use policies for authorization; middleware for protection.
- Performance: eager loading where appropriate, pagination, and caching for unread counts if needed.
