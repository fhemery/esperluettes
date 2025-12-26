# Profile Comments Tab

## Overview

Add a new "Commentaires" tab to the user profile page that displays all root chapter comments made by the user, organized by story. This feature requires first refactoring the profile page from AJAX-based tabs to full page reload tabs.

## Requirements (Confirmed)

| Requirement | Decision |
|-------------|----------|
| **Comment visibility** | Comments on public AND community stories (viewer must be USER_CONFIRMED) |
| **Comment type** | Root comments only (`parent_comment_id IS NULL`, `entityType = 'chapter'`) |
| **Self-comments** | Not applicable (users cannot comment their own chapters per policy) |
| **Tab visibility** | Only visible to `USER_CONFIRMED` role |
| **Tab naming** | "Commentaires" (French, PHP namespaced translations) |
| **Chapter ordering** | By chapter order in the story |
| **Empty state** | "Aucun commentaire pour le moment !" |
| **Tab loading** | Full page reload (Option A) - requires profile page refactor first |

---

## User Stories

### US-PROFILE-TABS-REFACTOR: Refactor Profile Page to Full Page Reload Tabs

**As a** developer  
**I want to** refactor the profile page to use full page reload tabs instead of AJAX  
**So that** we can easily add new tabs and have bookmarkable URLs

**Acceptance Criteria:**
- [ ] Profile page uses route-based tabs instead of AJAX loading
- [ ] Routes: `/profile/{slug}` (about), `/profile/{slug}/stories` (stories)
- [ ] Tab state is reflected in URL (bookmarkable)
- [ ] Stories tab content moved to Story domain component (Blade component)
- [ ] Existing functionality preserved (edit button, share link, moderation, etc.)
- [ ] Tests updated to reflect new routing

### US-COMMENT-API-01: Add Comment Public API Methods for Profile Comments

**As a** developer  
**I want to** query root comments by author from the Comment domain  
**So that** the Story domain can display user comments on the profile

**Acceptance Criteria:**
- [ ] `CommentPublicApi::getEntityIdsWithRootCommentsByAuthor(string $entityType, int $authorId): array<int>` - returns entity IDs (chapter IDs) where user has root comments
- [ ] `CommentPublicApi::getRootCommentsByAuthorAndEntities(string $entityType, int $authorId, array $entityIds): array<int, CommentDto>` - returns [entityId => CommentDto] for given entities
- [ ] Unit tests for both methods

### US-PROFILE-COMMENTS-01: View Comments Tab

**As a** confirmed user viewing a user profile  
**I want to** see a "Commentaires" tab  
**So that** I can discover what feedback this user has given to stories

**Acceptance Criteria:**
- [ ] A new "Commentaires" tab appears on the profile page (after "Histoires" tab)
- [ ] Tab is only visible to `USER_CONFIRMED` role
- [ ] Tab links to `/profile/{slug}/comments`

### US-PROFILE-COMMENTS-02: Display Stories with Comments

**As a** confirmed user viewing the Comments tab  
**I want to** see all stories the user has commented on  
**So that** I can browse their reading activity

**Acceptance Criteria:**
- [ ] Stories are displayed using the existing `story::card` component
- [ ] Below each story card, chapters with comments are shown in collapsibles
- [ ] Only stories with public or community visibility are shown
- [ ] Only published chapters are shown
- [ ] Chapters are ordered by chapter order in the story

### US-PROFILE-COMMENTS-03: Chapter Comments Display

**As a** confirmed user viewing the Comments tab  
**I want to** see the user's comments on each chapter  
**So that** I can read their feedback

**Acceptance Criteria:**
- [ ] Each chapter is displayed in a `shared::collapsible` component (collapsed by default)
- [ ] Collapsible title = chapter title
- [ ] Collapsible content = the user's root comment body (HTML rendered)

### US-PROFILE-COMMENTS-04: Empty State

**As a** confirmed user viewing the Comments tab  
**I want to** see a friendly message when the user has no comments  
**So that** I understand the tab is not broken

**Acceptance Criteria:**
- [ ] Display message: "Aucun commentaire pour le moment !"

---

## Technical Design

### Architecture Overview

```
Profile Domain                          Story Domain                    Comment Domain
─────────────────                       ────────────                    ──────────────
ProfileController                       
  └─ show()          ─────────────────► <x-story::profile-stories />    
  └─ showStories()   ─────────────────► <x-story::profile-stories />
  └─ showComments()  ─────────────────► <x-story::profile-comments />
                                             │
                                             ├─► CommentPublicApi
                                             │     └─► getEntityIdsWithRootCommentsByAuthor()
                                             │     └─► getRootCommentsByAuthorAndEntities()
                                             │
                                             └─► ChapterService / StoryService
```

### Phase 1: Refactor Profile Page to Full Page Reload

**Current state:**
- `GET /profile/{slug}` → `ProfileController@show` → renders full page with AJAX tabs
- `GET /profiles/{slug}/stories` → `StoryController@profileStories` → returns HTML partial (AJAX)

**Target state:**
- `GET /profile/{slug}` → `ProfileController@show` → redirects to `/profile/{slug}/about` OR shows about tab
- `GET /profile/{slug}/about` → `ProfileController@showAbout` → full page with about tab active
- `GET /profile/{slug}/stories` → `ProfileController@showStories` → full page with stories tab active  
- `GET /profile/{slug}/comments` → `ProfileController@showComments` → full page with comments tab active

**Components to create:**
- `<x-story::profile-stories>` - Blade component in Story domain (extracts current `profileStories` logic)
- `<x-story::profile-comments>` - Blade component in Story domain (new)

### Phase 2: Comment Public API Enrichment

**New methods in `CommentPublicApi`:**

```php
/**
 * Get all entity IDs where the author has at least one root comment.
 * @return array<int> List of entity IDs (e.g., chapter IDs)
 */
public function getEntityIdsWithRootCommentsByAuthor(string $entityType, int $authorId): array;

/**
 * Get root comments by author for specific entities.
 * @return array<int, CommentDto> [entityId => CommentDto]
 */
public function getRootCommentsByAuthorAndEntities(string $entityType, int $authorId, array $entityIds): array;
```

### Phase 3: Story Domain Profile Comments Component

**New Blade component:** `<x-story::profile-comments :userId="$userId" />`

**Logic:**
1. Call `CommentPublicApi::getEntityIdsWithRootCommentsByAuthor('chapter', $userId)` → chapter IDs
2. Load chapters by IDs (published only) with their stories (public/community only)
3. Group chapters by story, order chapters by `order` field
4. Call `CommentPublicApi::getRootCommentsByAuthorAndEntities('chapter', $userId, $chapterIds)` → comments
5. Build view models and render

### Phase 4: Profile Page Integration

**Modify `show.blade.php`:**
- Replace AJAX tabs with route-based tabs
- Add "Commentaires" tab (visible only to USER_CONFIRMED)
- Each tab links to its dedicated route

---

## Files to Create/Modify

### Phase 1: Profile Page Refactor

| File | Action | Notes |
|------|--------|-------|
| `Profile/Private/routes.php` | Modify | Add `/profile/{slug}/about`, `/profile/{slug}/stories`, `/profile/{slug}/comments` routes |
| `Profile/Private/Controllers/ProfileController.php` | Modify | Add `showAbout()`, `showStories()`, `showComments()` methods |
| `Profile/Private/Resources/views/pages/show.blade.php` | Modify | Remove AJAX, use route-based tabs |
| `Profile/Private/Resources/views/partials/profile-header.blade.php` | Create | Extract header to reusable partial |
| `Profile/Private/Resources/views/partials/profile-tabs.blade.php` | Create | Tab navigation component |
| `Story/Private/Components/ProfileStoriesComponent.php` | Create | Blade component class |
| `Story/Private/Resources/views/components/profile-stories.blade.php` | Create | Move from `partials/profile-stories.blade.php` |
| `Profile/Tests/Feature/ProfileTabsTest.php` | Create | Test new routing |

### Phase 2: Comment API

| File | Action | Notes |
|------|--------|-------|
| `Comment/Public/Api/CommentPublicApi.php` | Modify | Add 2 new methods |
| `Comment/Private/Services/CommentService.php` | Modify | Add supporting methods |
| `Comment/Private/Repositories/CommentRepository.php` | Modify | Add queries |
| `Comment/Tests/Unit/CommentPublicApiTest.php` | Create/Modify | Test new methods |

### Phase 3: Story Comments Component

| File | Action | Notes |
|------|--------|-------|
| `Story/Private/Components/ProfileCommentsComponent.php` | Create | Blade component class |
| `Story/Private/Resources/views/components/profile-comments.blade.php` | Create | View |
| `Story/Private/Resources/lang/fr/profile.php` | Create | Translations |
| `Story/Tests/Feature/ProfileCommentsComponentTest.php` | Create | Component tests |

### Phase 4: Integration

| File | Action | Notes |
|------|--------|-------|
| `Profile/Private/Resources/lang/fr/show.php` | Modify | Add "Commentaires" translation |
| `Profile/Tests/Feature/ProfileCommentsTabTest.php` | Create | Integration tests |

---

## Implementation Order

```
Phase 1: Profile Page Refactor
├── 1.1 Create profile header partial
├── 1.2 Create profile tabs partial  
├── 1.3 Create Story::ProfileStoriesComponent (extract from AJAX endpoint)
├── 1.4 Add new routes to Profile domain
├── 1.5 Update ProfileController with new methods
├── 1.6 Update show.blade.php to use partials and route-based tabs
├── 1.7 Write/update tests
└── 1.8 Remove old AJAX endpoint (or keep for backward compat)

Phase 2: Comment API Enrichment
├── 2.1 Add repository methods
├── 2.2 Add service methods
├── 2.3 Add public API methods
└── 2.4 Write unit tests

Phase 3: Story Comments Component
├── 3.1 Create ProfileCommentsComponent class
├── 3.2 Create view with story cards + collapsibles
├── 3.3 Add translations
└── 3.4 Write component tests

Phase 4: Integration
├── 4.1 Add comments route to Profile
├── 4.2 Add showComments() to ProfileController
├── 4.3 Add "Commentaires" tab (USER_CONFIRMED only)
├── 4.4 Add translations
└── 4.5 Write integration tests
```

---

## Test Plan

### Comment API Tests (Unit)
- `getEntityIdsWithRootCommentsByAuthor()` returns correct chapter IDs
- `getEntityIdsWithRootCommentsByAuthor()` excludes replies (non-root comments)
- `getRootCommentsByAuthorAndEntities()` returns correct comments mapped by entity
- `getRootCommentsByAuthorAndEntities()` returns empty for non-existent entities

### Story Component Tests (Feature)
- Component renders story cards for stories with comments
- Component shows chapters in correct order
- Component shows comment body in collapsible
- Component filters out unpublished chapters
- Component filters out private stories
- Component shows empty state when no comments

### Profile Integration Tests (Feature)
- Comments tab visible only to USER_CONFIRMED
- Comments tab not visible to USER role
- Comments tab not visible to guests
- Tab navigation works correctly
- URL reflects active tab
