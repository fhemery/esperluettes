# Comment Domain Implementation Planning - Phase 2 (News)

This phase delivers commenting for News items, aligning with the core Comment domain while honoring News-specific policies. Chapters were implemented in Phase 1.

---

## CM-201: Lazy Load Comments Section (News)

As a visitor, I want news comments to load only when I reach the comments section so that news pages remain fast.

Acceptance Criteria:
- A mount element (e.g., `#comments`) is present in `news.show` template.
- IntersectionObserver triggers fetch to the JSON endpoint when the section becomes visible or when URL has `#comments`/`#comment-<id>`.
- The initial payload returns the first page of root comments with their replies.
- Loading state and error state are displayed.
- Only authenticated users with sufficient role can load comments; otherwise the endpoint returns 401/403 and the UI shows a login/permission prompt (no list rendered).

Implementation Notes:
- GET endpoint:
  - `GET /news/{newsSlug}/comments`
- Pagination param: `page` for root threads; replies unpaginated.
- Controller delegates to `CommentService::listForTarget()`; view uses Alpine.js to render.
- Exclude inactive (`is_active = false`) and soft-deleted comments from payload.
- For non-JS feature tests, support `?showComments=1` SSR preload of the first page when authorized (see Testing Strategy).

---

## CM-202: Post Root Comment (News)

As a logged-in user with sufficient role, I can post a new root comment on a news item.

Acceptance Criteria:
- Comment form (Quill with strict purifier + blockquote) is displayed above the list when user is authorized; hidden otherwise.
- Guests see a prompt with a Login button that preserves intended URL with `#comments` anchor.
- On success, the new comment appears at the top of the root list (DESC ordering) and the editor is cleared.
- Sanitization enforced; disallowed tags stripped. Links are not allowed and are removed.
- No explicit character length limit in Phase 2 (mirrors Phase 1 behavior).

Implementation Notes:
- POST endpoint:
  - `POST /news/{newsSlug}/comments`
- `CommentRequest` validates: body required (non-empty after strip-tags); enforce toolbar constraints. No explicit length limit.
- Policies check: domain-specific minimum role, target visibility, and per-domain rules.
- News policy default: authors/co-authors MAY post root comments (unlike Story default).
- New comments are created with `is_active = true`.

---

## CM-203: Reply One Level Deep (News)

As a logged-in user, I can reply to a root comment, creating a single-level thread.

Acceptance Criteria:
- Each root comment has a Reply action (if authorized).
- Replies are displayed indented beneath the root, ordered ASC by creation.
- Validation prevents replying to a reply (only to roots where `parent_id is null`).

Implementation Notes:
- Same POST endpoint as CM-202; include `parent_id` of a root comment.
- Service verifies parent belongs to the same target and is a root (no `parent_id`).
- Body sanitization identical to CM-202 (no links).

---

## CM-204: List & Sort Comments (News)

As an authenticated user with sufficient role, I can view comments with consistent sorting and threading.

Acceptance Criteria:
- Root comments ordered by `created_at DESC`.
- Replies ordered by `created_at ASC`.
- Inactive (`is_active = false`) and soft-deleted comments are not shown.
- Pagination for root threads: 20 per page (configurable).

Implementation Notes:
- Efficient queries with indexes; eager-load `user` for all comments in the page.
- Serialize with view models to avoid leaking sensitive fields.

---

## CM-205: Edit Own Comment (News)

As the author of a comment, I can edit it and see an "edited" indicator.

Acceptance Criteria:
- Inline or modal editor for the author's own comments.
- Saves sanitized HTML and sets `edited_at`.
- No time limit for editing.
- Show an "edited" indicator with timestamp.
- Non-authors cannot edit; guests cannot edit.

Implementation Notes:
- `PUT /comments/{id}` guarded by `CommentPolicy@update`.
- Validation mirrors CM-202 body rules.

---

## CM-206: Comment Anchors & Permalinks (News)

As a user, I can copy a direct link to a specific comment and land on it when visiting the link.

Acceptance Criteria:
- Each item has `id="comment-<id>"` and a Copy Link UI.
- Visiting a URL ending with `#comment-<id>` loads the page and triggers comments fetch; after render, the view scrolls/focuses to the targeted comment.
- If the target comment is on a non-first page, the client includes a hint (e.g., `?commentId=<id>`) so the server returns the page containing that root thread.
- If the specified comment is missing, inactive, or unauthorized, the UI shows a brief "Comment unavailable" message and remains at the comments header.

Implementation Notes:
- Server supports `commentId` param to resolve the page containing that root and return that page as first payload.
- For non-JS feature tests, combine page request with `?showComments=1` and the list endpoint's `focusCommentId` support to assert the target appears in SSR HTML.

---

## CM-207: Permission Messaging & Login Intent (News)

As a guest or a user without sufficient rights, I understand why I cannot comment and can log in to continue.

Acceptance Criteria:
- When not authorized to post, the form area displays a message explaining the requirement.
- Guests see a Login button that redirects back to the same page with `#comments` after authentication.
- When not authorized to read, the comments list is not rendered and only the login/permission prompt is shown.

Implementation Notes:
- Use Laravel intended redirect preserving URL hash through frontend.
- Translations under Comment domain: `comment::messages.login_required`, `comment::messages.insufficient_role`.

---

## Policies & Roles (News specifics)
- Minimum role: `user` to read and post (configurable via `CommentTargetPolicyContract`).
- Authors/co-authors: allowed to post root and replies by default for News.
- Target visibility: comments follow News visibility rules; hidden/unpublished news are not readable for non-authorized users.

---

## Testing Strategy (News)
- Feature API tests for JSON endpoints (auth, sorting, pagination, one-level replies, edit rules, sanitization).
- Feature page tests using `?showComments=1` SSR preload for first page when authorized; assert HTML snippets and permission messaging.
- Unit tests for News adapter implementing `CommentTargetPolicyContract`.
- Optional serialization snapshots for list endpoint.

---

## Deferred / Shared Items
- Moderation and deactivation workflows (admin/reporting).
- Rate limiting and anti-spam heuristics.
- Diary domain support.
- Admin deletion (soft) and restoration.
