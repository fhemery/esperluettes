# Discord Bot - API Usage Documentation

**Version**: 1.0  
**Last Updated**: 2025-10-02  
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

1. **Website queues user notification**
   - Type: activity type (e.g., `comment`, `mention`)
   - Data contains message, URL, actor, target

2. **Bot polls notification endpoint**
   ```
   GET /api/discord/notifications/pending
   ```
   - Receives user notifications (within 1 minute)

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
      "discordId": "123456789012345678",
      "type": "comment",
      "data": {
        "message": "JohnDoe commented on your story \"Epic Adventure\"",
        "url": "https://esperluettes.com/stories/epic-adventure/chapters/1#comment-42",
        "actor": "JohnDoe",
        "target": "Epic Adventure - Chapter 1"
      },
      "createdAt": "2025-10-02T11:05:00Z"
    },
    {
      "id": 124,
      "discordId": "987654321098765432",
      "type": "mention",
      "data": {
        "message": "AliceWrites mentioned you in a comment",
        "url": "https://esperluettes.com/stories/mystery-novel/chapters/3#comment-89",
        "actor": "AliceWrites",
        "target": "Mystery Novel - Chapter 3"
      },
      "createdAt": "2025-10-02T11:06:30Z"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "perPage": 100,
    "total": 2,
    "lastPage": 1,
    "hasMore": false
  }
}
```

**Response Fields**:
- `data` (array): Array of notification objects
  - `id` (integer): Unique notification ID
  - `discordId` (string): Discord user ID this notification is for
  - `type` (string): Notification type (see types below)
  - `data` (object): Type-specific notification data
  - `createdAt` (string): ISO 8601 timestamp when notification was created
- `pagination` (object): Pagination metadata
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

**Rate Limit**: 120 requests/hour (1 per minute)

**Example**:
```bash
curl -X GET "https://esperluettes.com/api/discord/notifications/pending?page=1&perPage=50" \
  -H "Authorization: Bearer sk_abc123xyz789" \
  -H "Accept: application/json"
```

---

### POST /api/discord/notifications/mark-sent

Mark notifications as sent after bot delivers them.

**Authentication**: Required (API key)

**Request Body**:
```json
{
  "notificationIds": [123, 124, 125]
}
```

**Request Fields**:
- `notificationIds` (array, required): Array of notification IDs to mark as sent

**Success Response** (200 OK):
```json
{
  "success": true,
  "markedCount": 3
}
```

**Response Fields**:
- `success` (boolean): Always true on success
- `markedCount` (integer): Number of notifications marked as sent

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
    "notificationIds": ["The notificationIds field is required."]
  }
}
```

**Rate Limit**: 120 requests/hour

**Important**: 
- Only mark notifications as sent AFTER successfully delivering them
- If delivery fails, do not mark as sent (notification will be retried on next poll)
- Invalid notification IDs are silently ignored

**Example**:
```bash
curl -X POST "https://esperluettes.com/api/discord/notifications/mark-sent" \
  -H "Authorization: Bearer sk_abc123xyz789" \
  -H "Content-Type: application/json" \
  -d '{
    "notificationIds": [123, 124, 125]
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

## Notification Types

All notifications are user-targeted activity feed events sent as Discord DMs.

**Note**: Connection and disconnection are **synchronous operations** and do not generate notifications. The bot receives role information immediately when calling the connect endpoint.

**Common Structure**:
```json
{
  "id": 125,
  "discordId": "123456789012345678",
  "type": "{notification_type}",
  "data": {
    "message": "{human-readable message}",
    "url": "{clickable URL to website}",
    "actor": "{who triggered the event}",
    "target": "{what was affected}"
  },
  "createdAt": "2025-10-02T11:05:00Z"
}
```

**Data Fields**:
- `message` (string): Human-readable notification message
- `url` (string): Full URL to relevant page on website
- `actor` (string): Username of person who triggered event
- `target` (string): What was affected (story title, chapter, etc.)

**Notification Types** (examples, subject to expansion):
- `comment` - New comment on user's story/chapter
- `comment_reply` - Reply to user's comment
- `mention` - User was mentioned in a comment
- `follow` - Someone followed user
- `like` - Someone liked user's story
- `chapter_published` - New chapter from followed author
- `moderation` - Moderation action on user's content
- `message` - Private message received

**Bot Action**:
1. Format message for Discord (embeds, formatting)
2. Send DM to Discord user
3. Include clickable link to website
4. Mark notification as sent

**Example - Comment Notification**:
```json
{
  "id": 126,
  "discordId": "123456789012345678",
  "type": "comment",
  "data": {
    "message": "JohnDoe commented on your story \"Epic Adventure\"",
    "url": "https://esperluettes.com/stories/epic-adventure/chapters/1#comment-42",
    "actor": "JohnDoe",
    "target": "Epic Adventure - Chapter 1"
  },
  "createdAt": "2025-10-02T11:05:00Z"
}
```

**Example - Mention Notification**:
```json
{
  "id": 127,
  "discordId": "123456789012345678",
  "type": "mention",
  "data": {
    "message": "AliceWrites mentioned you in a comment",
    "url": "https://esperluettes.com/stories/mystery-novel/chapters/3#comment-89",
    "actor": "AliceWrites",
    "target": "Mystery Novel - Chapter 3"
  },
  "createdAt": "2025-10-02T11:06:15Z"
}
```

**Note**: Additional notification types will be added as website features expand. Bot should handle unknown types gracefully.

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