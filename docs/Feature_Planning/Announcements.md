# Announcements Feature Specification

## Overview
The Announcements feature enables administrators to create, manage, and publish announcements that are displayed publicly on the website. These announcements serve as official communications and content for the platform.

## Core Features

### Article Management
- **Title**: Text field for the announcement title
- **Slug**: Auto-generated from title using `spatie/laravel-sluggable`
- **Summary**: Short description/excerpt for previews and carousel display
- **Header Image**: Optional featured image with automatic processing via Intervention Image
- **Content**: Rich text editor using Quill.js with formatting options (headings, lists, links, etc.)
- **Pin Status**: Boolean flag to determine if article appears on homepage carousel
- **Display Order**: Integer field to control order of pinned articles in carousel
- **Draft/Published Status**: Articles can be saved as drafts or published
- **SEO Meta**: Meta description and Open Graph tags for social sharing

### Admin Interface
- **Filament Integration**: Full CRUD operations within existing Filament admin panel
- **Rich Text Editing**: Quill.js editor with toolbar for formatting
- **Image Upload**: Direct upload with automatic resizing and optimization
- **Preview Functionality**: Preview articles before publishing
- **Bulk Operations**: Bulk publish/unpublish/delete operations
- **Draft Auto-save**: Automatic saving of drafts while editing

### Public Display

#### Homepage Carousel
- **Location**: Displayed on the main homepage
- **Content**: Shows pinned articles only
- **Display Elements**: Header image, title, and summary
- **Behavior**: Auto-advancing carousel
- **Responsive**: Mobile-friendly design
- **Order**: Controlled by admin-defined display order

#### Announcements Listing Page (`/announcements/`)
- **URL**: `/announcements/`
- **Content**: All published announcements
- **Sorting**: Descending order by creation date
- **Pagination**: Paginated list view
- **SEO**: Proper meta tags and structured data

#### Individual Article Pages (`/announcements/<slug>`)
- **URL**: `/announcements/<article-slug>`
- **Content**: Full article display with rich formatting
- **SEO**: Complete meta tags, Open Graph, and structured data
- **Social Sharing**: Deferred (out of scope for MVP)
- **Public Access**: No authentication required

### Comment System (Deferred)
- Implementation deferred until admin and display are complete.
- When implemented later:
  - Reading and posting should be restricted to logged-in, email-verified users.
  - Consider an in-house, reusable comments domain for cross-feature reuse; evaluate packages first and fallback to custom if they fall short.
  - Features such as threading, flagging with thresholds, moderation queue, mentions, and reactions can be prioritized based on needs.

## Technical Specifications

### Technology Stack
- **Rich Text Editor**: Quill.js
- **Image Processing**: Intervention Image (existing setup, 800px width limit)
- **Slug Generation**: `spatie/laravel-sluggable` (globally unique)
- **Admin Panel**: Filament integration
- **SEO**: See SEO Strategy below
- **Storage**: `storage/app/public/announcements/` (public disk, symlinked to `public/storage`)
- **Content Storage**: HTML format (no versioning initially)

### Database Schema
```sql
announcements:
- id (primary key)
- title (string)
- slug (string, unique globally)
- summary (text)
- content (longtext)
- header_image_path (string, nullable)
- is_pinned (boolean, default false)
- display_order (integer, nullable)
- status (enum: draft, published)
- meta_description (string, nullable)
- published_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
- created_by (foreign key to users)
```

### Domain Structure
```
app/Domains/Announcement/
├── Controllers/
│   └── AnnouncementController.php (public views)
├── Models/
│   └── Announcement.php
├── Services/
│   └── AnnouncementService.php
├── Requests/
│   └── AnnouncementRequest.php
├── Views/
│   ├── index.blade.php (/announcements/)
│   ├── show.blade.php (/announcements/<slug>)
│   └── components/carousel.blade.php
├── Resources/
│   └── lang/
└── Database/
    └── migrations/

app/Domains/Admin/
└── Resources/
    └── AnnouncementResource.php (Filament resource pointing to `Announcement` model)
```

### URL Structure
- `/announcements/` - Announcements listing page
- `/announcements/<slug>` - Individual announcement page
- `/admin/announcements` - Filament admin interface

## User Stories

### Admin User Stories
- As an admin, I can create new announcements with rich text content
- As an admin, I can save announcements as drafts before publishing
- As an admin, I can pin important announcements to the homepage carousel
- As an admin, I can control the order of pinned announcements
- As an admin, I can upload and manage header images for announcements
- As an admin, I can bulk manage multiple announcements

### Public User Stories
- As a visitor, I can view the latest announcements on the homepage carousel
- As a visitor, I can browse all announcements on the announcements page
- As a visitor, I can read full announcements without authentication
  
Comment-related user stories are deferred until the comment system is implemented.

## Security Considerations
- Only admin users can create/edit announcements
- Image upload validation and processing (800px width limit)
- XSS protection in rich text content
  
Comment-related security items will be added when comments are implemented. Reading comments will require authentication with verified email.

## Performance Considerations
- Caching of homepage carousel data
- Image optimization and CDN delivery (800px width limit)
- Pagination for announcements listing
- Database indexing on slug and status fields
- Separate storage directory for announcements

## Future Enhancements
- Categories and tags for announcements
- Email notifications for new announcements
- RSS feed generation
- Advanced analytics and engagement tracking
- Scheduled publishing
- Multi-language support
- Full comment system (threading, flags with thresholds, moderation queue, mentions, reactions)

## Preview Behavior
- Draft articles are viewable by admins only within Filament and public views guarded by authorization.
- Public routes never display drafts. Draft pages show a clear "Draft" badge/caption for admins.
- No signed preview URLs in MVP; can be considered later if needed.

## HTML Sanitization Strategy
- Store content as HTML but sanitize on save and/or render.
- Recommended: use `mews/purifier` (HTMLPurifier) with a strict allowed list (headings, paragraphs, lists, links, basic formatting, images if needed). Configure a dedicated profile for announcements.
- Disable dangerous elements/attributes (scripts, events like `onload`, iframes unless explicitly allowed).
- If we later standardize on ProseMirror, its schema can enforce valid nodes/marks and reduce sanitizer surface.

## SEO Strategy
- We will use manual tags in Blade for MVP: meta title/description and Open Graph/Twitter tags per view (listing and show).
- Provide small helpers/partials to avoid duplication. If needs grow, we may later adopt a package (e.g., `spatie/laravel-seo`), but it's out of scope for now.

## Image Handling
- Store originals to `storage/app/public/announcements/` and generate responsive variants.
- Variants (MVP):
  - 400w (mobile-first)
  - 800w (default)
  - WebP generation alongside original format when possible
- Provide `srcset` on public pages to improve loading on mobile. Larger desktop variants can be added later if needed.
- Consider focal cropping for carousel visuals in a future iteration.

## Carousel Accessibility
- Provide visible controls: Previous, Next, and Pause/Play.
- Respect user reduced-motion preference (prefers-reduced-motion): disable auto-advance when enabled.
- Ensure keyboard navigation: focusable controls with logical tab order, arrow-key support if applicable.
- Add appropriate ARIA roles/labels for slides and controls; announce slide changes for screen readers.

## Database Indexes and Constraints
- Indexes:
  - `slug` unique index (already unique)
  - Composite index on `(status, published_at DESC)` to speed listing queries
  - Index on `is_pinned` and `display_order` for carousel queries
  - Index on `published_at` for date-range queries and sorting
- Constraints and rules:
  - `display_order` must be unique among records where `is_pinned = true`; allow `NULL` for unpinned items
  - When unpinning an item, `display_order` should be set to `NULL`
  - Validate that `published_at` is set when status transitions to `published`

## Translations (i18n)
- Continue hybrid strategy:
  - JSON `fr.json` for generic strings
  - PHP namespaced files under `app/Domains/Announcement/Resources/lang` for domain-specific strings
- Namespace: `announcement` for public strings; Filament admin continues to use the `admin` namespace.

## Editor Strategy
- Use Filament RichEditor for announcement content editing (Quill-like toolbar) for MVP.
- We may integrate Quill.js later if needed; current sanitization/profile is compatible with RichEditor output.
