# Discord Bot Integration - Feature Planning

**Status**: Planning  
**Created**: 2025-10-02  
**Domain**: Discord (new domain)

## Overview

Integration of a Discord bot (written in TypeScript/JavaScript) with the Esperluettes website to enable user authentication, role synchronization, and activity notifications.

## Architecture Constraints

- **Website**: PHP/Laravel (no websockets available, no Redis)
- **Bot**: Behind firewall (website cannot initiate connections to bot)
- **Communication**: Bot → Website (pull model via REST API)
- **Bot Trust**: Bot is fully trusted; it authenticates via API key and provides Discord IDs
- **Polling Interval**: 1 minute (adjustable if needed)

## Key Decisions

### Authentication & Connection
- **Connection Type**: Website generates one-time codes displayed on user profile; user enters code in Discord bot command
- **Bot Authentication**: API key stored in environment variables
- **User Limit**: One Discord account per website user (and one website user per Discord account)
- **Connection Flow**: Synchronous (bot calls API with code, gets roles immediately)
- **Disconnection**: User initiates via Discord `/disconnect` command; bot calls API to disconnect (synchronous)
- **Connection Privacy**: Discord connection status is private information
- **Auditability**: Connection/disconnection emit application events (see Events) rather than being stored in a dedicated logs table

### Role Synchronization
- **Role Mapping**: Handled entirely by bot (not website's responsibility)
- **Website Role**: Website only returns user's current roles; bot handles Discord role assignment

### Notifications
- **Types**: User-targeted activity feed events only 
- **User Control**: Granular control via dedicated settings page (implementation details TBD)
- **Polling**: Bot polls every 1 minute for all pending notifications
- **Rate Limiting**: None on website side (bot handles delivery limits)
- **Delivery**: Bot fetches all notifications and dispatches as DMs to users

### Request Logging
- Bot API requests logged for investigation purposes

## Features

### Feature #1: User Authentication & Role Synchronization

**User Story**: As a Discord user, I want to link my Discord account to my website account so that my Discord roles reflect my website permissions.

**Flow**:
1. User visits their profile page on website (must be logged in)
2. Website generates a one-time connection code (e.g., `1258ac67`) valid for 5 minutes
3. Website displays command for user to copy: `/connect 1258ac67`
4. User types `/connect 1258ac67` in Discord
5. Bot calls `POST /api/discord/users` with `{ code: "1258ac67", discordId: "123456789", discordUsername: "DisplayName" }` (discordUsername is the discriminator-less display name)
7. Website returns user roles immediately: `{ success: true, userId: 123, roles: ["user", "author"] }`
8. Bot assigns corresponding Discord server roles (mapping handled by bot)
9. Website emits auditable/domain events for connection

**Disconnection Flow**:
1. User types `/disconnect` command in Discord
2. Bot calls `DELETE /api/discord/users/{discordId}`
3. Website removes Discord ID ↔ User ID mapping and clears any pending notifications for this user
4. Website returns success response
5. Website emits auditable/domain events for disconnection
6. Bot removes Discord server roles based on its own logic
7. User stops receiving Discord notifications immediately

### Feature #2: Activity Feed Notifications

**User Story**: As a user, I want to receive Discord DMs for activity feed updates so I stay informed without checking the website.
**Flow**:
1. User registers bot as contact on Discord
2. User configures Discord notification preferences in website settings page
3. When activity occurs on website, notification is queued in database
4. Bot polls website every 1 minute: `GET /api/discord/notifications/pending`
5. Website returns all pending notifications (paginated)
6. Bot sends DMs to users with queued notifications
7. Bot calls `POST /api/discord/notifications/mark-sent` with notification IDs
8. Website marks notifications as sent

**Notification Types** (all user-targeted, sent as DMs - implementation details TBD):
- New comment on user's story/chapter
- New reply to user's comment
- Someone mentioned user
- Someone followed user
- Someone liked user's story
- New chapter from followed author
- Moderation actions (story approved, rejected, etc.)
- Private messages
- Other activity feed events

**User Configuration**:
- Dedicated settings page for notification preferences
- Granular control over which types go to Discord
- Implementation details to be defined during development

## Technical Architecture

### Authentication & Security

**Time-limited Token Flow (Confirmed)**

**Bot Authentication**:
- All API requests require `Authorization: Bearer {API_KEY}` header
- API key stored in website's `.env` file: `DISCORD_BOT_API_KEY=...`
- Bot stores same key in its configuration

**User Connection Flow**:
1. User visits profile page on website (must be authenticated)
2. Website generates one-time code (e.g., `1258ac67`) valid for 5 minutes
3. Website displays command for user: `/connect 1258ac67`
4. User types command in Discord
5. Bot calls `POST /api/discord/users`
   - Headers: `Authorization: Bearer {API_KEY}`
   - Body: `{ code: "1258ac67", discordId: "123456789", discordUsername: "User#1234" }`
6. Website validates code and associates Discord ID with user
7. Website returns roles immediately: `{ success: true, userId: 123, roles: ["user", "author"] }`
8. Bot assigns Discord roles based on website roles (mapping handled by bot)

**Code Cleanup**:
- Expired codes (>5 minutes) are automatically deleted whenever we generate a new one
- Used codes are marked as consumed but kept for audit trail

### API Endpoints (Website provides)

#### Authentication Endpoints

**POST /api/discord/users**
- **Auth**: API key required
- **Request**: `{ code: string, discordId: string, discordUsername: string }`
- **Response**: `{ success: true, userId: int, roles: string[] }`
- **Purpose**: Connect Discord account using one-time code from website profile
- **Note**: Returns roles immediately (synchronous)

**DELETE /api/discord/users/{discordId}**
- **Auth**: API key required
- **Response**: `{ success: true, message: "Discord account disconnected" }`
- **Purpose**: Disconnect user (initiated by user via bot `/disconnect` command)

#### Role Sync Endpoints

**GET /api/discord/users/{discordId}**
- **Auth**: API key required
- **Response**: `{ userId: int, roles: string[] }`
- **Purpose**: Get current roles for connected Discord user (may include more fields later)

#### Notification Endpoints

**GET /api/discord/notifications/pending**
- **Auth**: API key required
- **Query params**: `?page=1&perPage=100`
- **Response**: `{ data: Notification[], pagination: {...} }`
- **Purpose**: Fetch all pending notifications across all users
- **Note**: Returns all pending, bot filters/dispatches as needed

**POST /api/discord/notifications/mark-sent**
- **Auth**: API key required
- **Request**: `{ notificationIds: int[] }`
- **Response**: `{ success: true, markedCount: int }`
- **Purpose**: Mark notifications as sent after bot delivers them

**Notification Object Structure**:

All notifications are user-targeted activity events:
```json
{
  "id": 125,
  "discordId": "123456789",
  "type": "comment",
  "data": {
    "message": "User X commented on your story Y",
    "url": "https://esperluettes.com/stories/slug/chapters/1#comment-5",
    "actor": "Username",
    "target": "Story Title"
  },
  "createdAt": "2025-10-02T11:05:00Z"
}
```

### Database Schema (Draft)

#### `discord_users` table
```sql
CREATE TABLE discord_users (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    discord_id VARCHAR(255) NOT NULL UNIQUE,
    discord_username VARCHAR(255) NOT NULL,
    connected_at TIMESTAMP NOT NULL,
    last_role_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_discord_id (discord_id)
);
```

#### `discord_connection_codes` table
```sql
CREATE TABLE discord_connection_codes (
    code VARCHAR(16) NOT NULL PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);
```

#### `discord_notifications` table
```sql
CREATE TABLE discord_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discord_user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    data JSON NOT NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (discord_user_id) REFERENCES discord_users(discord_user_id) ON DELETE CASCADE,
    INDEX idx_discord_user_sent (discord_user_id, sent_at),
    INDEX idx_sent_at (sent_at),
    INDEX idx_type (type)
);
```

#### Auditability (Events)
Connection/disconnection are auditable via application events, not a dedicated logs table. See Events section below.

### Website Components Needed

#### Domain Structure
- **New Domain**: `Discord`
- **Location**: `app/Domains/Discord/`

#### Components to Create

**API Controllers** (`app/Domains/Discord/Api/Controllers/`):
- `AuthController` - Handle auth flow
- `UserController` - Role sync
- `NotificationController` - Notification queue

**Services** (`app/Domains/Discord/Services/`):
- `DiscordAuthService` - Token generation, connection logic
- `DiscordNotificationService` - Queue management, notification creation
- `DiscordRoleService` - User role retrieval

**Models** (`app/Domains/Discord/Models/`):
- `DiscordUser` - Discord ↔ User mapping
- `DiscordAuthToken` - Temporary auth tokens (table name: `discord_connection_codes`)
- `DiscordNotification` - Notification queue

**Middleware** (`app/Domains/Discord/Middleware/`):
- `DiscordApiAuth` - Validate API key

**Migrations** (`app/Domains/Discord/Database/Migrations/`):
- `create_discord_users_table`
- `create_discord_connection_codes_table` (temporary auth codes)
- `create_discord_notifications_table`

**Views** (`app/Domains/Discord/Resources/views/`):
- `connect.blade.php` - User authorization page
- `settings/index.blade.php` - Discord settings page
- `settings/notifications.blade.php` - Notification preferences

**Events** (`app/Domains/Discord/Events/`):
- `DiscordConnected` - Fired when user connects (implements `AuditableEvent` and `DomainEvent` from `app/Domains/Events/Public/Contracts/`)
- `DiscordDisconnected` - Fired when user disconnects (implements `AuditableEvent` and `DomainEvent`)

**Listeners** (`app/Domains/Discord/Listeners/`):
- Various activity listeners to queue notifications

### Bot → Website Communication Pattern

**Confirmed Pattern**: Database-backed polling

**How it works**:
1. **Bot polls** website API every 1 minute (notifications only)
2. **Bot initiates** all HTTP requests (website never contacts bot)
3. **Website maintains** notification queue in MySQL database
4. **Bot authenticates** every request with API key
5. **Bot batches** notification fetching (100 per request, paginated)

**No Redis**: All queuing handled via MySQL `discord_notifications` table

**Polling Schedule**:
- **Notifications**: Every 1 minute → `GET /api/discord/notifications/pending`
- **Role Sync**: On-demand (bot requests roles as needed)
- **Auth**: Connect/Disconnect are synchronous calls; no notifications are generated for these actions

## Role Mapping

**Decision**: Role mapping is entirely handled by the bot, not the website.

**Website Responsibility**:
- Return user's current roles as string array: `["user", "author", "moderator", "admin"]`
- No knowledge of Discord role names or mapping logic

**Bot Responsibility**:
- Maintain mapping between website roles and Discord roles
- Assign/remove Discord roles based on website roles
- Handle edge cases (non-confirmed users, role conflicts, etc.)

## Notification Types

**All activity feed events** are eligible for Discord notifications, including:
- New comment on user's story/chapter
- New reply to user's comment  
- Someone mentioned user
- Someone followed user
- Someone liked user's story
- New chapter from followed author
- Moderation actions (story approved, rejected, etc.)
- Private messages (when messaging feature is implemented)
- Other activity feed events

**User Configuration**:
- Users have granular control via dedicated settings page
- Implementation details (UI, preferences storage) to be defined during development
- System will use event listeners to queue notifications based on user preferences

## Security Considerations

**API Security**:
- All endpoints require API key authentication via `Authorization: Bearer` header
- API key stored in `.env`: `DISCORD_BOT_API_KEY`
- Rate limiting on API endpoints (Laravel throttle middleware)
- Validate Discord IDs format (numeric string, 17-19 digits)

**Token Security**:
- Auth tokens expire after 5 minutes
- Tokens are single-use (marked as consumed)
- Expired tokens automatically cleaned up
- Random token generation (cryptographically secure)

**User Privacy**:
- Discord connection status is **private** (not shown on public profile)
- Connection/disconnection events are logged in `discord_connection_logs`
- Users must explicitly authorize connection on website
- Users can disconnect at any time from settings page

**GDPR Compliance**:
- Store only necessary Discord data (ID, username)
- Allow users to disconnect and delete their Discord data
- Log connections for security/debugging
- Include Discord data in user data export requests

**Auditability**:
- Emit `DiscordConnected`/`DiscordDisconnected` domain/auditable events with relevant context (user id, discord id)
- Log bot API requests (consider middleware logging)
- Track notification delivery status

## Open Questions

### Notification Preference Storage

**Question**: How should user notification preferences be stored?

**Option A**: JSON column on `discord_users` table
```json
{
  "enabled": true,
  "preferences": {
    "comment": true,
    "mention": true,
    "follow": true,
    "like": false,
    "chapter": true,
    "moderation": true,
    "message": true
  }
}
```

**Option B**: Separate `discord_notification_preferences` table
```sql
CREATE TABLE discord_notification_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discord_user_id BIGINT UNSIGNED NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    enabled BOOLEAN DEFAULT true,
    UNIQUE KEY (discord_user_id, notification_type)
);
```

**Status**: Too early to decide, depends on full notification system design.

## Architectural Decisions Made

### Synchronous Connect/Disconnect
**Decision**: Connect and disconnect are synchronous API operations. No notifications are queued for these actions. Application emits auditable/domain events instead.

## Next Steps

1. **Create API documentation**: Detailed docs for bot developer (separate file)
2. **Define notification preferences schema**: How are user preferences stored? Per-type toggles?
3. **Create user stories**: Break down implementation into manageable tasks
4. **Design UI**: Settings pages for connection and notification preferences
5. **Implementation**: 
   - Database migrations (no connection logs table; use events)
   - Models and relationships
   - Services and business logic
   - API controllers and routes
   - Middleware for authentication
   - Settings pages (views + controllers)
   - Event/listener infrastructure (emit `AuditableEvent` + `DomainEvent` for connect/disconnect)
7. **Testing**: Unit tests for services, feature tests for API endpoints
8. **Bot development**: Separate repository/project

## API Rate Limits (Proposed)

- `POST /api/discord/users`: 100 requests/minute per IP
- `GET /api/discord/notifications/pending`: 120 requests/hour (1 per minute)
- `POST /api/discord/notifications/mark-sent`: 120 requests/hour
- `GET /api/discord/users/{discordId}`: 300 requests/hour
- `DELETE /api/discord/users/{discordId}`: 100 requests/minute per IP

## UI Component (Profile Integration)

- **Goal**: Keep Profile decoupled from Discord domain. Provide a reusable component that encapsulates display and interaction.
- **Class-based Component**: `app/Domains/Discord/Private/Views/Components/DiscordComponent.php`
  - Renders Discord status (disconnected: grey icon; connected: blue icon with user id and a small disconnect icon)
  - Opens a popover/modal with instructions
  - Triggers code generation via AJAX (Alpine) when user initiates connect flow
  - When connected, shows the disconnect command/instructions
- **Blade (anonymous) view**: `app/Domains/Discord/Private/Resources/views/components/discord-component.blade.php`
- **Notes**:
  - Component fetches all required Discord-related data/logic internally (Profile stays dumb)
  - Uses synchronous API connect/disconnect flows

## BDD Test Plan (First Scenarios)

- **Location**: `app/Domains/Discord/Tests/Feature/Api/AuthConnectAuthTest.php`
- **Scenario 1**: POST `/api/discord/auth/connect` without `Authorization` header → returns 401 JSON `{ error: "Unauthorized", message: "Invalid API key" }`
- **Scenario 2**: POST with invalid API key → returns 401
- (Later) **Scenario 3**: POST with valid API key but invalid payload → 400 with validation errors
- (Later) **Scenario 4**: Happy path connect → 200 with `{ success: true, userId, roles: [...] }`

Use Laravel throttle middleware according to proposed limits for these endpoints.

