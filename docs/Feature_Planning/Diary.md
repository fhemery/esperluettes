# Diary Feature Specification

## Overview
The Diary feature enables registered users to create personal blog entries that are displayed on their profile pages. These entries are private to the platform community and serve as personal expression and sharing within the verified user base.

## Core Features

### Article Management
- **Title**: Text field for the diary entry title
- **Slug**: Auto-generated from title using `spatie/laravel-sluggable`
- **Summary**: Short description/excerpt for profile tab display
- **Header Image**: Optional featured image with automatic processing via Intervention Image
- **Content**: Rich text editor using Quill.js with formatting options (headings, lists, links, etc.)
- **Pin Status**: Boolean flag to highlight important entries (future feature)
- **Draft/Published Status**: Entries can be saved as drafts or published
- **Privacy Level**: Always restricted to verified, logged-in users

### User Interface
- **Profile Integration**: Diary tab within existing profile page structure
- **Rich Text Editing**: Quill.js editor with toolbar for formatting
- **Image Upload**: Direct upload with automatic resizing and optimization
- **Preview Functionality**: Preview entries before publishing
- **Draft Auto-save**: Automatic saving of drafts while editing
- **Mobile Responsive**: Touch-friendly interface for mobile devices

### Display & Access

#### Profile Tab Display
- **Location**: Tab within user profile page (added below existing profile frame)
- **Default Tab**: Diary tab becomes the default tab when enabled
- **Toggle Feature**: Profile owners can enable/disable diary feature via profile settings
- **Access Control**: Only verified, logged-in users can view any diary
- **Content**: List of published diary entries for the profile owner
- **Sorting**: Descending order by creation date
- **Pagination**: Paginated list view within profile context

#### Individual Entry Pages (`/profile/<user-slug>/diary/<entry-slug>`)
- **URL**: `/profile/<user-slug>/diary/<entry-slug>`
- **Content**: Full diary entry display with rich formatting
- **Access Control**: Verified, logged-in users only
- **Navigation**: Back to profile, previous/next entries
- **No SEO**: No meta tags or search engine indexing (private content)

### Comment System
- **Access Control**: Only registered and verified users (email verification) can comment
- **Threading**: Nested/threaded replies supported
- **Pagination**: 10-15 comments per page (configurable parameter)
- **Moderation**: 
  - Users can flag inappropriate comments
  - Auto-hide comments after 2 flags (configurable via env variable: `DIARY_COMMENT_FLAG_THRESHOLD=2`)
  - Entry owner can moderate comments on their entries
  - Admins have moderation buttons directly on diary entries
- **Rich Text**: Basic formatting in comments
- **User Mentions**: Ability to mention other users
- **Reactions**: Like/support functionality for personal context
- **Draft Visibility**: Only authors can see their own diary drafts

## Technical Specifications

### Technology Stack
- **Rich Text Editor**: Quill.js
- **Image Processing**: Intervention Image (existing setup, 800px width limit)
- **Slug Generation**: `spatie/laravel-sluggable` (unique per user)
- **Comments**: `lakm/laravel-comments` package (configured for diary context)
- **Profile Integration**: Extends existing Profile domain views
- **Authentication**: Laravel's built-in auth with email verification requirement
- **Storage**: User-based folder structure (`storage/diary/<user-id>/`)
- **Content Storage**: HTML format (no versioning initially)

### Database Schema
```sql
diary_entries:
- id (primary key)
- user_id (foreign key to users)
- title (string)
- slug (string, unique per user)
- summary (text)
- content (longtext)
- header_image_path (string, nullable)
- is_pinned (boolean, default false)
- status (enum: draft, published)
- published_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)

# Composite unique index on (user_id, slug)

profile_profiles:
- diary_enabled (boolean, default false) # Toggle to activate diary feature per user
```

### Domain Structure
```
app/Domains/Diary/
├── Controllers/
│   ├── DiaryController.php (user views)
│   └── DiaryManagementController.php (user CRUD)
├── Models/
│   ├── DiaryEntry.php
│   └── DiaryComment.php
├── Services/
│   ├── DiaryService.php
│   └── DiaryCommentService.php
├── Requests/
│   ├── StoreDiaryEntryRequest.php
│   └── UpdateDiaryEntryRequest.php
├── Views/
│   ├── index.blade.php (profile tab)
│   ├── show.blade.php (individual entry)
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── components/
├── Resources/
│   └── lang/
└── Database/
    └── migrations/
```

### URL Structure
- `/profile/<user-slug>/diary` - User's diary tab (redirects to profile with diary tab active)
- `/profile/<user-slug>/diary/<entry-slug>` - Individual diary entry
- `/diary/create` - Create new diary entry
- `/diary/<entry-id>/edit` - Edit own diary entry

## User Stories

### Diary Author Stories
- As a user, I can create new diary entries with rich text content
- As a user, I can save diary entries as drafts before publishing
- As a user, I can upload header images for my diary entries
- As a user, I can edit and delete my own diary entries
- As a user, I can moderate comments on my diary entries
- As a user, I can view my diary entries listed on my profile

### Community User Stories
- As a verified user, I can browse other users' diary entries
- As a verified user, I can read full diary entries from any user
- As a verified user, I can comment on diary entries
- As a verified user, I can reply to comments on diary entries
- As a verified user, I can flag inappropriate comments
- As a verified user, I can react to diary entries and comments

### Privacy & Access Stories
- As a non-logged-in visitor, I cannot access any diary content
- As an unverified user, I cannot access diary content
- As a verified user, I can access all published diary content from any user

## Security Considerations
- Strict access control: verified users only (email verification)
- Users can only edit/delete their own diary entries
- Comment moderation by entry owner and admin oversight
- Image upload validation and processing (800px width limit)
- XSS protection in rich text content
- Rate limiting on entry and comment submissions
- No search engine indexing (robots.txt, meta noindex)
- Configurable flag thresholds via environment variables
- Admin moderation buttons for oversight

## Privacy Considerations
- No public access to diary content
- No SEO optimization (content remains private to community)
- User control over their own content
- Ability to delete entries and associated comments
- No external sharing capabilities initially

## Performance Considerations
- Pagination for diary listings on profile pages
- Image optimization and storage (800px width limit)
- Database indexing on user_id and status fields
- Lazy loading of comments (10-15 per page)
- Efficient queries for profile tab display
- User-based storage directory structure (`diary/<user-id>/`)
- Profile diary toggle to reduce unnecessary queries

## Integration Points

### Profile Domain Integration
- Add diary tab to existing profile page layout
- Extend profile navigation to include diary section
- Integrate with existing profile routing structure
- Use existing profile authentication and access controls

### User Experience Flow
1. User enables diary feature in their profile settings (toggle)
2. User navigates to their profile or another user's profile
3. Diary tab appears (becomes default tab when enabled)
4. Views paginated list of published diary entries
5. Can click to read full entries
6. Can create new entries (if own profile and diary enabled)
7. Can comment and interact (if verified user)
8. Admin users see moderation buttons on diary entries

## Future Enhancements
- Mood tracking and emotional context
- Private diary entries (visible only to author)
- Friend/follower system with restricted access
- Diary entry templates
- Export functionality
- Rich media embedding (videos, audio)
- Collaborative diary entries
- Diary entry scheduling
- Personal analytics (writing streaks, word counts)
