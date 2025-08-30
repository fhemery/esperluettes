# Story & Chapter Sharing Feature Specification

## Overview
The Story & Chapter sharing feature is the core functionality of the platform, enabling users to create, publish, and share stories with rich configuration options and chapter management. Stories can be commented on by the community, with comprehensive visibility controls and metadata management.

## Core Features

### Story Management

#### Basic Story Fields
- **Title**: Text field for the story title
- **Description**: Rich text description of the story
- **Cover Image**: Optional story cover with automatic processing via Intervention Image
- **Co-Authors**: Support for multiple authors collaborating on a single story
- **Visibility**: Three levels of access control (independent of chapter publication status)
  - **Public**: Accessible to everyone, including non-members
  - **Community**: Accessible only to users with role `user-confirmed`
  - **Private**: Visible only to author(s) and co-authors
  
#### Deletion
- **Delete Story**: Author-only hard delete. When confirmed, the story is permanently removed along with its pivot links (genres, trigger warnings) via database cascading.

#### Story Configuration Options

##### Mandatory Fields
- **Story Type**: Single choice from `StoryRefType` (mandatory)
- **Genre**: Multiple choice from `StoryRefGenre` (1 minimum, 3 maximum)
- **Target Audience**: Single choice from `StoryRefAudience` (mandatory)
- **Copyright**: Single choice from `StoryRefCopyright` (mandatory)

##### Optional Fields
- **Writing Status**: Single choice from `StoryRefStatus` (optional)
- **Feedback Type**: Single choice from `StoryRefFeedback` (optional)
- **Trigger Warnings**: Multiple choice from `StoryRefTriggerWarning` (optional)

### Chapter Management

#### Chapter Structure
- **Title**: Text field for chapter title
- **Author Note**: Rich text editor with basic formatting options
- **Chapter Content**: Rich text editor with basic formatting options
- **Publication Status**: Two states
  - **Not Published**: Draft state, not visible to readers
  - **Published**: Live and accessible based on story visibility

#### Chapter Operations
- **Add Chapter**: Create new chapters for a story (chapter caps deferred to Stage 4)
- **Edit Chapter**: Modify existing chapter content and metadata
- **Reorder Chapters**: Bulk reordering functionality for chapter sequence
- **Delete Chapter**: Permanent removal (hard delete)

#### Chapter Creation Limits (Deferred to Stage 4)
Chapter caps and related UX are deferred to Stage 4. No limits are enforced in Phase 3.

### Reading & Viewing Experience
 - **Story Listing**: Browse available stories with filtering and sorting options
 - **Story Detail**: View story metadata, description, and table of contents
 - **Chapter Reading**: Sequential chapter reading with next/previous navigation
 - **Reading Progress**:
   - Logged users can manually mark a chapter as read/unread. Read/unread actions are idempotent: attempting to mark as read when already read (or unread when already unread) is a no-op.
   - Guests can click "Mark as read" to increment guest reads. One-way; no per-guest persistence or unmarking.
 - **Trigger Warning Display**: Prominently displayed on story description and search results
 - **Comment System**: Community commenting on stories and chapters (future phase)
 - **Stats Display**:
   - Show total reads (guest + logged) to everyone on chapter pages and in the story TOC.
   - Clicking the total opens a popover (`app/Domains/Shared/Resources/views/components/popover.blade.php`) with guest vs logged breakdown.
   - For logged non-author readers, the TOC shows a per-chapter read-status icon that can be clicked to toggle read/unread.


## Technical Specifications

### Technology Stack
- **Rich Text Editor**: Quill (mirrors existing Story summary editor). Strict HTML sanitization via `config/purifier.php` (strict profile). Toolbar buttons: bold, italic, underline, strikethrough, ordered list, unordered list, blockquote. Excluded: links, headings, images, code block, alignment.
- **Image Processing**: Intervention Image (existing setup, 800px width limit)
- **Admin Panel**: Filament integration for story reference data management
- **Storage**: `storage/app/public/stories/` for covers. No chapter asset uploads initially; if added later, use `storage/app/public/stories/chapters/`.

### Database Schema (Preliminary)

#### Stories Table
```sql
stories:
- id (primary key)
- created_by_user_id (foreign key to users)        -- immutable; audit only, not used for permissions
- title (string)
- slug (string, unique globally; includes the numeric id suffix, e.g., "my-story-title-123")
- description (longtext)
- cover_image_path (string, nullable)
- **visibility** (enum: public, community, private)
- **story_ref_type_id** (foreign key, mandatory, NOT NULL)
- **story_ref_audience_id** (foreign key, mandatory, NOT NULL)
- **story_ref_copyright_id** (foreign key, mandatory, NOT NULL)
- story_ref_status_id (foreign key, nullable)
- story_ref_feedback_id (foreign key, nullable)
- last_chapter_published_at (timestamp, nullable, for sorting)
- created_at (timestamp)
- updated_at (timestamp)
```

#### Story Collaborators Table (extensible: co-authors now, beta readers later)
```sql
story_collaborators:
- story_id (foreign key)
- user_id (foreign key)
- role (enum: author, beta_reader)              -- beta_reader reserved for a future feature
- invited_by_user_id (foreign key to users)        -- who initiated the invite
- invited_at (timestamp)
- accepted_at (timestamp, nullable)
- left_at (timestamp, nullable)
- expires_at (timestamp, nullable)                 -- for time-bound access (e.g., beta readers)
- unique key (story_id, user_id)                   -- a user has a single role per story
```

Notes:
- On story creation, also insert a row in `story_collaborators` for the creator with `role = author`, `invited_by_user_id = created_by_user_id`, `invited_at = now`, `accepted_at = now`.
- Authorization is based solely on `story_collaborators.role` (e.g., `author`), not on `stories.created_by_user_id`.

#### Reading Progress Table
```sql
reading_progress:
- id (primary key)
- user_id (foreign key)
- story_id (foreign key)
- chapter_id (foreign key)
- read_at (timestamp)
- unique key (user_id, chapter_id)
```

#### User Domain Stats Table
```sql
user_domain_stats:
- id (primary key)
- user_id (foreign key, indexed)
- domain (string, indexed)               -- e.g., 'story'
- stat_key (string, indexed)             -- e.g., 'current_available_chapters'
- stat_value (integer)                   -- integer for chapter caps
- updated_at (timestamp)
- unique key (user_id, domain, stat_key)
```
Notes:
- `current_available_chapters` is stored as `('story', 'current_available_chapters')`.
- Updated by services and real-time projections that consume the audit log (`App\Domains\Shared\Models\DomainEvent`).

#### Story Genre Pivot Table
```sql
story_story_ref_genre:
- story_id (foreign key)
- story_ref_genre_id (foreign key)
- primary key (story_id, story_ref_genre_id)
```

#### Story Trigger Warning Pivot Table
```sql
story_story_ref_trigger_warning:
- story_id (foreign key)
- story_ref_trigger_warning_id (foreign key)
- primary key (story_id, story_ref_trigger_warning_id)
```

#### Chapters Table
```sql
chapters:
- id (primary key)
- story_id (foreign key to stories)
- title (string)
- slug (string, unique within story; includes numeric id suffix, e.g., "chapter-one-45")
- author_note (text, nullable)
- content (longtext)
- sort_order (integer)
- status (enum: not_published, published)
- first_published_at (timestamp, nullable)
- reads_guest_count (unsigned integer, default 0)
- reads_logged_count (unsigned integer, default 0)
- created_at (timestamp)
- updated_at (timestamp)
```

### Domain Structure
```
app/Domains/Story/
├── Controllers/
│   ├── StoryController.php (public views)
│   └── ChapterController.php (public views)
├── Models/
│   ├── Story.php
│   └── Chapter.php
├── Services/
│   ├── StoryService.php
│   └── ChapterService.php
├── Requests/
│   ├── StoryRequest.php
│   └── ChapterRequest.php
├── Views/
│   ├── stories/
│   │   ├── index.blade.php
│   │   ├── show.blade.php
│   │   └── create.blade.php
│   └── chapters/
│       ├── show.blade.php
│       └── edit.blade.php
├── Providers/
│   └── StoryServiceProvider.php  # Registers domain-scoped views and PHP translations (namespace: "story")
├── Resources/
│   └── lang/                     # Pure PHP translations loaded via provider, e.g., resources/lang/fr/story.php
└── Database/
    └── migrations/
```

### URL Structure
- `/stories/` - Story listing page with filtering and sorting (excludes stories without any published public chapter)
- `/stories/create` - Create new story (authenticated)
- `/stories/<slug-with-id>` - Individual story page with table of contents (e.g., `my-story-title-123`)
- `/stories/<story-slug-with-id>/chapters/<chapter-slug-with-id>` - Individual chapter page with navigation (e.g., `my-story-title-123/chapters/chapter-one-45`)
- `/stories/<slug-with-id>/edit` - Edit story (author/co-author only)
- `/stories/<slug-with-id>/co-authors` - Manage co-authors (authors and co-authors)
- `/stories/<story-slug-with-id>/chapters/create` - Add new chapter (author/co-author)
- `/stories/<story-slug-with-id>/chapters/<chapter-slug-with-id>/edit` - Edit chapter (author/co-author only)

#### Read/Stats Endpoints (CSRF-protected)
- `POST /stories/{story-slug-with-id}/chapters/{chapter-slug-with-id}/read` — Logged users: mark-as-read. Idempotent (no-op if already read). Increments `reads_logged_count` only when newly marked. Responds `204 No Content` on success.
- `DELETE /stories/{story-slug-with-id}/chapters/{chapter-slug-with-id}/read` — Logged users: mark-as-unread. Idempotent (no-op if already unread). Decrements `reads_logged_count` on successful unmark (not below zero). Responds `204 No Content` on success.
- `POST /stories/{story-slug-with-id}/chapters/{chapter-slug-with-id}/read/guest` — Guests: one-way increment of `reads_guest_count`. Responds `204 No Content` on success.

Notes:
- When a story title changes, the slug base changes but the `-id` suffix remains. Visiting an old slug that ends with the correct `-id` performs a 301 redirect to the canonical, updated slug.
- When a chapter title changes, the chapter slug base changes but the `-id` suffix remains. Visiting an old chapter slug (with correct `-id`) performs a 301 redirect to the canonical chapter URL. This canonicalization applies independently to both story and chapter slug segments.

### SEO
- Chapter page `<title>`: "{Story Title} — {Chapter Title}" truncated to 160 characters (no HTML).

## User Stories

### Author User Stories
- As an author, I can create a new story with all configuration options
- As an author, I can invite co-authors to collaborate on my stories
- As an author, I can set visibility levels for my stories (independent of chapter status)
- As an author, I can add chapters to my stories
- As an author, I can increase my chapter limit by commenting on other stories
- As an author, I can reorder chapters in bulk
- As an author, I can edit my story metadata and chapters at any time
- As an author, I can delete chapters
- As an author, I can control publication status of individual chapters
- As a co-author, I can edit story content, add chapters, and invite co-authors; even the initial author can leave the story

### Reader User Stories
- As a reader, I can browse public stories without authentication
- As a member, I can access community-visible stories
- As a reader, I can filter stories by genre, type, audience, status, and trigger warnings
- As a reader, I can sort stories by latest update, random, or recommended
- As a reader, I can view story details with prominent trigger warning display
- As a reader, I can navigate through chapters with table of contents
- As a reader, I can use next/previous buttons while reading chapters
- As a logged user, I can manually mark chapters as read

### Admin User Stories
- As an admin, I can manage story reference data (types, genres, statuses, etc.)
- As an admin, I can moderate stories and chapters if needed

## Security Considerations
- Authentication required for story creation and editing
- Author-only access to story/chapter management
- Visibility controls enforced at database query level (community requires role `user-confirmed`)
- XSS protection in rich text content
- Image upload validation and processing
  - Chapter embedded images are not supported initially; if introduced later, they will be stored under `storage/app/public/stories/chapters/`

Unauthorized access handling:
- For protected resources (e.g., private/community content without proper rights, or edit routes when not an author collaborator), the application returns 404 to avoid leaking existence.
- Mark-as-read endpoints are forbidden for authors and co-authors (return 403 when attempted).

## Performance Considerations
- Database indexing on slug, visibility, and user_id fields
- Pagination for story listings
- Image optimization and CDN delivery
- Efficient chapter ordering queries (use sparse ordering increments on `sort_order`, e.g., steps of 100, to minimize renumbering on reorder)

## Clarified Requirements Summary

### ✅ Resolved Questions
- **Multi-Author Support**: Stories can have multiple co-authors with collaboration features
- **Chapter Titles**: Authors have complete control over chapter titles (no auto-numbering)
- **Publication Model**: Story visibility is independent of chapter publication status
- **Metadata Editing**: No fields are locked - authors can edit everything at any time
-- **Chapter Limits**: Deferred to Stage 4 (no enforcement in Phase 3)
- **Reading Progress**: Tracked for logged users
- **Trigger Warnings**: Prominently displayed, no pop-up modals
- **Content Moderation**: Community-based reporting by verified users
- **Rich Text Editor**: Quill with strict purifier; basic formatting; no links or headings; include blockquote
- **Search**: Title/description + metadata filtering only (no full-text search)
- **Chapter Navigation**: Next/previous buttons + table of contents
- **Story Discovery**: Sort by latest update, random, recommended + extensive filtering

## Outstanding Questions

### ✅ Final Clarifications

#### Co-Author Management
- **Invitation Process**: Purely in-app notifications (no email)
- **Permission Levels**: All co-authors have equal rights
- **Removal Process**: No one can remove co-authors after acceptance; co-authors can leave voluntarily (creates copyright management concerns)

#### Chapter Limit System (Deferred to Stage 4)
- **Comment Counter**: Separate counter that increases with each comment made on other stories
- **Comment Deletion**: Users cannot delete comments made on their stories; deleted stories still count toward commenter's limit
- **Gaming Prevention**: Community reporting system for fake comments
- **Retroactive Limits**: Block new chapter creation if limit exceeded; cannot publish/de-archive chapters when over limit

#### Reading Progress & Reporting
- **Progress Definition**: Manual "mark as read" for logged users; no anonymous persistence
 - Overall reads are tracked via `reads_guest_count` and `reads_logged_count` (not page views)
- **Report Handling**: Moderation team processes all reports (no automation initially)
- **Verified Status**: Email-verified users can report content
- **AI Cover Reporting**: Users can report AI-generated covers on story pages

#### Technical Architecture
- **Notifications**: Activity log system with events (no real-time)
- **Event System**: Story domain throws events, Activity and Admin domains catch them
- **Recommendation Algorithm**: Deferred to future wishlist
 - **Stats Projection**: A projector listens to domain events and updates `user_domain_stats` accordingly; backfill commands can replay the audit log to recompute stats.

## Feature to be defined later
- Reporting system (including reporting AI-generated covers)

## Future Enhancements
- Comment system for stories and chapters
- Rating and review system
- Story collections and series management
- Advanced search and filtering
- Reading lists and bookmarks
- Author following system
- Story statistics and analytics
- Email notifications for new chapters
- Mobile app support
- Story export functionality
- Collaborative editing features

## Final Architecture Recommendation

Given the complexity and interconnected nature of this domain (stories, chapters, co-authors, reading progress, limits, reporting, events), I recommend:

### **Event-Driven Enhanced MVC with Domain Services**

#### **Core Structure**
- **Enhanced MVC**: Familiar Laravel structure with rich service layer
- **Event-Driven**: Laravel events for cross-domain communication
- **Domain Services**: Specialized services for complex business logic

#### **Service Architecture**
```
app/Domains/Story/
├── Services/
│   ├── StoryService.php           # Core CRUD + business logic
│   ├── ChapterService.php         # Chapter management + ordering
│   ├── CoAuthorService.php        # Collaboration management
│   ├── ReadingProgressService.php # Progress tracking
│   ├── ChapterLimitService.php    # Dynamic limit calculation
│   ├── StoryDiscoveryService.php  # Filtering + sorting
│   └── ReportingService.php       # Content moderation
├── Events/
│   ├── StoryCreated.php
│   ├── ChapterPublished.php
│   ├── CoAuthorInvited.php
│   ├── StoryReported.php
│   └── CommentMadeOnStory.php     # For limit calculation
├── Listeners/
│   └── UpdateChapterLimitCounter.php
```

#### **Why This Architecture?**
1. **Event-Driven Benefits**:
   - Clean separation between Story domain and Activity/Admin domains
   - Easy to add new listeners without modifying core logic
   - Perfect for your activity log requirements

2. **Service Layer Benefits**:
   - Single responsibility per service
   - Testable business logic
   - Reusable across controllers, commands, jobs

3. **Laravel Native**:
   - Uses Laravel's event system naturally
   - Familiar MVC structure
   - Easy to maintain and extend

#### **Event Flow Example**
```php
// When a comment is made on a story
CommentMadeOnStory::dispatch($story, $comment, $user);

// Listeners:
// 1. Story domain: UpdateChapterLimitCounter
// 2. Activity domain: LogCommentActivity  
// 3. Admin domain: UpdateModerationStats
```

#### **Implementation Strategy**
1. **Phase 1**: Core MVC structure with basic services
2. **Phase 2**: Add event system for cross-domain communication
3. **Phase 3**: Enhance services with complex business logic
4. **Phase 4**: Add activity logging and admin integration

**This approach gives you the structure needed for complexity while maintaining Laravel conventions and enabling clean cross-domain communication through events.**

## Dependencies
- Existing StoryRef models and data
- User authentication and verification system
- Quill rich text editor setup + HTMLPurifier strict profile
- Intervention Image processing
- Filament admin panel integration
- Comment system (for chapter limit calculation)
- Activity domain (for event logging)
- Laravel event system
- User roles and permissions system
