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

#### Blog Listing Page (`/blog/`)
- **URL**: `/blog/`
- **Content**: All published announcements
- **Sorting**: Descending order by creation date
- **Pagination**: Paginated list view
- **SEO**: Proper meta tags and structured data

#### Individual Article Pages (`/blog/<slug>`)
- **URL**: `/blog/<article-slug>`
- **Content**: Full article display with rich formatting
- **SEO**: Complete meta tags, Open Graph, and structured data
- **Social Sharing**: Share buttons for social platforms
- **Public Access**: No authentication required

### Comment System
- **Access Control**: Only registered and verified users (email verification) can comment
- **Threading**: Nested/threaded replies supported
- **Pagination**: 20-30 comments per page (configurable parameter), viewable only by verified users
- **Moderation**: 
  - Users can flag inappropriate comments
  - Auto-hide comments after 3 flags (configurable via env variable: `ANNOUNCEMENT_COMMENT_FLAG_THRESHOLD=3`)
  - Admin review queue for flagged content
- **Rich Text**: Basic formatting in comments
- **User Mentions**: Ability to mention other users
- **Reactions**: Like/dislike functionality
- **Draft Visibility**: Only admins can see draft announcements

## Technical Specifications

### Technology Stack
- **Rich Text Editor**: Quill.js
- **Image Processing**: Intervention Image (existing setup, 800px width limit)
- **Slug Generation**: `spatie/laravel-sluggable` (globally unique)
- **Comments**: `lakm/laravel-comments` package
- **Admin Panel**: Filament integration
- **SEO**: `spatie/laravel-meta` (optional)
- **Storage**: Separate folder structure (`storage/announcements/`)
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
│   ├── AnnouncementController.php (public views)
│   └── Admin/AnnouncementResource.php (Filament)
├── Models/
│   ├── Announcement.php
│   └── AnnouncementComment.php
├── Services/
│   ├── AnnouncementService.php
│   └── AnnouncementCommentService.php
├── Requests/
│   └── AnnouncementRequest.php
├── Views/
│   ├── index.blade.php (/blog/)
│   ├── show.blade.php (/blog/<slug>)
│   └── components/carousel.blade.php
├── Resources/
│   └── lang/
└── Database/
    └── migrations/
```

### URL Structure
- `/blog/` - Blog listing page
- `/blog/<slug>` - Individual announcement page
- `/admin/announcements` - Filament admin interface

## User Stories

### Admin User Stories
- As an admin, I can create new announcements with rich text content
- As an admin, I can save announcements as drafts before publishing
- As an admin, I can pin important announcements to the homepage carousel
- As an admin, I can control the order of pinned announcements
- As an admin, I can upload and manage header images for announcements
- As an admin, I can moderate comments and manage flagged content
- As an admin, I can bulk manage multiple announcements

### Public User Stories
- As a visitor, I can view the latest announcements on the homepage carousel
- As a visitor, I can browse all announcements on the blog page
- As a visitor, I can read full announcements without authentication
- As a registered user, I can comment on announcements
- As a registered user, I can reply to other comments
- As a registered user, I can flag inappropriate comments

## Security Considerations
- Only admin users can create/edit announcements
- Comment moderation to prevent spam and abuse
- Image upload validation and processing (800px width limit)
- XSS protection in rich text content
- Rate limiting on comment submissions
- Email verification required for commenting
- Configurable flag thresholds via environment variables

## Performance Considerations
- Caching of homepage carousel data
- Image optimization and CDN delivery (800px width limit)
- Pagination for blog listing
- Database indexing on slug and status fields
- Lazy loading of comments (20-30 per page)
- Separate storage directory for announcements

## Future Enhancements
- Categories and tags for announcements
- Email notifications for new announcements
- RSS feed generation
- Advanced analytics and engagement tracking
- Scheduled publishing
- Multi-language support
