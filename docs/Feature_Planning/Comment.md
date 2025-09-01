# Comment Feature Specification

## Overview
The Comment feature enables community discussion across multiple domains of the site:
- News posts (comment on published news)
- Story chapters (comment on individual chapters)
- Diary entries (planned)

Comments are lightweight, threaded to a single level (replies to a root comment; no deeper nesting), and respect the visibility/authorization rules of their target (e.g., unpublished chapters remain inaccessible to non-authors).

## Core Features

### Commenting Scope
- Targets: polymorphic to support `News`, `Chapter` (Story domain), and later `Diary`.
- Single-level threading: a comment may have replies, but replies cannot themselves be replied to.
- Sorting: root comments displayed by newest first (DESC by creation); replies within each thread by oldest first (ASC by creation).

### Permissions & Roles
- Domain-specific posting rules: minimum role can differ per domain (configurable via policy/service):
  - Story Chapters: minimum role `user` (Phase 1)
  - Diary: minimum role `user-confirmed` (future phase)
  - News: minimum role `user`
- Ability to forbid authors/co-authors from posting root comments is domain-specific (e.g., for Story we may forbid root comments by authors/co-authors but allow replies).
  - Defaults:
    - Story: forbid authors/co-authors from posting root comments; replies allowed.
    - News: authors/co-authors may post root comments.
 - Reading requires authentication and the same minimum role as posting for the target domain.
 - Guests:
   - Cannot read or post; see a call-to-action with a Login button that preserves intended URL including the `#comments` or `#comment-<id>` anchor.
  
Notes:
- Authors/co-authors recognition is domain-specific (e.g., Story collaborators vs News authors). Policies must delegate to the owning domain to resolve authorship.

### Editing & Moderation
- Authors of a comment can edit their own comment. An `edited_at` timestamp is displayed when applicable.
- Deactivation (moderation) state exists; when a root comment is deactivated, its entire thread is hidden. This will be administrative/reporting driven in a later phase.
- Soft deletes supported for audit and possible restoration.

### Display
- Render profile details of the comment author next to each comment (avatar, display name, roles badges as available).
- Provide per-comment permalinks (`#comment-<id>`) and a comments section anchor (`#comments`).
- Lazy loading: comments are not fetched at initial page load. They are fetched when the user reaches the comments section (IntersectionObserver) or when the URL contains a comments anchor.
 - Comment counts/badges are shown only to users authorized to read comments; they are hidden for guests or insufficient-role users.

## Technical Specifications

### Technology Stack
- Editor: QuillJS with strict purifier profile. Toolbar: bold, italic, underline, strikethrough, ordered list, unordered list, blockquote.
- HTML sanitization via `config/purifier.php` (strict profile). Links are disallowed (no exceptions in Phase 1).
- Domain-Oriented Architecture: `app/Domains/Comment/` with controllers using services. Policies enforce permissions.
- Frontend: Alpine.js for interaction, TailwindCSS for styling, Blade templates per domain.

### Validation
- No explicit character length limit is enforced in Phase 1 (root or replies). Per-domain limits may be introduced later.
- Links are not allowed and are stripped; only toolbar-allowed tags are retained.

### Database Schema (Preliminary)

```sql
comments:
- id (primary key)
- user_id (foreign key to users, indexed)
- commentable_type (string, indexed)
- commentable_id (unsigned bigint, indexed)
- parent_comment_id (unsigned bigint, nullable, indexed)      -- null for root; non-null for one-level reply
- body (longtext)                                      -- sanitized HTML; also store a plain-text cache for search/limits if needed
- edited_at (timestamp, nullable)
- is_active (boolean) default true                     -- when false, hide the comment; when a root is inactive, hide its children
- is_answered (boolean) default false                  -- used by Story: for root comments, true when any author/co-author replied; updated by service/event
- created_at (timestamp)
- updated_at (timestamp)

indexes:
- (commentable_type, commentable_id, created_at)
- (parent_comment_id)
- (user_id)
```

Notes:
- One-level threading enforced via validation and policy: if `parent_comment_id` is set, the referenced comment must be a root comment (`parent_comment_id is null`).
- Consider a computed `thread_root_id` for faster queries; optional in Phase 1.
- `is_answered` applies only to root comments for Story/Chapter targets; other domains may ignore it.

### Domain Structure
```
app/Domains/Comment/
├── Http/
│   └── Controllers/
│       └── CommentController.php     # JSON endpoints for fetch/create/update/delete
├── Models/
│   └── Comment.php                   # use SoftDeletes; relationships to user and morph target
├── Policies/
│   └── CommentPolicy.php             # create/update/delete/restore rules
├── Services/
│   └── CommentService.php            # core business logic and sanitization
├── Requests/
│   └── CommentRequest.php            # validation and body sanitization rules
├── Resources/
│   ├── lang/
│   └── views/
│       └── components/
│           ├── provider.blade.php    # headless provider: Alpine store, data loading, actions
│           ├── list.blade.php        # renders threads using parent provider state (no own store)
│           └── editor.blade.php      # create/edit form using parent provider state
└── Database/
    └── Migrations/
```

### Frontend Composition (Headless Provider + Primitives)
- The Comment domain ships only generic UI primitives; it does not render domain-specific tabs (e.g., Annotations).
- `provider.blade.php` wraps the comments area and exposes an Alpine store, e.g. `commentsApp({ targetType, targetId })`.
  - Responsibilities: lazy fetch, pagination, create/reply/edit, anchors, permission prompts.
- `list.blade.php` renders current threads from the provider (newest roots DESC, replies ASC).
- `editor.blade.php` renders the Quill editor and posts through the provider.
- __Extension points__ (non-breaking):
  - Per-item slots in `list.blade.php`, e.g., `meta` to inject badges like “Annotations (n)”.
  - The provider renders no extra tabs; domains compose around it.

### Annotations Integration (Story-owned, V2)
- Comment core remains annotations-agnostic. No tabs or panels are shipped by the Comment domain.
- The Story domain composes its own tabs (e.g., "General" and "Annotations") around `comment::provider`:
  - General tab uses `comment::list` and `comment::editor`.
  - Annotations tab is implemented by Story (custom panel, Story endpoints).
- Optional per-comment badge: Story may inject a pill via the `meta` slot in `comment::list` when a comment has related annotations.
- Data sources:
  - Comments JSON: Comment domain endpoints under the target route.
  - Annotations JSON: Story domain endpoint (e.g., `/stories/{story}/chapters/{chapter}/annotations`).
- Inline marks: controlled by Story; authors/co-authors only. Reader view exposes Annotations tab content without inline marks.


### URL Structure
- Fetch comments (lazy): JSON endpoint under the target context.
  - Chapters: `GET /stories/{storySlug}/chapters/{chapterSlug}/comments` → paginated, includes threads with replies.
  - News: `GET /news/{newsSlug}/comments`.
- Create comment:
  - Chapters: `POST /stories/{storySlug}/chapters/{chapterSlug}/comments` (root or reply via `parent_id`).
  - News: `POST /news/{newsSlug}/comments`.
- Update comment: `PUT /comments/{id}` (author only).
- Deactivate comment: `PUT /comments/{id}/deactivate` (admin/moderation; Phase 2+).
- Delete comment (soft): `DELETE /comments/{id}` (admin-only; Phase 2+).

All mutating routes are CSRF-protected and governed by policies. Controllers never access DB directly; use `CommentService`.

### Rendering & Lazy Loading
- The comments section places a lightweight mount element on the page (`#comments`).
- An IntersectionObserver triggers a fetch to the JSON endpoint when the section becomes visible (or immediately if URL has `#comments` or `#comment-<id>`).
- Server returns:
  - Root comments with metadata and nested replies (one level only).
  - Pagination of root threads; each root includes all its replies (ASC).
  - Inactive comments (`is_active = false`) are excluded in Phase 1.
  - If the viewer lacks permission to read comments, the server responds 401/403 and the UI shows a permission/login message (no comments data shown).

#### SSR Preload for Tests (showComments)
- To enable Feature tests without a browser runner, we support an opt-in server-side render path:
  - When `?showComments=1` and the user is authorized to read comments, the owning page controller fetches the first page via `CommentService::listForTarget($target, page: 1)` and passes it to the view as `$preloadedComments`.
  - The `comment::provider` detects preloaded data (e.g., a `preloaded` flag) and skips the initial JS fetch. `comment::list` renders the first page server-side.
  - If unauthorized, we still render the usual permission/login prompt; no comment HTML is emitted.
  - Only the first page is preloaded; normal lazy-load resumes for subsequent pages.
- Defaults remain unchanged: if `showComments` is absent/false, we use IntersectionObserver and fetch via JSON as usual.
- Implementation notes (non-breaking):
  - Reuse the same `comment::list` partial for SSR to avoid divergence with the JS-rendered list.
  - Prevent double-rendering by hydrating the Alpine store from the preloaded dataset or by marking the SSR list container (e.g., `data-testid="comments-ssr"`).
  - Enforce the exact same policies/adapters on the SSR path as on the JSON endpoints.

### Sorting & Pagination
- Root threads: order by `created_at DESC`.
- Replies: order by `created_at ASC`.
- Pagination: 20 root threads per page (configurable). Replies are not paginated within a root thread in Phase 1.

### Anchors & Deep Links
- Each comment list item has `id="comment-<id>"`.
- Copy-link UI sets `location.hash = #comment-<id>` without reload.
- If initial URL contains a specific comment anchor, ensure the corresponding page fetch includes the page with that root comment, then scroll to it on render.
 - If the specified comment is missing, inactive, or unauthorized for the viewer, show a brief "Comment unavailable" message and keep focus at the comments header.
 - For non-JS Feature tests, the list endpoint may accept `focusCommentId` so the server can compute the page containing a given root comment; this is useful together with `showComments=1` to assert the target comment appears in the preloaded HTML.

## Testing Strategy

- **Feature (API) tests — primary**
  - Hit JSON endpoints in `CommentController` backed by `CommentService`.
  - Assert permissions (401/403/404/200), sorting, pagination, one-level replies, edit rules, sanitization (no links), and `is_answered` events.

- **Feature (Page) tests — SSR on demand (no JS)**
  - Request pages with `?showComments=1` to SSR the first page of comments when authorized.
  - Assert HTML contains expected comment author/body snippets and order; assert permission prompt when unauthorized.
  - Combine with `focusCommentId` in the list endpoint to verify anchors by ensuring the targeted root appears in the preloaded HTML.

- **Unit tests — policies/adapters**
  - Validate per-domain rules via `CommentTargetPolicyContract` adapters (read/post root/reply, forbid author root, authorship resolution).

- **Serialization/snapshots (optional)**
  - Snapshot a curated JSON payload (with volatile fields stripped) for the list endpoint to detect regressions.

- **Out of scope for now**
  - No Dusk/browser tests. Lazy-load remains exercised indirectly by API tests; page HTML assertions rely on `showComments` SSR.

## User Stories (High-Level)
- As an authenticated user with sufficient role, I can post a new comment on a chapter/news.
- As an authenticated user with sufficient role, I can reply one level deep to an existing root comment.
- As the author of a comment, I can edit my comment, with an "edited" indicator.
- As an authenticated user with sufficient role, I can read comments respecting the target's visibility rules.
- As a guest or insufficient-role user, I see a login/permission prompt that preserves my intended return to the comments anchor.
- As a moderator/admin, I can deactivate a comment (later phase) to hide it and its thread.
- As a story co-author, I can see unanswered comments on my chapters (later phase).

## Security & Authorization
- Read/Create/Reply/Edit are limited to authenticated users who meet the domain’s minimum role.
- Guests and insufficient-role users receive a 401/403; the UI shows a login/insufficient-role message.
- All actions enforce the visibility of the target content (e.g., unpublished chapters return 404 to non-authors; comments are inaccessible too).
- Policies prevent replying to a reply (enforce one-level threads).

## Performance Considerations
- Lazy load comments to avoid heavy initial queries.
- N+1 avoidance via eager loading of users for all comments in a page.
- Indexes on `(commentable_type, commentable_id, created_at)` for root fetch and `(parent_id)` for replies.
- Consider caching comment counts per target for UI badges; updated via events.

## Events & Integrations
- Emit `CommentCreated`, `CommentUpdated`, `CommentDeleted` events within the Comment domain.
- Story domain can listen for `CommentCreated` and `CommentDeleted`/`CommentDeactivated` to maintain `is_answered` on roots: set true when any author/co-author replies; set false when the last such reply is removed or becomes inactive.

## Outstanding Questions
- Diary-specific policies to be defined in a later phase (min role `user-confirmed`, author root rule, counts visibility).

## Future Enhancements
- Reporting flow and moderation dashboard (Filament).
- Unanswered comments indicator endpoints and UI in Story/Chapter management.
- Mentions and notifications.
- Rate limiting and anti-spam heuristics.
- Story V2: Inline comments (annotations). Tabs are composed by Story around the Comment provider (Comment core ships no tabs).
  - Annotations are attached to a standard comment. Replying to the annotation replies the parent comment. No separate "answered" tracking for annotations.
  - Inline marks are visible to authors/co-authors in the reading view; readers won’t see inline marks but may access the Annotations tab content.

## Dependencies
- User authentication and roles.
- Story and News domains expose visibility rules and model policies.
- QuillJS setup with strict purifier profile including blockquote (update existing Story editors to include blockquote).
- Domain events bus.
