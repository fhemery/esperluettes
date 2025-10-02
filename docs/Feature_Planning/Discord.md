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
- **Connection Type**: Ephemeral token flow (5-minute validity) for initial connection, then permanent Discord ID ↔ User ID mapping
- **Bot Authentication**: API key stored in environment variables
- **User Limit**: One Discord account per website user
- **Disconnection**: Users can disconnect from website UI; this stops notifications immediately
- **Connection Privacy**: Discord connection status is private information
- **Audit Logging**: Connection/disconnection events are logged for investigation

### Role Synchronization
- **Role Mapping**: Handled entirely by bot (not website's responsibility)
- **Website Role**: Website only returns user's current roles; bot handles Discord role assignment

### Notifications
- **Types**: Bot-targeted (connect/disconnect) + User-targeted (activity feed events)
- **User Control**: Granular control via dedicated settings page (implementation details TBD)
- **Polling**: Bot polls every 1 minute for all pending notifications
- **Rate Limiting**: None on website side (bot handles delivery limits)
- **Delivery**: Bot fetches all notifications, handles bot-targeted ones directly, dispatches user-targeted ones as DMs
- **Unified Queue**: Single notification queue handles both bot and user notifications

### Request Logging
- Bot API requests logged for investigation purposes

## Features

### Feature #1: User Authentication & Role Synchronization

**User Story**: As a Discord user, I want to link my Discord account to my website account so that my Discord roles reflect my website permissions.

**Flow**:
1. User types `/connect` command in Discord
2. Bot initiates auth flow: `POST /api/discord/auth/init` with API key
3. Website returns temporary token (5-min validity) and connection URL
4. Bot sends URL to user via Discord DM
5. User opens URL in browser
6. Website authenticates user (or shows "already logged in" page)
7. User authorizes Discord connection
8. Website stores Discord ID ↔ User ID mapping
9. Website queues a **bot-targeted notification** (type: `bot.user_connected`)
10. Bot polls `GET /api/discord/notifications/pending` and receives connection notification
11. Bot extracts user roles from notification data
12. Bot assigns corresponding Discord server roles (mapping handled by bot)

**Disconnection Flow**:
1. User clicks "Disconnect Discord" button on website settings page
2. Website queues a **bot-targeted notification** (type: `bot.user_disconnected`)
3. Website removes Discord ID ↔ User ID mapping
4. Website fires disconnection event (logged)
5. User stops receiving user-targeted Discord notifications immediately
6. Bot polls and receives disconnection notification
7. Bot handles Discord role removal based on its own logic

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

**Notification Types**:

**Bot-Targeted Notifications** (system events for bot):
- `bot.user_connected` - User successfully connected their Discord account
- `bot.user_disconnected` - User disconnected their Discord account

**User-Targeted Notifications** (sent as DMs to users - implementation details TBD):
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
1. Bot calls `POST /api/discord/auth/init` with Discord user data
   - Headers: `Authorization: Bearer {API_KEY}`
   - Body: `{ discord_id: "123456789", discord_username: "User#1234" }`
2. Website generates unique token (5-minute expiration)
3. Website returns: `{ token: "abc123", expires_at: "2025-10-02T11:15:00Z", url: "https://esperluettes.com/discord/connect/abc123" }`
4. Bot sends URL to user via Discord DM
5. User visits URL, authenticates (if needed), authorizes connection
6. Website stores Discord ID ↔ User ID mapping permanently (until disconnect)
7. Bot polls `GET /api/discord/auth/status/{token}` every 2-3 seconds
8. Once connected, API returns: `{ status: "connected", user_id: 123, roles: ["user", "author", "moderator"] }`
9. Bot assigns Discord roles based on website roles (mapping handled by bot)

**Token Cleanup**:
- Expired tokens (>5 minutes) are automatically deleted
- Used tokens are marked as consumed but kept for audit trail

### API Endpoints (Website provides)

#### Authentication Endpoints

**POST /api/discord/auth/init**
- **Auth**: API key required
- **Request**: `{ discordId: string, discordUsername: string }`
- **Response**: `{ token: string, expiresAt: datetime, url: string }`
- **Purpose**: Initialize connection flow, generate temporary token and URL for user

**DELETE /api/discord/users/{discordId}**
- **Auth**: API key required
- **Response**: `{ success: true, message: "Discord account disconnected" }`
- **Purpose**: Bot-initiated disconnection (if needed)

#### Role Sync Endpoints

**GET /api/discord/users/{discordId}/roles**
- **Auth**: API key required
- **Response**: `{ userId: int, discordId: string, roles: string[], lastUpdated: datetime }`
- **Purpose**: Get current roles for connected Discord user

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

**Bot-Targeted Notification (user connected)**:
```json
{
  "id": 123,
  "discordId": "123456789",
  "type": "bot.user_connected",
  "data": {
    "userId": 456,
    "discordUsername": "User#1234",
    "roles": ["user", "author"],
    "connectedAt": "2025-10-02T11:05:00Z"
  },
  "createdAt": "2025-10-02T11:05:00Z"
}
```

**Bot-Targeted Notification (user disconnected)**:
```json
{
  "id": 124,
  "discordId": "123456789",
  "type": "bot.user_disconnected",
  "data": {
    "userId": 456,
    "discordUsername": "User#1234",
    "disconnectedAt": "2025-10-02T11:10:00Z"
  },
  "createdAt": "2025-10-02T11:10:00Z"
}
```

**User-Targeted Notification (activity)**:
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
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_discord_id (discord_id)
);
```

#### `discord_auth_tokens` table
```sql
CREATE TABLE discord_auth_tokens (
    token VARCHAR(64) NOT NULL PRIMARY KEY,
    discord_id VARCHAR(255) NOT NULL,
    discord_username VARCHAR(255) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    status ENUM('pending', 'connected', 'expired') DEFAULT 'pending',
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_token (token),
    INDEX idx_status (status),
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
    
    FOREIGN KEY (discord_user_id) REFERENCES discord_users(id) ON DELETE CASCADE,
    INDEX idx_discord_user_sent (discord_user_id, sent_at),
    INDEX idx_sent_at (sent_at),
    INDEX idx_type (type)
);
```

#### `discord_connection_logs` table
```sql
CREATE TABLE discord_connection_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    discord_id VARCHAR(255) NOT NULL,
    action ENUM('connected', 'disconnected') NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_discord_id (discord_id),
    INDEX idx_action (action)
);
```

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
- `DiscordAuthToken` - Temporary auth tokens
- `DiscordNotification` - Notification queue
- `DiscordConnectionLog` - Audit trail

**Middleware** (`app/Domains/Discord/Middleware/`):
- `DiscordApiAuth` - Validate API key

**Migrations** (`app/Domains/Discord/Database/Migrations/`):
- `create_discord_users_table`
- `create_discord_auth_tokens_table`
- `create_discord_notifications_table`
- `create_discord_connection_logs_table`

**Views** (`app/Domains/Discord/Resources/views/`):
- `connect.blade.php` - User authorization page
- `settings/index.blade.php` - Discord settings page
- `settings/notifications.blade.php` - Notification preferences

**Events** (`app/Domains/Discord/Events/`):
- `DiscordConnected` - Fired when user connects
- `DiscordDisconnected` - Fired when user disconnects

**Listeners** (`app/Domains/Discord/Listeners/`):
- `LogDiscordConnection` - Log connection events
- Various activity listeners to queue notifications

### Bot → Website Communication Pattern

**Confirmed Pattern**: Database-backed polling

**How it works**:
1. **Bot polls** website API every 1 minute
2. **Bot initiates** all HTTP requests (website never contacts bot)
3. **Website maintains** notification queue in MySQL database
4. **Bot authenticates** every request with API key
5. **Bot batches** notification fetching (100 per request, paginated)

**No Redis**: All queuing handled via MySQL `discord_notifications` table

**Polling Schedule**:
- **Notifications**: Every 1 minute → `GET /api/discord/notifications/pending`
- **Role Sync**: On-demand when user roles change (bot doesn't poll for this)
- **Auth Status**: Every 2-3 seconds during active connection flow only

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

**Audit Logging**:
- Log all connection/disconnection events with IP and user agent
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

### Bot-Targeted Notifications
**Decision**: Connect/disconnect events communicated via notification system.
- Website queues `bot.user_connected` notification when user authorizes connection
- Website queues `bot.user_disconnected` notification when user disconnects
- Bot polls same notification endpoint for both bot-targeted and user-targeted notifications
- Bot distinguishes by `type` field and handles accordingly
- Discord role management (including removal on disconnect) is bot's responsibility

**Benefits**:
- Unified polling endpoint
- Consistent notification delivery mechanism
- Decouples website from Discord role management
- Bot can implement its own role removal logic/timing

## Next Steps

1. **Create API documentation**: Detailed docs for bot developer (separate file)
2. **Define notification preferences schema**: How are user preferences stored? Per-type toggles?
3. **Create user stories**: Break down implementation into manageable tasks
4. **Design UI**: Settings pages for connection and notification preferences
5. **Implementation**: 
   - Database migrations
   - Models and relationships
   - Services and business logic
   - API controllers and routes
   - Middleware for authentication
   - Settings pages (views + controllers)
   - Event/listener infrastructure
7. **Testing**: Unit tests for services, feature tests for API endpoints
8. **Bot development**: Separate repository/project

## API Rate Limits (Proposed)

- `POST /api/discord/auth/init`: 50 requests/minute per IP
- `GET /api/discord/notifications/pending`: 120 requests/hour (1 per minute)
- `POST /api/discord/notifications/mark-sent`: 120 requests/hour
- `GET /api/discord/users/{discordId}/roles`: 300 requests/hour
- `DELETE /api/discord/users/{discordId}`: No specific limit (rare operation)

