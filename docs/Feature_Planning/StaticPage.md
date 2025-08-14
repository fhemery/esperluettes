# Static Page Feature Specification

## Overview
The Static Page feature enables administrators to create, manage, and publish static content pages that are accessible directly via their slug URLs (e.g., `/faq`, `/legal-notice`). These pages serve as permanent content like legal notices, rules, FAQ, writing guidelines, and other informational content.

## Core Features

### Page Management
- **Title**: Text field for the page title
- **Slug**: Auto-generated from title using `spatie/laravel-sluggable` (globally unique)
- **Summary**: Short description/excerpt for SEO meta description
- **Header Image**: Optional featured image with automatic processing via shared ImageService
- **Content**: Rich text editor using the same component as News domain
- **Draft/Published Status**: Pages can be saved as drafts or published
- **SEO Meta**: Meta description and Open Graph tags for social sharing
- **Last Updated Display**: Show when page was last modified

### Admin Interface
- **Filament Integration**: Full CRUD operations within existing Filament admin panel
- **Rich Text Editing**: Same editor component as News with formatting options
- **Image Upload**: Direct upload with automatic resizing via shared ImageService
- **Preview Functionality**: Preview pages before publishing
- **Bulk Operations**: Bulk publish/unpublish/delete operations
- **Draft Auto-save**: Automatic saving of drafts while editing
- **Alphabetical Sorting**: Pages sorted alphabetically in admin interface

### Public Display

#### Direct URL Access
- **URL Pattern**: `/{slug}` (e.g., `/faq`, `/legal-notice`, `/writing-guidelines`)
- **Content**: Full page display with rich formatting
- **SEO**: Complete meta tags, Open Graph, and structured data
- **Public Access**: No authentication required
- **Last Updated**: Display last modification date

#### Navigation Integration
- **Manual Links**: Links to static pages will be manually added to footer, menus, etc.
- **No Automatic Menu**: No automatic navigation generation

## Technical Specifications

### Technology Stack
- **Rich Text Editor**: Same component as News domain (reusable admin component)
- **Image Processing**: Shared ImageService (existing setup, 800px width limit)
- **Slug Generation**: `spatie/laravel-sluggable` (globally unique)
- **Admin Panel**: Filament integration
- **SEO**: Meta tags, Open Graph, structured data
- **Storage**: `storage/app/public/static-pages/` (public disk, symlinked to `public/storage`)
- **Content Storage**: HTML format (no versioning initially)
- **Caching**: Route caching to avoid dynamic route generation on each request

### Database Schema
```sql
static_pages:
- id (primary key)
- title (string)
- slug (string, unique globally)
- summary (text, nullable)
- content (longtext)
- header_image_path (string, nullable)
- status (enum: draft, published)
- meta_description (string, nullable)
- published_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
- created_by (foreign key to users)
```

### Domain Structure
```
app/Domains/StaticPage/
├── Controllers/
│   └── StaticPageController.php (public views)
├── Models/
│   └── StaticPage.php
├── Services/
│   └── StaticPageService.php
├── Requests/
│   └── StaticPageRequest.php
├── Views/
│   └── show.blade.php (/{slug})
├── Resources/
│   ├── lang/
│   │   └── fr/
│   │       └── public.php
│   └── StaticPageResource.php (Filament)
├── Database/
│   └── migrations/
│       └── create_static_pages_table.php
├── Providers/
│   └── StaticPageServiceProvider.php
└── Routes/
    ├── web.php (public routes)
    └── admin.php (admin routes)
```

### Routing Strategy
- **Dynamic Routes**: Routes registered dynamically based on published static pages
- **Caching**: Route cache to avoid database queries on each request
- **Cache Invalidation**: Clear route cache when pages are created/updated/deleted
- **Conflict Prevention**: Ensure static page slugs don't conflict with existing application routes

### Reusable Components
- **Rich Text Editor**: Reuse admin component from News domain
- **Image Processing**: Use shared ImageService for header images
- **SEO Components**: Extract SEO meta tag generation to shared service if needed
- **Slug Generation**: Use existing sluggable configuration

## SEO Strategy

### Meta Tags
- **Title**: Page title with site name
- **Description**: Use summary field or auto-generated from content
- **Keywords**: Optional field for meta keywords
- **Canonical URL**: Proper canonical URLs for each page

### Open Graph
- **og:title**: Page title
- **og:description**: Page summary
- **og:image**: Header image if available
- **og:url**: Canonical page URL
- **og:type**: "article"

### Structured Data
- **Schema.org**: WebPage or Article markup
- **JSON-LD**: Structured data for search engines

## User Stories

### Admin User Stories
- As an admin, I can create new static pages with rich content
- As an admin, I can edit existing static pages
- As an admin, I can save pages as drafts before publishing
- As an admin, I can upload header images for pages
- As an admin, I can preview pages before publishing
- As an admin, I can manage SEO settings for each page
- As an admin, I can see when pages were last updated
- As an admin, I can delete pages I no longer need
- As an admin, I can see all pages sorted alphabetically

### Public User Stories
- As a visitor, I can access static pages directly via their URLs (e.g., `/faq`)
- As a visitor, I can read static page content without authentication
- As a visitor, I can see when a page was last updated
- As a visitor, I can share static pages on social media with proper previews

## Implementation Phases

### Phase 1: Core CRUD
- Database migration and model
- Basic Filament admin interface
- StaticPageService for business logic
- Basic public controller and view

### Phase 2: Rich Content & Images
- Rich text editor integration (reuse from News)
- Header image upload via shared ImageService
- Content formatting and display

### Phase 3: Dynamic Routing & Caching
- Dynamic route registration
- Route caching mechanism
- Cache invalidation on page changes

### Phase 4: SEO & Polish
- Complete SEO meta tags
- Open Graph integration
- Structured data
- Last updated display
- Admin interface polish

## Technical Considerations

### Route Conflicts
- Implement route conflict detection
- Reserve system routes (admin, api, etc.)
- Validate slugs against existing routes

### Performance
- Route caching for production
- Database indexing on slug field
- Efficient cache invalidation

### Security
- Input sanitization for rich content
- XSS prevention in content display
- Admin-only access to management features

## Future Enhancements (Out of Scope)
- Page hierarchy and nested URLs
- Page templates/layouts
- Content versioning and history
- Automatic menu generation
- Page categories/tags
- Content scheduling
- Multi-language support
