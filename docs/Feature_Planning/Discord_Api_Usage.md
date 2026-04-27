# Discord Bot - API Usage Documentation

**Version**: 1.1  
**Last Updated**: 2026-04-25  
**Base URL**: `https://esperluettes.com`

## Overview

This document describes the Esperluettes website API that the Discord bot integrates with. The API enables:
- User authentication via one-time codes
- Discord account linking and role synchronization
- Activity notification delivery to Discord users

## Architecture

### Communication Model
- **Direction**: Bot → Website (bot always initiates requests)
- **Protocol**: HTTPS REST API
- **Polling Interval**: 1 minute for notifications
- **Authentication**: API key via Bearer token

### Connection Model
- **User-initiated**: Users visit their profile page to get a one-time connection code
- **Synchronous**: Connection happens immediately when bot calls API with code
- **Bot-initiated disconnect**: Users disconnect via `/disconnect` command in Discord

### Notification System
The bot polls for pending notifications containing:
- **User-targeted notifications**: Activity feed events (comments, mentions, follows, etc.)

Key points for bot developers:
- Notifications appear in the pending queue **only for users who have opted in** to Discord delivery for that notification type. User preferences are managed on the website's notification preferences page.
- A user must have a linked Discord account to receive notifications. Opting in without linking has no effect.
- If a user disconnects their Discord account, all their pending unsent notifications are deleted immediately.
- The pending queue is pre-filtered — the bot never needs to apply preference logic itself.

## Authentication

All API requests require authentication via API key.

### Headers
```http
Authorization: Bearer {API_KEY}
Content-Type: application/json
Accept: application/json
```

### API Key
- Provided by website administrator
- Store securely in bot's environment variables
- Same key used for all API requests

### Example Request
```bash
curl -X GET "https://esperluettes.com/api/discord/notifications/pending" \
  -H "Authorization: Bearer sk_abc123xyz789" \
  -H "Accept: application/json"
```

## Workflows

### 1. User Connection Workflow

**Trigger**: User types `/connect <code>` command in Discord

**Steps**:

1. **User visits profile page**
   - User must be logged in to website
   - Website generates and displays one-time code (e.g., `1258ac67`)
   - Code valid for 5 minutes
   - Website shows command: `/connect 1258ac67`

2. **User types command in Discord**
   ```
   /connect 1258ac67
   ```

3. **Bot calls connection endpoint**
   ```
   POST /api/discord/auth/connect
   ```
   - Sends code, Discord ID, and Discord username
   - Website validates code and associates Discord account
   - Website returns user roles **immediately** (synchronous)

4. **Bot assigns Discord roles**
   - Extracts roles from response
   - Maps to Discord server roles
   - Assigns roles to user
   - Sends confirmation message to user

**Flow Diagram**:
```
User → Profile page (logged in) → Website generates code
                  ↓
         User copies: /connect 1258ac67
                  ↓
    User → Discord → /connect 1258ac67 → Bot
                  ↓
    Bot → POST /api/discord/auth/connect (with code)
                  ↓
    Website validates & returns roles immediately
                  ↓
         Bot assigns Discord roles
```

### 2. User Disconnection Workflow

**Trigger**: User types `/disconnect` command in Discord

**Steps**:

1. **User types command in Discord**
   ```
   /disconnect
   ```

2. **Bot calls disconnection endpoint**
   ```
   DELETE /api/discord/users/{discordId}
   ```
   - Website removes Discord ID ↔ User ID mapping
   - Website returns success response **immediately** (synchronous)

3. **Bot removes Discord roles**
   - Bot decides which roles to remove
   - Bot removes roles from user
   - User stops receiving notifications
   - Sends confirmation message to user

**Flow Diagram**:
```
User → Discord → /disconnect → Bot
                  ↓
    Bot → DELETE /api/discord/users/{discordId}
                  ↓
    Website removes association & returns success
                  ↓
         Bot removes Discord roles
```
### 3. Activity Notification Workflow

**Trigger**: Activity event occurs on website (comment, mention, etc.)
**Steps**:

1. **Website queues Discord notification automatically**
   - When the activity triggers a notification, the Notification domain calls the Discord channel's delivery callback.
   - The callback resolves each concerned user's `discord_id` and writes a row to `discord_pending_notifications`.
   - Only users who have opted in for that notification type on the Discord channel are queued.

2. **Bot polls notification endpoint**
   ```
   GET /api/discord/notifications/pending
   ```
   - Receives pre-filtered, pre-resolved notifications (within 1 minute)

3. **Bot sends Discord DM**
   - Formats notification message
   - Sends to user's Discord account
   - Includes clickable URL to website

4. **Bot marks notification as sent**
   ```
   POST /api/discord/notifications/mark-sent
   ```

## API Endpoints

### POST /api/discord/users

Create a Discord user link using a one-time code generated on the website.

**Authentication**: Required (API key)

**Request Body**:
```json
{
  "code": "1258ac67",
  "discordId": "123456789012345678",
  "discordUsername": "DisplayName"
}
```

**Request Fields**:
- `code` (string, required): One-time connection code from user's profile page
- `discordId` (string, required): Discord user ID (17-19 digit numeric string)
- `discordUsername` (string, required): Discord display name (no discriminator)

**Success Response** (200 OK):
```json
{
  "success": true,
  "userId": 456,
  "roles": ["user", "author", "moderator"]
}
```

**Response Fields**:
- `success` (boolean): Always true on success
- `userId` (integer): Website user ID
- `roles` (array): User's current website roles

**Error Responses**:

```json
// 401 Unauthorized - Invalid API key
{
  "error": "Unauthorized",
  "message": "Invalid API key"
}

// 400 Bad Request - Validation error
{
  "error": "Validation failed",
  "errors": {
    "code": ["The code field is required."],
    "discordId": ["The discordId field is required."],
    "discordUsername": ["The discordUsername field is required."]
  }
}

// 404 Not Found - Invalid or expired code
{
  "error": "Not found",
  "message": "Invalid or expired connection code"
}

// 409 Conflict - User already connected or Discord ID in use
{
  "error": "Conflict",
  "message": "Discord account already connected to another user"
}

// 429 Too Many Requests - Rate limit exceeded
{
  "error": "Too many requests",
  "message": "Rate limit exceeded. Try again in 60 seconds."
}
```

**Rate Limit**: 100 requests/minute per IP

**Example**:
```bash
curl -X POST "https://esperluettes.com/api/discord/auth/connect" \
  -H "Authorization: Bearer sk_abc123xyz789" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "1258ac67",
    "discordId": "123456789012345678",
    "discordUsername": "CoolUser"
  }'
```

---

### GET /api/discord/notifications/pending

Fetch all pending user activity notifications.

**Authentication**: Required (API key)

**Query Parameters**:
- `page` (integer, optional): Page number (default: 1)
- `perPage` (integer, optional): Items per page (default: 100, max: 100)

**Success Response** (200 OK):
```json
{
  "data": [
    {
      "id": 123,
      "type": "comment",
      "data": {
        "message": "JohnDoe commented on your story \"Epic Adventure\"",
        "url": "https://esperluettes.com/stories/epic-adventure/chapters/1#comment-42",
        "actor": "JohnDoe",
        "target": "Epic Adventure - Chapter 1"
      },
      "avatarUrl": "https://jd-esperluettes.fr/storage/profile_pictures/1_1760348457.jpg",
      "defaultText": "[JohnDoe](https://esperluettes.com/profile/johndoe) commented on your story **\"Epic Adventure\"**",
      "recipients": [
        "123456789012345678",
        "987654321098765432"
      ],
      "createdAt": "2025-10-02T11:05:00Z"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "perPage": 100,
    "total": 1,
    "lastPage": 1,
    "hasMore": false
  }
}
```

**Response Fields**:
- `data` (array): Array of notification objects — one entry per notification, regardless of recipient count
  - `id` (integer): Pending notification ID — use this in mark-sent
  - `type` (string): Notification type (see types below)
  - `data` (object): Type-specific notification data — identical for all recipients
  - `avatarUrl` (string|null): Avatar URL of the user who triggered the notification; absent when system-generated
  - `defaultText` (string): Ready-to-send Discord-formatted message. HTML links from the website notification are converted to `[text](url)` Discord links and bold text becomes `**text**`. Use this as the DM body unless you need custom formatting.
  - `recipients` (array of strings): Discord user IDs to send this notification to
  - `createdAt` (string): ISO 8601 timestamp when notification was created
- `pagination` (object): Pagination metadata — page counts refer to notification entries, not individual recipients
  - `currentPage` (integer): Current page number
  - `perPage` (integer): Items per page
  - `total` (integer): Total pending notifications
  - `lastPage` (integer): Last page number
  - `hasMore` (boolean): Whether more pages exist

**Error Responses**:

```json
// 401 Unauthorized - Invalid API key
{
  "error": "Unauthorized",
  "message": "Invalid API key"
}
```

**Rate Limit**: 300 requests / minute

**Example**:
```bash
curl -X GET "https://esperluettes.com/api/discord/notifications/pending?page=1&perPage=50" \
  -H "Authorization: Bearer sk_abc123xyz789" \
  -H "Accept: application/json"
```

---

### POST /api/discord/notifications/mark-sent

Mark notifications as delivered after the bot sends DMs. Supports partial delivery — if some recipients failed, list them in `failedRecipients` so they are retried on the next poll.

**Authentication**: Required (API key)

**Request Body**:
```json
{
  "notifications": [
    {"id": 123},
    {"id": 124, "failedRecipients": ["111222333444555666"]}
  ]
}
```

**Request Fields**:
- `notifications` (array, required): Array of delivery reports
  - `id` (integer, required): Pending notification ID (from the `id` field in the poll response)
  - `failedRecipients` (array of strings, optional): Discord user IDs that could not be reached. When absent, all recipients of this notification are marked as delivered. When present, all recipients **except** the listed ones are marked as delivered — the listed ones remain pending and reappear on the next poll.

**Success Response** (200 OK):
```json
{
  "success": true,
  "markedCount": 5
}
```

**Response Fields**:
- `success` (boolean): Always true on success
- `markedCount` (integer): Number of individual recipients marked as delivered

**Error Responses**:

```json
// 401 Unauthorized - Invalid API key
{
  "error": "Unauthorized",
  "message": "Invalid API key"
}

// 400 Bad Request - Validation error
{
  "error": "Validation failed",
  "errors": {
    "notifications": ["The notifications field is required."]
  }
}
```

**Rate Limit**: 120 requests/hour

**Important**:
- Only report delivery after the DM has been successfully sent
- Unknown notification IDs and unknown `failedRecipients` discord IDs are silently ignored
- A notification with all recipients marked delivered will no longer appear in the poll response

**Example**:
```bash
curl -X POST "https://esperluettes.com/api/discord/notifications/mark-sent" \
  -H "Authorization: Bearer sk_abc123xyz789" \
  -H "Content-Type: application/json" \
  -d '{
    "notifications": [
      {"id": 123},
      {"id": 124, "failedRecipients": ["111222333444555666"]}
    ]
  }'
```

---

### GET /api/discord/users/{discord_id}

Get current roles for a connected Discord user. Might contain more information later on

**Authentication**: Required (API key)

**URL Parameters**:
- `discordId` (string, required): Discord user ID

**Success Response** (200 OK):
```json
{
  "userId": 456,
  "discordId": "123456789012345678",
  "roles": ["user", "author", "moderator"],
  "lastUpdated": "2025-10-02T11:05:00Z"
}
```

**Response Fields**:
- `userId` (integer): Website user ID
- `discordId` (string): Discord user ID
- `roles` (array): Array of current role names
- `lastUpdated` (string): ISO 8601 timestamp of last role change

**Error Responses**:

```json
// 404 Not Found - Discord user not connected
{
  "error": "Not found",
  "message": "Discord user not found or not connected"
}

// 401 Unauthorized - Invalid API key
{
  "error": "Unauthorized",
  "message": "Invalid API key"
}
```

**Rate Limit**: 300 requests/hour

**Use Case**: On-demand role sync when you need to verify user's current roles

**Example**:
```bash
curl -X GET "https://esperluettes.com/api/discord/users/123456789012345678/roles" \
  -H "Authorization: Bearer sk_abc123xyz789" \
  -H "Accept: application/json"
```

---

### DELETE /api/discord/users/{discord_id}

Disconnect a Discord account from the website. Also clears any pending Discord notifications for this user.

**Authentication**: Required (API key)

**URL Parameters**:
- `discordId` (string, required): Discord user ID to disconnect

**Success Response** (204 No Content):

**Error Responses**:

```json
// 404 Not Found - Discord user not connected
{
  "error": "Not found",
  "message": "Discord user not found or not connected"
}

// 401 Unauthorized - Invalid API key
{
  "error": "Unauthorized",
  "message": "Invalid API key"
}
```

**Rate Limit**: 100 requests/minute per IP

**Use Case**: Called when user types `/disconnect` command in Discord

**Example**:
```bash
curl -X DELETE "https://esperluettes.com/api/discord/users/123456789012345678" \
  -H "Authorization: Bearer sk_abc123xyz789" \
  -H "Accept: application/json"
```

## Error Handling

### HTTP Status Codes

- **200 OK**: Request succeeded
- **400 Bad Request**: Validation failed
- **401 Unauthorized**: Invalid or missing API key
- **404 Not Found**: Resource not found (disconnected user, etc.)
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error (retry later)

### Error Response Format

```json
{
  "error": "Error type",
  "message": "Human-readable error message",
  "errors": {
    "field_name": ["Validation error messages"]
  }
}
```

### Retry Strategy

**Recommended approach**:

1. **Rate Limit (429)**:
   - Wait duration specified in `Retry-After` header
   - If no header, wait 60 seconds
   - Exponential backoff for repeated 429s

2. **Server Error (500)**:
   - Retry with exponential backoff
   - Max 3 retries
   - If persistent, alert administrator

3. **Network Errors**:
   - Retry with exponential backoff
   - Max 5 retries
   - Log for investigation

4. **Client Errors (4xx except 429)**:
   - Do not retry
   - Log error for investigation

**Example Backoff**:
```
Attempt 1: Immediate
Attempt 2: Wait 2 seconds
Attempt 3: Wait 4 seconds
Attempt 4: Wait 8 seconds
Attempt 5: Wait 16 seconds
```

## Rate Limits Summary

| Endpoint | Limit | Per |
|----------|-------|-----|
| `POST /api/discord/auth/connect` | 100 requests | Minute (per IP) |
| `GET /api/discord/notifications/pending` | 120 requests | Hour (1/minute) |
| `POST /api/discord/notifications/mark-sent` | 120 requests | Hour |
| `GET /api/discord/users/{discordId}/roles` | 300 requests | Hour |
| `DELETE /api/discord/users/{discordId}` | 100 requests | Minute (per IP) |

**Rate Limit Headers** (included in responses):
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1696248000
```

## Security Considerations

### API Key Protection
- Store API key in environment variables (never hardcode)
- Never log API key
- Rotate API key periodically
- Use HTTPS for all requests

### Discord ID Validation
- Verify Discord IDs are 17-19 digit numeric strings
- Validate Discord user exists before processing
- Handle deleted/banned Discord accounts gracefully

### Data Privacy
- Don't log sensitive user data (messages, personal info)
- Respect user's notification preferences
- Handle disconnections immediately (stop sending notifications)

### Rate Limiting
- Respect rate limits to avoid IP bans
- Implement proper backoff strategies
- Monitor rate limit headers