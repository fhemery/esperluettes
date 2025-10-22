# FAQ Feature Specification

## Overview
The FAQ (Frequently Asked Questions) feature provides a structured, searchable knowledge base for users. It enables administrators to create organized FAQ content with categories and questions, while providing users with an intuitive, SEO-optimized interface to find answers.

## Core Features

### Category Management
- **Name**: Category name (plain text, translatable)
- **Slug**: Auto-generated from name using `spatie/laravel-sluggable` (unique)
- **Description**: Optional short description of the category (plain text, translatable)
- **Sort Order**: Manual integer for custom ordering
- **Active Flag**: Enable/disable categories without deletion
- **Question Count**: Display number of active questions in category
- **Audit Trail**: created_by_user_id, updated_by_user_id, created_at, updated_at (no foreign keys)

### Question Management
- **Question**: Plain text question (translatable)
- **Slug**: Auto-generated from question text (unique within FAQ)
- **Answer**: Rich text editor with HTMLPurifier (`admin-content` profile)
- **Category**: Belongs to one category
- **Image**: Optional image at top of answer (800px max width, alt text support)
- **Sort Order**: Manual integer for ordering within category
- **Active Flag**: Enable/disable questions without deletion
- **Audit Trail**: created_by_user_id, updated_by_user_id, created_at, updated_at (no foreign keys)

### Multi-Language Support
- **Configuration**: `FAQ_MULTILANGUAGE` environment variable (default: false)
- **Fields**: When enabled, question, answer, category name, and description become translatable
- **UI Adaptation**: Admin interface shows language tabs only when multiple languages configured
- **Default Language**: French (fr)
- **Future-Ready**: Architecture supports multiple languages even if initially hidden

### Admin Interface (Filament)
- **Location**: Admin domain (`app/Domains/Admin/Filament/Resources/`)
- **Resources**: FaqCategoryResource and FaqQuestionResource
- **Communication**: Admin panel uses FAQ domain's Public API for all write operations
- **Permissions**: Only `admin` and `admin_tech` roles can access
- **Category Admin**:
  - CRUD operations via FaqPublicApi
  - Sortable list with drag-drop reordering
  - Active/inactive toggle
  - Display question count per category
  - Search by name
- **Question Admin**:
  - CRUD operations via FaqPublicApi
  - Rich text editor for answers (reuse existing component)
  - Image upload with automatic 800px resizing
  - Alt text field for accessibility
  - Category selection dropdown
  - Manual sort order within category
  - Active/inactive toggle
  - Search by question text
  - Filter by category and status

### Public Display

#### Main FAQ Page (`/faq`)
- **Structure**: Hybrid tab/accordion interface for each category
- **First Category**: Loads immediately with all questions expanded server-side
- **Other Categories**: Load content dynamically when tab clicked (AJAX)
- **Question Display**: Each question is a collapsible panel
  - Closed by default (except when deep-linked)
  - Click question to expand/collapse answer
  - Smooth animations for expand/collapse
  - Image displayed at top of answer if present
- **Empty Categories**: Hidden from public view
- **SEO**: Full server-side rendering with proper heading structure

#### Category Pages (`/faq/{category-slug}`)
- **URL Pattern**: Each category accessible via dedicated URL
- **Content**: All active questions in that category rendered server-side
- **SEO**: Complete meta tags, Open Graph, structured data
- **Question Links**: Questions shown as expanded by default on category pages
- **Breadcrumbs**: Home → FAQ → Category Name

#### Deep-Linked Questions (`/faq/{category-slug}#{question-slug}`)
- **Behavior**: Opens category tab and scrolls to specific question
- **Question State**: Target question expanded automatically
- **SEO**: Proper anchor handling with server-side category rendering
- **Shareability**: Direct links to specific questions

#### Search Page (`/faq/search?q={query}`)
- **Search Scope**: Questions (text) and answers (content)
- **Results Display**: Dedicated search results page
- **Result Format**: Question title, excerpt from answer, category badge, link to question
- **Highlighting**: Search terms highlighted in results
- **No Results**: Helpful message with suggestions
- **SEO**: Noindex on search results pages

### Search Functionality
- **Location**: Search box at top of FAQ page
- **Behavior**: Submit redirects to `/faq/search?q={query}`
- **Backend**: Full-text search in questions and answer content
- **Performance**: Database indexes on searchable fields
- **Result Ranking**: Prioritize question text matches over answer content matches

## Technical Specifications

### Technology Stack
- **Backend**: Laravel with Domain-Oriented Architecture
- **Admin Panel**: Filament (in Admin domain)
- **Rich Text Editor**: Reuse existing admin component (HTMLPurifier `admin-content` profile)
- **Image Processing**: Intervention Image (800px max width, maintain aspect ratio)
- **Slug Generation**: `spatie/laravel-sluggable`
- **Frontend**: Alpine.js for dynamic tab loading and collapsible panels
- **Storage**: `storage/app/public/faq/` for images
- **Caching**: Laravel cache for FAQ data (categories, questions, search results)
- **Translation**: Laravel's built-in translation system (when enabled)

### Database Schema

```sql
faq_categories:
- id (primary key)
- name (json) # {fr: "Catégorie", en: "Category"} when multilang enabled
- slug (string, unique, indexed)
- description (json, nullable) # translatable
- sort_order (integer, indexed)
- is_active (boolean, default true)
- created_by_user_id (integer, not null) # no foreign key
- updated_by_user_id (integer, nullable) # no foreign key
- created_at (timestamp)
- updated_at (timestamp)

faq_questions:
- id (primary key)
- faq_category_id (foreign key to faq_categories, cascades on delete)
- question (json) # translatable
- slug (string, unique, indexed)
- answer (json) # translatable, HTML content
- image_path (string, nullable)
- image_alt_text (json, nullable) # translatable
- sort_order (integer, indexed)
- is_active (boolean, default true)
- created_by_user_id (integer, not null) # no foreign key
- updated_by_user_id (integer, nullable) # no foreign key
- created_at (timestamp)
- updated_at (timestamp)

Indexes:
- faq_categories: (slug), (sort_order), (is_active)
- faq_questions: (slug), (faq_category_id, sort_order), (is_active)
- Fulltext: faq_questions (question, answer) for search
```

### Domain Structure

```
app/Domains/FAQ/
├── Database/
│   └── Migrations/
│       ├── YYYY_MM_DD_HHiiss_create_faq_categories_table.php
│       └── YYYY_MM_DD_HHiiss_create_faq_questions_table.php
│
├── Private/
│   ├── Controllers/
│   │   ├── FaqController.php (public FAQ pages)
│   │   └── FaqSearchController.php (search functionality)
│   ├── Models/
│   │   ├── FaqCategory.php
│   │   └── FaqQuestion.php
│   ├── Services/
│   │   ├── FaqService.php (business logic)
│   │   ├── FaqSearchService.php (search logic)
│   │   └── FaqCacheService.php (caching layer)
│   ├── Requests/
│   │   ├── FaqCategoryRequest.php
│   │   └── FaqQuestionRequest.php
│   ├── Resources/
│   │   ├── views/
│   │   │   ├── index.blade.php (/faq)
│   │   │   ├── category.blade.php (/faq/{slug})
│   │   │   ├── search.blade.php (/faq/search)
│   │   │   └── components/
│   │   │       ├── category-tab.blade.php
│   │   │       └── question-panel.blade.php
│   │   ├── js/
│   │   │   └── faq.js (Alpine.js components)
│   │   ├── css/
│   │   │   └── faq.scss
│   │   └── lang/
│   │       └── fr/
│   │           ├── faq.php (public strings)
│   │           └── admin.php (admin strings)
│   ├── Providers/
│   │   └── FaqServiceProvider.php
│   └── routes.php
│
├── Public/
│   ├── Api/
│   │   └── FaqPublicApi.php (API for Admin domain)
│   ├── Contracts/
│   │   └── Dto/
│   │       ├── FaqCategoryDto.php
│   │       └── FaqQuestionDto.php
│   └── Events/ (optional, for future use)
│
└── Tests/
    ├── Feature/
    │   ├── FaqPublicTest.php
    │   ├── FaqSearchTest.php
    │   └── FaqPublicApiTest.php
    └── Unit/
        ├── FaqServiceTest.php
        └── FaqSearchServiceTest.php
```

### Admin Domain Integration

```
app/Domains/Admin/Filament/Resources/
├── FaqCategoryResource.php
│   ├── Pages/
│   │   ├── ListFaqCategories.php
│   │   ├── CreateFaqCategory.php
│   │   └── EditFaqCategory.php
└── FaqQuestionResource.php
    ├── Pages/
    │   ├── ListFaqQuestions.php
    │   ├── CreateFaqQuestion.php
    │   └── EditFaqQuestion.php
```

### Public API (FAQ Domain)

**FaqPublicApi** exposes methods for Admin domain:

```php
// Categories
public function createCategory(array $data): FaqCategoryDto;
public function updateCategory(int $id, array $data): FaqCategoryDto;
public function deleteCategory(int $id): bool;
public function reorderCategories(array $orderedIds): bool;

// Questions
public function createQuestion(array $data): FaqQuestionDto;
public function updateQuestion(int $id, array $data): FaqQuestionDto;
public function deleteQuestion(int $id): bool;
public function reorderQuestionsInCategory(int $categoryId, array $orderedIds): bool;
```

All methods:
- Accept user_id for audit trail
- Handle image uploads/deletions
- Validate data
- Clear relevant caches
- Return DTOs or success status

### Routing Strategy

```php
// Public routes (routes.php in FAQ domain)
Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');
Route::get('/faq/search', [FaqSearchController::class, 'search'])->name('faq.search');
Route::get('/faq/{category}', [FaqController::class, 'category'])->name('faq.category');

// AJAX route for dynamic tab loading
Route::get('/api/faq/category/{category}/questions', [FaqController::class, 'categoryQuestions'])
    ->name('faq.category.questions');
```

### Caching Strategy

**Cache Keys:**
- `faq:categories:active` - List of active categories with question counts
- `faq:category:{slug}:questions` - Questions for a specific category
- `faq:all:active` - Full FAQ data for main page first load
- `faq:search:{hash(query)}` - Search results (short TTL, e.g., 15 min)

**Cache Invalidation:**
- Create/Update/Delete category: Clear category caches
- Create/Update/Delete question: Clear category + all caches
- Reorder: Clear relevant caches
- Image upload/delete: Clear relevant caches

**Cache Duration:**
- Category/Question data: 1 hour (or until invalidated)
- Search results: 15 minutes
- Use cache tags for easier group invalidation if Redis available

### SEO Strategy

#### Meta Tags
- **Title**: "FAQ - {Category Name}" or "Frequently Asked Questions"
- **Description**: Category description or default FAQ intro text
- **Canonical URL**: Proper canonical URLs for each page

#### Open Graph
- **og:title**: Page title
- **og:description**: Category description
- **og:url**: Canonical URL
- **og:type**: "website"

#### Structured Data (JSON-LD)
- **FAQPage**: Schema.org FAQPage markup for main FAQ page
- **Question/Answer**: Individual Q&A pairs with proper schema
- **Breadcrumbs**: BreadcrumbList for navigation

#### SEO Best Practices
- Proper heading hierarchy (H1 for page title, H2 for categories, H3 for questions)
- Server-side rendering for all content
- Semantic HTML5 elements
- Alt text for all images
- Clean URL structure
- Fast loading (caching + optimized images)

### Frontend Implementation

#### Alpine.js Components

**Category Tabs:**
```javascript
x-data="faqTabs"
- Handles tab switching
- Loads category content dynamically (if not first category)
- Updates URL hash on tab change
- Detects hash in URL and opens corresponding category
```

**Question Panels:**
```javascript
x-data="faqQuestion"
- Handles expand/collapse animation
- Scroll to question on deep link
- Accessibility (aria-expanded, roles)
```

**Search:**
```javascript
x-data="faqSearch"
- Auto-submit on Enter
- Input validation
- Loading state
```

#### Responsive Design
- Mobile: Accordion-style (no tabs)
- Tablet/Desktop: Tab interface
- Touch-friendly tap targets
- Smooth scroll behavior

## User Stories

### Admin User Stories
- As an admin, I can create FAQ categories with names and descriptions
- As an admin, I can reorder categories by dragging them
- As an admin, I can activate/deactivate categories without deleting them
- As an admin, I can see how many questions are in each category
- As an admin, I can create questions with rich text answers
- As an admin, I can add images to question answers with alt text
- As an admin, I can assign questions to categories
- As an admin, I can reorder questions within a category
- As an admin, I can activate/deactivate questions without deleting them
- As an admin, I can search for questions by text
- As an admin, I can filter questions by category and status
- As an admin, I can see who created/modified each item and when
- As an admin, I can edit category and question slugs if needed

### Public User Stories
- As a visitor, I can access the FAQ without logging in
- As a visitor, I can see all FAQ categories organized in tabs
- As a visitor, I can click a category tab to view its questions
- As a visitor, I can see a summary of questions in each category
- As a visitor, I can click a question to expand and read the answer
- As a visitor, I can collapse questions I've read
- As a visitor, I can search across all questions and answers
- As a visitor, I can view search results with highlighted terms
- As a visitor, I can share direct links to specific questions
- As a visitor, I can view images in answers with proper alt text
- As a visitor, I benefit from fast page loads thanks to caching
- As a search engine, I can crawl and index all FAQ content properly

## Implementation Phases

### Phase 1: Core Structure & Models
- Database migrations for categories and questions
- FaqCategory and FaqQuestion models
- Basic FaqService with CRUD operations
- FaqPublicApi implementation
- DTOs for data transfer
- Unit tests for models and service

### Phase 2: Admin Interface
- FaqCategoryResource in Admin domain
- FaqQuestionResource in Admin domain
- Integration with FaqPublicApi
- Image upload handling
- Rich text editor integration
- Manual ordering UI (drag & drop)
- Active/inactive toggles
- Translations for admin interface

### Phase 3: Public Pages - Basic
- Main FAQ page controller and view
- Category page controller and view
- Basic HTML/CSS layout
- Server-side rendering of first category
- Routing and URL handling
- Translations for public interface

### Phase 4: Dynamic Loading & Interactivity
- Alpine.js components for tabs
- AJAX loading for additional categories
- Collapsible question panels
- Smooth animations
- Deep linking with hash anchors
- Responsive design

### Phase 5: Search Functionality
- FaqSearchService implementation
- Full-text search with database indexes
- Search results page
- Highlighting search terms
- Search result ranking
- Search caching

### Phase 6: Caching & Performance
- FaqCacheService implementation
- Cache strategy implementation
- Cache invalidation on updates
- Performance optimization
- Load testing

### Phase 7: SEO & Polish
- Complete meta tags for all pages
- Open Graph integration
- JSON-LD structured data (FAQPage, Question/Answer)
- Breadcrumbs
- Accessibility audit (ARIA, keyboard navigation)
- Image optimization
- Final UI polish

### Phase 8: Multi-Language Support (Optional)
- Translatable fields implementation
- Admin UI language tabs (conditional)
- Language switcher (if multiple languages)
- RTL support if needed
- Language-specific slugs

## Technical Considerations

### Performance
- Database indexes on slug, sort_order, is_active, category_id
- Full-text indexes for search
- Eager loading for category->questions relationships
- Cache all public-facing data
- Lazy loading for non-first categories
- Image optimization (WebP format, lazy loading)
- CDN for images if available

### Security
- HTMLPurifier for answer content (admin-content profile)
- XSS prevention in all outputs
- CSRF protection on all forms
- Admin-only access to management features
- Input validation on all endpoints
- Rate limiting on search endpoint
- Secure image upload handling

### Accessibility
- ARIA labels and roles for interactive elements
- Keyboard navigation (Tab, Enter, Escape)
- Focus management on expand/collapse
- Screen reader friendly
- Alt text for all images (required field)
- Semantic HTML structure
- Color contrast compliance

### Multi-Language Considerations
- JSON columns for translatable fields
- Fallback to default language if translation missing
- Conditional UI rendering based on `FAQ_MULTILANGUAGE` env var
- Slug generation per language (optional)
- Language-aware search (search in user's language)
- RTL support for future Arabic/Hebrew

### Audit Trail
- Track created_by_user_id and updated_by_user_id
- No foreign key constraints (avoid cross-domain dependencies)
- Display creator/modifier in admin panel
- Timestamps for created_at and updated_at
- Consider soft deletes for future recovery (out of scope for now)

## Configuration

### Environment Variables
```env
# Multi-language support
FAQ_MULTILANGUAGE=false

# Cache settings
FAQ_CACHE_TTL=3600  # 1 hour in seconds
FAQ_SEARCH_CACHE_TTL=900  # 15 minutes

# Image settings
FAQ_IMAGE_MAX_WIDTH=800
FAQ_IMAGE_STORAGE_PATH=faq

# Search settings
FAQ_SEARCH_RESULTS_PER_PAGE=20
```

## Future Enhancements (Out of Scope)

### Content Management
- Question view counter (track most viewed)
- User feedback (Was this helpful? Yes/No)
- Related questions suggestions
- Question tags/keywords for better organization
- Version history for questions
- Scheduled publishing for questions

### Advanced Search
- Autocomplete suggestions
- "Did you mean...?" for typos
- Advanced filters (by category, date, etc.)
- Sorting options (relevance, date, popularity)

### User Features
- Save favorite questions (for logged users)
- Email question suggestions from users
- Community-driven Q&A (user submissions)
- Question upvoting

### Analytics
- Track popular questions
- Search analytics (what users search for)
- Category engagement metrics
- Export analytics to admin dashboard

### Integration
- Chatbot integration (AI-powered FAQ answers)
- Help widget for embedding FAQ in other pages
- Public API for external consumption
- RSS feed for new questions

### Technical
- Real-time updates for admin panel
- Markdown support alternative to rich text
- Video embedding in answers
- File attachments for downloadable resources
- A/B testing for FAQ organization

## Testing Strategy

### Unit Tests
- Model methods and relationships
- Service layer business logic
- DTO creation and validation
- Cache service operations
- Search ranking algorithms

### Feature Tests
- Public FAQ page rendering
- Category page rendering
- Search functionality
- Deep linking behavior
- Cache invalidation
- AJAX category loading

### Integration Tests
- Admin panel CRUD operations through PublicApi
- Image upload and processing
- Multi-language switching (when enabled)
- Full search workflow

## Documentation

### Admin Guide
- How to create and organize categories
- How to write effective FAQ questions
- Rich text editor guide
- Image upload best practices
- SEO tips for FAQ content

### Technical Documentation
- API documentation for FaqPublicApi
- Cache strategy documentation
- Database schema documentation
- Deployment checklist

## Success Metrics

### User Engagement
- FAQ page views
- Average time on FAQ pages
- Question expansion rate
- Search usage rate
- Bounce rate reduction

### Content Quality
- Number of active questions
- Category distribution
- Question updates frequency
- Image usage rate

### Performance
- Page load time < 2 seconds
- Cache hit rate > 80%
- Search response time < 500ms
- Zero downtime deployments
