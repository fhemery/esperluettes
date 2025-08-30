# Story Domain Implementation Planning - Phase 3

This phase focuses on implementing the chapter management system for stories.

## **Prerequisites**

Phase 3 requires completion of Phase 1 (US-001 through US-008) and Phase 2 (US-009 through US-021).

---

## ** [DONE] US-030: Add Chapter to Story**

**As a story author, I want to add chapters to my story so that I can organize my content.**

**Acceptance Criteria:**

- **Access**: Only authors/co-authors (role `author`) can access create route/form. The form is accessible from the story details page, in a new "Chapters" area below the "Summary" area.
- **Form fields and order**: The create form shows fields in this order: Title (text, required), Author Note (Quill editor, optional), Content (Quill editor, required), Published (toggle, default ON = published).
- **Editors**: Use Quill-based editor component (same stack as story summary in `app/Domains/Story/Views/components/form.blade.php`) with strict purifier profile from `config/purifier.php`.
- **Quill toolbar**: Buttons = bold, italic, underline, strikethrough, ordered list, unordered list, blockquote. Excluded: links, headings, images, code block, alignment.
- **Title**: Max 255 chars; server validation enforced.
- **Author Note**: Optional, sanitized via purifier strict profile; stored as TEXT (DB); functional max length 1000 characters for plain text content (HTML tags excluded from limit); server-side validation enforces the 1000-char logical limit.
- **Content**: Required, sanitized via purifier strict profile; stored as longtext; no max length limit.
- **Default status**: If the Published toggle remains ON, chapter is created as `published`; if OFF, as `not_published`.
- **Default order**: New chapter is appended at the end using sparse ordering (e.g., `max(sort_order) + 100`).
- **Slug**: Slug generated from title with `-id` suffix; uniqueness ensured by the ID suffix (no composite unique needed). 301 canonical redirect policy is defined in US-038.
- **Authorization**: Only story collaborators with role `author` can access create route/form.
- **Limits (deferred enforcement)**: Chapter cap enforcement is out of scope for Phase 3. Do not block creation yet; full cap logic and UX (including co-authors counting against each author) will be implemented in Stage 4.

**Implementation Notes:**

- Route: `GET /stories/{story-slug-with-id}/chapters/create` and `POST /stories/{story-slug-with-id}/chapters`.
- Controller validates inputs and sanitizes HTML via purifier strict config.
- Ordering via sparse increments (100). Index `(story_id, sort_order)` recommended.
- Slug generation mirrors stories: base slug + `-id` suffix, with canonical redirect on mismatch (US-038).
- Author Note storage: saved as TEXT with 1000-char plain-text limit (plain text, HTML stripped).

## ** [DONE] US-031: Edit Chapter Content**

**As a story author, I want to edit chapter content with rich text so that I can format my writing.**

**Acceptance Criteria:**

- **Editors**: Use Quill for both Author Note and Content.
- **Quill toolbar**: Buttons = bold, italic, underline, strikethrough, ordered list, unordered list, blockquote. Excluded: links, headings, images, code block, alignment.
- **Heights**: Author Note editor defaults to ~5 lines height; Content editor defaults to 50vh (fallback: 20 lines) for better writing comfort.
- **Validation**: Same as US-030 (Title required ≤255; Author Note optional ≤1000 logical chars; Content required; purifier strict profile).
- **Authorization**: Only authors/co-authors (role `author`) can edit.
- **UI**: Published toggle is present and reflects current state; changing it follows US-032 rules.
- **Access**: Only authors/co-authors (role `author`) can access edit route/form. Access can be done from chapter list on story page, through an edit icon.

**Implementation Notes:**

- Route: `GET /stories/{story}/chapters/{chapter}/edit` and `PUT/PATCH /stories/{story}/chapters/{chapter}`.
- Preserve user input on validation errors; tab/error indicators consistent with story forms.

## **[DONE] US-031-1: Chapter list on Story page**

**As a reader, I want to see a list of chapters on a story page so that I can navigate easily.**

**Acceptance Criteria:**

- **Placement**:
  - Rendered via partial `app/Domains/Story/Views/partials/chapters.blade.php`.
  - Included in `app/Domains/Story/Views/show.blade.php` below the story header/summary.

- **Visibility filtering**:
  - Guests and non-authorized users: show only chapters with `status = published`.
  - Authors/collaborators (authorized): show all chapters, including drafts.

- **Ordering**:
  - Always ascending by `sort_order`.

- **Displayed per chapter**:
  - Title only (no teaser, no dates, no read counts in this US).
  - If the chapter is not published and the current user is authorized, show a “Brouillon” chip.

- **Navigation**:
  - Clicking an item navigates to `chapters.show` with `storySlug` and `chapterSlug`.
  - Unpublished chapters are only visible/clickable for authorized users.

- **Empty state**:
  - If there are no chapters to display (per viewer permissions), show a single message (no CTA). The existing “Create” button elsewhere remains as-is.

- **No pagination / sorting controls**:
  - Do not paginate.
  - No UI to change order; always ascending.

- **Authorization**:
  - Use policy/role checks already in place for author/collaborator visibility.

- **i18n**:
  - Use Story domain translations:
    - `story::chapters.list.empty`
    - `story::chapters.list.draft` (value: “Brouillon”)

- **Accessibility**:
  - Render as a semantic list (`<ul>/<li>`), links with descriptive text, and accessible chip markup.

- **Performance**:
  - Query only required fields (id, title, slug, status, sort_order); do not load `content`.
  - Avoid N+1s; eager-load nothing extra unless needed.


**Controller supplies partial:**

- Controller (Story show action) provides the filtered and ordered chapters to the partial via the `StoryShowViewModel`:
  - Public viewers: published-only.
  - Authorized (author/collaborator): all chapters.

**Tests to add:**

- In `app/Domains/Story/Tests/Feature/StoryShowTest.php`:
  - Guest viewing a story with one published and one draft:
    - Sees only the published title; does not see “Brouillon”.
    - Sees empty message if no published chapters exist.
  - Author viewing the same:
    - Sees both published and draft titles; sees “Brouillon” chip on drafts.

## **[DONE] US-032: Publish/Unpublish Chapters**

**As a story author, I want to control chapter publication status so that I can manage what readers see.**

**Acceptance Criteria:**

- **States**: Only two statuses exist: `not_published` and `published`.
- **Transitions**: `not_published` ↔ `published`.
- **first_published_at**: Set only the first time a chapter is published. Subsequent unpublish/re-publish or content edits do not change it.
- **Story update**: When a chapter is first published, update `stories.last_chapter_published_at` to the max(`first_published_at`) across the story’s chapters (i.e., only if this publish is the latest).
- **Cap**: Cap enforcement is checked on chapter creation only (not on publish). Unpublishing does not refund or affect caps.
- **Audit**: Keep `created_at`/`updated_at` for edits; do not repurpose them for publication tracking.
- **Immutability**: Unpublishing or deleting a chapter does not modify `stories.last_chapter_published_at`.

**Implementation Notes:**

- Database: `chapters.first_published_at` (nullable timestamp) replaces `published_at` from preliminary spec.
- Maintain efficient query to compute latest first_published_at.

## **[DONE] US-033: Read Published Chapters**

**As a reader, I want to read published chapters so that I can enjoy the story.**

**Acceptance Criteria:**

- **Visibility enforcement**: Chapter access follows story visibility:
  - Public: anyone
  - Community: only users with role `user-confirmed`
  - Private: only authors/co-authors
- **Unpublished handling**: Non-authors requesting an unpublished chapter receive 404.
- **Authors**: Authors/co-authors can view unpublished chapters on the reading route as a preview; non-authors still receive 404 for unpublished (see US-034 for navigation).
- **TOC on story page**: Readers see only published chapters; authors may see full list with status badges (in story details UI, not the chapter page).

**Implementation Notes:**

- Route: `GET /stories/{story-slug-with-id}/chapters/{chapter-slug-with-id}` (see US-039).
- Unauthorized/protected content returns 404 (no existence leak), consistent with `docs/Feature_Planning/Story.md`.

## **[DONE] US-034: Navigate Between Chapters**

**As a reader, I want next/previous navigation so that I can easily move through the story.**

**Acceptance Criteria:**

- **Scope**: Navigation includes only published chapters ordered by `sort_order` ASC (authors also navigate across published only).
- **Ends**: First chapter disables Prev; last chapter disables Next (buttons disabled, not hidden).
- **TOC**: Full Table of Contents lives only on the story details page in Phase 3. A compact selector inside chapter pages may come later.

**Implementation Notes:**

- Precompute neighbor chapter ids via indexed `(story_id, sort_order)` queries.

## **[DONE] US-035: Reorder Chapters with Sparse Ordering**

**As an author/co-author, I want to reorder chapters efficiently so that large stories don't require renumbering
everything.**

**Acceptance Criteria:**

- Drag-and-drop or bulk reorder updates `sort_order` using sparse increments (e.g., 100)
- Reordering does not cause O(n) updates for all rows
- Readers see chapters in the new order immediately
- **Desktop vs Mobile**:
  - Desktop: drag-and-drop list with live preview.
  - Mobile: provide a “Reorder mode” with per-item Up/Down controls and an optional numeric position input. User taps “Reorder”, uses Up/Down buttons to move items without page refresh; when tapping “Done”, a single save applies the new order.
- **Authorization**: Authors/co-authors only; CSRF-protected endpoint.

**Implementation:**

- Implement reorder endpoint that assigns spaced order values to minimize churn
- Rebalance very occasionally when gaps are exhausted; O(n) rebalance acceptable as a rare maintenance action.
- Add index `(story_id, sort_order)`.

## **US-036: Read Counters (Guest vs Logged) and Stats**

**As an author, I want to see how many times a chapter was read so that I can understand engagement.**

**Acceptance Criteria:**

- **Counters**: Track two integers per chapter:
  - `reads_guest_count` (unsigned int) for anonymous reads
  - `reads_logged_count` (unsigned int) for logged-user reads
- **Scope**: Counts change only via explicit actions (see US-037): logged users toggling read/unread; guests clicking one-way mark-as-read. No page view counting.
- **Stats display**:
  - On chapter pages and in the story TOC (for everyone), show the total reads (guest + logged).
  - Clicking the total opens a popover (see `app/Domains/Shared/Resources/views/components/popover.blade.php`) with guest vs logged breakdown.
  - For logged non-author readers, the TOC shows a per-chapter read-status icon that can be clicked to toggle read/unread (see US-037).
- **No throttling**: MVP allows multiple guest increments; debouncing prevents rapid-fire requests during a single click.

**Implementation:**

- Database fields on `chapters`: `reads_guest_count` and `reads_logged_count` (unsigned int, default 0).
- Efficient atomic increments/decrements tied to logged toggle and guest one-way increment.
- TOC displays totals using the stored counts. Ignore reconciliation/data-drift for now; a maintenance job can be introduced later if needed.

**Endpoints (CSRF-protected):**
- `POST /stories/{story-slug-with-id}/chapters/{chapter-slug-with-id}/read` — Logged users: mark-as-read. Idempotent (no-op if already read). Increments `reads_logged_count` only when newly marked. Responds `204 No Content` on success.
- `DELETE /stories/{story-slug-with-id}/chapters/{chapter-slug-with-id}/read` — Logged users: mark-as-unread. Idempotent (no-op if already unread). Decrements `reads_logged_count` on successful unmark (not below zero). Responds `204 No Content` on success.
- `POST /stories/{story-slug-with-id}/chapters/{chapter-slug-with-id}/read/guest` — Guests: one-way increment of `reads_guest_count`. Responds `204 No Content` on success.

## **US-037: Manually Mark Chapter as Read (Readers)**

**As a reader, I want to manually mark a chapter as read so that I can track my progress.**

**Acceptance Criteria:**

- **Access**: Only available on published chapter pages. Not shown to authors/co-authors.
- **Logged users**: A toggle button reflects current state (read/unread). Clicking “Mark as read” creates `reading_progress (user_id, chapter_id, read_at)` and increments `reads_logged_count`. Clicking “Mark as unread” deletes that row and decrements `reads_logged_count` (not below zero). Actions are idempotent: attempting to mark as read when already read or mark as unread when already unread is a no-op.
- **Guests**: A one-way “Mark as read” button increments `reads_guest_count`. No per-guest persistence (cannot unmark).
- **UI**: Button shows “Read” state if logged user already marked it; guests always see it as not toggled. Add a brief debounce while requests are in-flight.
- **TOC toggle**: For logged non-author users, the TOC shows a read-status icon per chapter; clicking it toggles read/unread using the same backend endpoints.

**Implementation:**

- Endpoints:
  - `POST /stories/{story}/chapters/{chapter}/read` (logged mark-as-read, 204 on success)
  - `DELETE /stories/{story}/chapters/{chapter}/read` (logged mark-as-unread, 204 on success)
  - `POST /stories/{story}/chapters/{chapter}/read/guest` (guest one-way, 204 on success)
  - All CSRF-protected.
- Server rules and guards:
  - 403 for authors/co-authors attempting to call these endpoints.
  - Enforce story visibility (public/community/private) before acting.
  - Only published chapters are actionable; unpublished hide controls and backend rejects changes.
- `reading_progress` table: unique key `(user_id, chapter_id)`; FK to `story_chapters` with CASCADE on delete.
- Debounce client requests to prevent rapid re-clicks while a request is in-flight; also guard server-side with idempotency and non-negative counters.

## **US-038: Canonical Redirects for Chapter Slugs**

**As a reader, I want stable chapter URLs so that old links redirect correctly after title/slug changes.**

**Acceptance Criteria:**

- If the request path ends with a valid `-id` for the chapter but the base slug is outdated, respond with HTTP 301 to the canonical slug.
- This applies both to story slug and chapter slug; either mismatch triggers redirect to the canonical combined path.
- No composite unique index on `(story_id, slug)` is required because the `-id` suffix ensures uniqueness.

**Implementation Notes:**

- Shared helper for extracting IDs from slug-with-id and building canonical URLs.
- Add tests to assert 301 redirects for old slugs.

## **US-039: Chapter Routes Use "/chapters" Segment (Including Show)**

**As a reader, I want consistent chapter URLs so that create/edit/show share the same route pattern.**

**Acceptance Criteria:**

- Chapter show route is `GET /stories/{story-slug-with-id}/chapters/{chapter-slug-with-id}` (note the `chapters` segment).
- Create and Edit already use `/chapters`; align Show accordingly.
- Old pattern (if ever introduced) should 301 to the new canonical path.

**Implementation Notes:**

- Adjust route definitions and link generators. Ensure canonical redirect (US-038) still applies.

## **US-040: Database Adjustments for Chapters**

**As an engineer, I want the chapters schema to reflect Phase 3 behavior.**

**Acceptance Criteria / Notes:**

- `chapters` table:
  - `first_published_at` (nullable timestamp) replaces preliminary `published_at`.
  - `status` enum only: `not_published`, `published`.
  - `sort_order` integer with index `(story_id, sort_order)`.
  - `reads_guest_count` unsigned int default 0.
  - `reads_logged_count` unsigned int default 0.
  - Cascade delete on story -> chapters.
  - Storage: `author_note` as TEXT; `content` as LONGTEXT.
- `reading_progress` table:
  - FKs to `users`, `stories`, `chapters`; CASCADE on chapter delete.
  - Unique `(user_id, chapter_id)`.
- `stories` table:
  - Keep `last_chapter_published_at` updated only on first publishes (US-032).

## **US-041: Story Details and Chapter Page SEO**

**As a visitor, I want meaningful titles for chapters so that I understand the page content.**

**Acceptance Criteria:**

- Chapter `<title>`: "{Story Title} — {Chapter Title}" truncated to 160 characters (no HTML).
- No HTML meta description tag for now.
- OG/Twitter: keep title; image uses story cover (or default cover) — no chapter-specific image for now.

## **US-042: Stories Index Requires At Least One Published Public Chapter**

**As a visitor, I want the stories index to list only stories with at least one published public chapter so that listings reflect readable content.**

**Acceptance Criteria:**

- `/stories` shows only stories that are Public AND have ≥1 chapter with `status = published` (and story visibility = public).
- Filtering/sorting continues to work as in Phase 2.
- Empty state and SEO unchanged.

**Implementation Notes:**

- Update index query to `where(visibility = public)` AND `whereExists` published chapters.
- Optionally use `stories.last_chapter_published_at` to optimize listings.

---

## **US-043: Delete Chapter (Hard Delete)**

**As an author, I want to permanently delete a chapter so that I can remove content I no longer want to publish.**

**Acceptance Criteria:**

- **Authorization**: Authors/co-authors (role `author`) only.
- **Confirmation**: Requires explicit confirmation (2-step UI) before deletion.
- **Result**: Chapter is removed from the database permanently.
- **Ordering**: Leave gaps in `sort_order` values (do not renumber siblings).
- **Counters & Progress**: Delete associated `reading_progress` rows via FK cascade. Do not adjust `reads_guest_count`/`reads_logged_count` totals (historical counters remain on the deleted record are lost, which is acceptable).
- **Cap**: No refund/credit on delete; cap slot is not returned in Phase 3.
- **Redirect**: After delete, redirect to the story details page with success flash.

**Implementation Notes:**

- Route: `DELETE /stories/{story-slug-with-id}/chapters/{chapter-slug-with-id}`.
- CSRF-protected. Return 404 when unauthorized, consistent with domain policy.

## Deferred to Stage 4

- **Co-author chapter caps**: Creating a chapter by any co-author consumes one cap slot per author collaborator. Cap calculation and enforcement across collaborators to be implemented in Stage 4.
- **Archiving**: Story archiving and chapter archiving flows are out of scope for Phase 3.

## Notes

- Purification uses the strict profile from `config/purifier.php` for Author Note and Content.
- Quill configuration should mirror the story summary editor used in `app/Domains/Story/Views/components/form.blade.php`.
- Quill toolbar: buttons = bold, italic, underline, strikethrough, ordered list, unordered list, blockquote. Excluded: links, headings, images, code block, alignment.

## Decisions & Conventions (Phase 3)

- **Slug helper (shared)**: Implement a reusable helper under `app/Domains/Shared/Support/SlugWithId.php` to:
  - Parse IDs from `slug-with-id` strings (e.g., `my-title-123`).
  - Build canonical slugs from a base title and numeric id.
  - Provide utilities to compare a requested slug and the canonical slug (used by US-038/039 later).
- **Slug storage and uniqueness**: Chapters will store a full `slug` including the `-id` suffix (same strategy as stories). A global unique index on `chapters.slug` will be added (uniqueness ensured by id suffix).
- **Unauthorized handling**: For create/edit/delete routes, non-author collaborators receive 404 (not 403) to avoid existence leaks; consistent with Story domain policy.
- **Publication vs visibility**: Publication status is independent from story visibility. A published chapter in a private story is visible to all collaborators; unpublished chapters are visible only to authors/co-authors (via edit/preview), and 404 to others.
- **Author Note length**: Enforce 1000-character limit on logical plain text content. Implementation: sanitize HTML with the strict profile, strip tags, then measure and validate. Validation errors attach to the `author_note` field.
- **Editor heights**: Reuse existing shared editor component. Author Note defaults to a compact height (~5 lines). Content uses the standard large editor; explicit `50vh` sizing is not implemented at this time.
- **UI composition**: Create a dedicated Chapters partial included in the story details page (below “Summary”). This partial manages TOC, create entry point, and future chapter controls.
- **Post-create redirect**: After creating a chapter, redirect to the chapter show page (US-039 path) when available; validation errors keep user on the create form.
- **Translations**: Add chapter-related translations under `app/Domains/Story/Resources/lang/<locale>/chapters.php` and reuse `story::shared` keys when applicable.
- **Collaborators**: Use `Story::authors` relationship for collaborator checks and display where needed.

**ViewModel Composition:**

- `app/Domains/Story/ViewModels/ChapterSummaryViewModel.php`
  - Fields (read-only):
    - `id: int`
    - `title: string`
    - `slug: string` (slug-with-id)
    - `isDraft: bool` (derived from status)
    - `url: string` (prebuilt route to `chapters.show`)
  - Optional helpers: `ariaLabel()` for accessibility.

- `app/Domains/Story/ViewModels/StoryShowViewModel.php`
  - Exposes:
    - `story: Story`
    - `chapters: Collection<ChapterSummaryViewModel>` (already filtered for the current viewer and ordered ASC)
  - Controller builds the `StoryShowViewModel` and passes it to the view. The view includes the partial with `$vm->chapters`.
