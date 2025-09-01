# Comment Domain Implementation Planning - Phase 1

This phase delivers core commenting for Chapters (Story domain) only, with single-level replies, lazy loading, and editing by author. News is deferred to Phase 2.

---

## CM-001: Lazy Load Comments Section (Per Target)

As a visitor, I want comments to load only when I reach the comments section so that pages remain fast.

Acceptance Criteria:
- A mount element (e.g., `#comments`) is present in `stories.show` template.
- IntersectionObserver triggers fetch to the JSON endpoint when the section becomes visible or when URL has `#comments`/`#comment-<id>`.
- The initial payload returns the first page of root comments with their replies.
- Loading state and error state are displayed.
- Only authenticated users with sufficient role can load comments; otherwise the endpoint returns 401/403 and the UI shows a login/permission prompt (no list rendered).

Implementation Notes:
- GET endpoint:
  - `GET /stories/{storySlug}/chapters/{chapterSlug}/comments`
- Pagination param: `page` for root threads; replies unpaginated.
- Controller delegates to `CommentService::listForTarget()`; view uses Alpine.js to render.
- Exclude inactive (`is_active = false`) and soft-deleted comments from payload.

---

## CM-002: Post Root Comment (Chapters)

As a logged-in user with sufficient role, I can post a new root comment.

Acceptance Criteria:
- Comment form (Quill with strict purifier + blockquote) is displayed above the list when user is authorized; hidden otherwise.
- Guests see a prompt with a Login button that preserves intended URL with `#comments` anchor.
- On success, the new comment appears at the top of the root list (due to DESC ordering) and the editor is cleared.
- Sanitization enforced; disallowed tags stripped. Links are not allowed and are removed.
- No explicit character length limit in Phase 1 (root or replies).

Implementation Notes:
- POST endpoint:
  - `POST /stories/{storySlug}/chapters/{chapterSlug}/comments`
- `CommentRequest` validates: body required (non-empty after strip-tags); enforce toolbar constraints. No explicit length limit in Phase 1.
- Policies check: domain-specific minimum role, target visibility, and additional per-domain rules.
- Story policy: configurable to forbid authors/co-authors from posting root comments (replies allowed). Default: forbid in Story; confirm.
- New comments are created with `is_active = true`.

---

## CM-003: Reply One Level Deep

As a logged-in user, I can reply to a root comment, creating a single-level thread.

Acceptance Criteria:
- Each root comment has a Reply action (if authorized).
- Replies are displayed indented beneath the root, ordered ASC by creation.
- Validation prevents replying to a reply (only to roots where `parent_id is null`).

Implementation Notes:
- Same POST endpoint as CM-002; include `parent_id` of a root comment.
- Service verifies parent belongs to the same target and is a root (no `parent_id`).
- Body sanitization identical to CM-002 (no links).

---

## CM-004: List & Sort Comments

As an authenticated user with sufficient role, I can view comments with consistent sorting and threading.

Acceptance Criteria:
- Root comments ordered by `created_at DESC`.
- Replies ordered by `created_at ASC`.
- Inactive (`is_active = false`) and soft-deleted comments are not shown in Phase 1.
- Pagination for root threads: 20 per page (configurable).

Implementation Notes:
- Efficient Eloquent queries with indexes; eager-load `user` for all comments in the page.
- View models for serialization (avoid leaking sensitive fields).

---

## CM-005: Edit Own Comment

As the author of a comment, I can edit it and see an "edited" indicator.

Acceptance Criteria:
- Inline or modal editor for the author's own comments.
- Saves sanitized HTML and sets `edited_at`.
- No time limit for editing.
- Show an "edited" indicator with timestamp.
- Non-authors cannot edit; guests cannot edit.

Implementation Notes:
- `PUT /comments/{id}` guarded by `CommentPolicy@update`.
- Validation mirrors CM-002 body rules.

---

## CM-006: Comment Anchors & Permalinks

As a user, I can copy a direct link to a specific comment and land on it when visiting the link.

Acceptance Criteria:
- Each item has `id="comment-<id>"` and a Copy Link UI.
- Visiting a URL ending with `#comment-<id>` loads the page and triggers comments fetch; after render, the view scrolls/focuses to the targeted comment.
- If the target comment is on a non-first page, the client includes a hint (e.g., `?commentId=<id>`) so the server returns the page containing that root thread.
- If the specified comment is missing, inactive, or unauthorized, the UI shows a brief "Comment unavailable" message and remains at the comments header.

Implementation Notes:
- Server supports `commentId` param to resolve the page containing that root and return that page as first payload.

---

## CM-007: Permission Messaging & Login Intent

As a guest or a user without sufficient rights, I understand why I cannot comment and can log in to continue.

Acceptance Criteria:
- When not authorized to post, the form area displays a message explaining the requirement.
- Guests see a Login button that redirects back to the same page with `#comments` after authentication.
- When not authorized to read, the comments list is not rendered and only the login/permission prompt is shown.

Implementation Notes:
- Use Laravel intended redirect preserving URL hash through frontend.
- Translations under Comment domain: `comment::messages.login_required`, `comment::messages.insufficient_role`.

---

## Deferred to Phase 2+
- Deactivation and moderation endpoints (admin/policy).
- Display policy for deactivated placeholders.
- Per-target rate limiting and anti-spam.
- "Unanswered comments" indicators for Story/Chapters.
- If not enforced in Phase 1: per-domain rule about authors posting root comments on their own content.
- Diary endpoints/routes.
- Delete (soft) endpoints and admin-only deletion workflow.
