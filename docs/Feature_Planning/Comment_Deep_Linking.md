# Comment Deep Linking Feature

## Overview
Enable direct linking to specific comments via URL anchors (`#comment-<id>`). The feature is implemented entirely server-side, with the page pre-loading all necessary comments before rendering.

## User Story
**As a user**, I want to share a link to a specific comment so that recipients can view that exact comment in context immediately upon page load.

## Requirements

### 1. Anchor Links
- All comments (root and replies) must have an `id` attribute: `id="comment-{{ $comment->id }}"`
- Browser will automatically scroll to the anchored comment on page load

### 2. URL Format
- Anchor format: `#comment-<id>` (e.g., `/story/my-story/chapter-1#comment-123`)
- Works with any comment ID (root or reply)

### 3. Reply Comment Handling
- If anchor points to a **reply comment**, load its parent root comment with all children
- The anchor ID is placed on the reply element itself, not the parent
- Browser scrolls directly to the reply within the expanded parent thread

### 4. Validation & Error Handling
- Before loading any pages, validate:
  - Comment exists
  - Comment belongs to the current entity (entityType + entityId match)
- If validation fails (comment not found, wrong entity, deleted, etc.):
  - Load nothing for the anchor
  - Render page normally (page=0, lazy mode)
  - No error message shown (graceful degradation)

### 5. Server-Side Pre-Loading
- All logic happens in `CommentList` Blade component constructor
- Pages are loaded server-side **before** Alpine.js initializes
- No Alpine.js involvement or client-side fetching for deep links
- Once rendered, normal infinite scroll takes over for subsequent pages

## Implementation Design

### Architecture Overview
```
URL with #comment-123
    ↓
CommentList::__construct()
    ↓
1. Parse anchor from request URL
2. Validate comment (CommentPublicApi::getComment)
3. Check entity match
4. Resolve target page (root comment position)
5. Load pages 1..N server-side
6. Render all items
    ↓
Browser auto-scrolls to #comment-123
    ↓
Alpine.js infinite scroll for remaining pages
```

### Step-by-Step Flow

#### Step 1: Detect Anchor in Constructor
```php
// In CommentList::__construct()
$fragment = parse_url(request()->fullUrl(), PHP_URL_FRAGMENT);
$targetCommentId = null;
if ($fragment && preg_match('/^comment-(\d+)$/', $fragment, $matches)) {
    $targetCommentId = (int) $matches[1];
}
```

#### Step 2: Validate Comment
```php
if ($targetCommentId) {
    try {
        $targetComment = $api->getComment($targetCommentId, withChildren: false);
        
        // Validate entity match
        if ($targetComment->entityType !== $this->entityType 
            || $targetComment->entityId !== $this->entityId) {
            $targetCommentId = null; // Invalid, ignore
        }
    } catch (\Throwable $e) {
        // Comment not found or access denied, ignore
        $targetCommentId = null;
    }
}
```

#### Step 3: Resolve Target Root Comment
```php
if ($targetCommentId && $targetComment) {
    // If it's a reply, find the root parent
    $rootCommentId = $targetComment->parentCommentId 
        ? $targetComment->parentCommentId 
        : $targetComment->id;
}
```

#### Step 4: Calculate Page Number
Need a new service method:
```php
// CommentService::findPageForRootComment()
public function findPageForRootComment(
    string $entityType, 
    int $entityId, 
    int $rootCommentId, 
    int $perPage
): int
```

This method:
- Get the root comment's `created_at` timestamp
- Count root comments with `created_at > target.created_at` (DESC order)
- Calculate page: `floor(count / perPage) + 1`

#### Step 5: Load Pages 1 Through Target Page
```php
$targetPage = $service->findPageForRootComment(
    $this->entityType, 
    $this->entityId, 
    $rootCommentId, 
    $this->perPage
);

$allItems = [];
for ($p = 1; $p <= $targetPage; $p++) {
    $pageData = $api->getFor($this->entityType, $this->entityId, $p, $this->perPage);
    $allItems = array_merge($allItems, $pageData->items);
}

// Merge into single DTO with updated pagination state
$this->comments = CommentListDto::merged($allItems, ...);
```

#### Step 6: Render with All Items
- Component renders with all pre-loaded items
- Alpine.js initializes with correct `page` and `hasMore` state
- Infinite scroll continues from `targetPage + 1`

### Component Modifications

#### CommentList Blade Component
**File**: `app/Domains/Comment/Private/View/Components/CommentList.php`

**Changes**:
- Add anchor detection in constructor
- Add validation logic
- Add page pre-loading loop
- Merge paginated results into single DTO

#### CommentPublicApi
**File**: `app/Domains/Comment/Public/Api/CommentPublicApi.php`

**Changes**:
- Existing `getComment()` method is sufficient for validation
- May need to expose `entityType` and `entityId` on `CommentDto` if not already present

#### CommentService
**File**: `app/Domains/Comment/Private/Services/CommentService.php`

**New Method**:
```php
public function findPageForRootComment(
    string $entityType, 
    int $entityId, 
    int $rootCommentId, 
    int $perPage
): int
```

#### CommentRepository
**File**: `app/Domains/Comment/Private/Repositories/CommentRepository.php`

**New Method**:
```php
public function countRootCommentsAfter(
    string $entityType, 
    int $entityId, 
    Carbon $createdAt
): int
```

Query:
```sql
SELECT COUNT(*) 
FROM comments 
WHERE commentable_type = ? 
  AND commentable_id = ? 
  AND parent_comment_id IS NULL 
  AND created_at > ?
```

### Blade Template Modifications

#### comment-item.blade.php
**File**: `app/Domains/Comment/Private/Resources/views/components/partials/comment-item.blade.php`

**Change**:
```blade
<!-- Add id attribute to <li> -->
<li class="py-4 sm:px-4 sm:mb-4" id="comment-{{ $comment->id }}">
```

**For reply comments** (nested within parent):
```blade
<!-- Each reply also gets its own id -->
<li id="comment-{{ $reply->id }}">
```

### Alpine.js Component State

#### comment-list.blade.php
**File**: `app/Domains/Comment/Private/Resources/views/components/comment-list.blade.php`

**Changes**:
- No Alpine changes needed
- Component receives pre-loaded items with correct pagination state
- `page` prop reflects the last loaded page
- `hasMore` calculated correctly based on total vs loaded

## Edge Cases & Considerations

### 1. Comment Deleted Between Share and Click
- Validation fails silently
- Page loads normally without pre-fetch
- User sees standard comment list

### 2. Reply Comment Without Parent
- If `parent_comment_id` is set but parent doesn't exist (data integrity issue)
- Validation should catch this (getComment will fail or return orphan)
- Fall back to normal loading

### 3. Very Deep Page Numbers
- If comment is on page 100, loading 100 pages could be heavy
- **Mitigation Options**:
  - Set a reasonable limit (e.g., max 20 pages)
  - Optimize with batch loading in future
  - For MVP: accept the tradeoff (most comments won't be that deep)

### 4. Performance
- Multiple API calls in constructor (up to N pages)
- Each call loads root comments + children (with joins)
- **Impact**: Acceptable for MVP (typical case: 1-5 pages)
- **Future**: Consider batch endpoint that loads multiple pages in one query

### 5. Race Condition: New Comments Added
- Comment shared at position page 3
- New comments added before link clicked
- Target comment is now on page 4
- **Result**: Comment not visible, user must scroll
- **Mitigation**: Accept for MVP (rare case, user can manually load more)

### 6. Permissions
- User might not have access to view comments (guest on members-only)
- Validation in `getComment()` will throw exception
- Catch and ignore, render normal error UI

## Testing Strategy

### Unit Tests

#### CommentRepository::countRootCommentsAfter()
- Count with no comments after target
- Count with multiple comments after target
- Count with exact timestamp match (boundary)

#### CommentService::findPageForRootComment()
- First page (no comments after)
- Middle page
- Last page
- Edge: exactly at page boundary

### Feature Tests

#### Deep Link to Root Comment
- Create 15 comments, perPage=5
- Link to comment on page 3
- Assert pages 1-3 are rendered
- Assert correct comment has id attribute

#### Deep Link to Reply Comment
- Create root with 3 replies
- Link to reply #2
- Assert parent root is loaded with all children
- Assert reply has id attribute

#### Invalid Comment ID
- Link to non-existent comment
- Assert normal page load (page=0)
- Assert no error shown

#### Wrong Entity
- Link to comment from different chapter
- Assert normal page load
- Assert no error shown

#### Guest User on Members-Only Comments
- Link to valid comment but user unauthorized
- Assert error UI shown (existing behavior)
- Assert no crash

### Browser Tests (Optional)
- Verify scroll behavior works with anchor
- Test with very long comment threads
- Mobile vs desktop viewport

## Security Considerations

1. **Access Control**: All comment loading goes through `CommentPublicApi` which enforces auth checks
2. **XSS**: Comment IDs are cast to integers, anchor parsing uses regex
3. **DoS**: Pre-loading many pages could be abused, but limited by existing pagination
4. **Information Disclosure**: Validation only confirms comment exists/belongs to entity, no data leaked

## Future Enhancements

### Phase 2: Optimizations
- Batch API endpoint: load pages 1-N in single query
- Cache page calculations (Redis)
- Add limit to max prefetch pages (e.g., 20)

### Phase 3: UX Improvements
- Highlight the target comment temporarily (CSS animation)
- Show "loading context" message if prefetch takes >1s
- Support prefetch for infinite scroll (client-side)

### Phase 4: Analytics
- Track deep link usage
- Monitor performance of multi-page loads
- Identify popular linked comments

## Implementation Checklist

- [ ] Add `id` attribute to comment items (root and replies)
- [ ] Add `CommentRepository::countRootCommentsAfter()`
- [ ] Add `CommentService::findPageForRootComment()`
- [ ] Modify `CommentList::__construct()` with anchor detection logic
- [ ] Add `CommentListDto::merged()` helper (or equivalent)
- [ ] Update Alpine component state initialization
- [ ] Write feature test for root comment deep link
- [ ] Write feature test for reply comment deep link
- [ ] Write feature test for invalid comment handling
- [ ] Manual browser testing (scroll behavior)
- [ ] Performance testing (20+ pages scenario)

## Open Questions

1. **Max prefetch limit**: Should we cap at 20/50/100 pages?
2. **Highlight animation**: Should target comment be visually highlighted temporarily?
3. **Error messaging**: Should we show a toast if comment not found, or stay silent?
4. **Analytics**: Should we track deep link usage?

## Timeline Estimate

- **Repository/Service layer**: 1-2 hours
- **Component modifications**: 2-3 hours
- **Template changes**: 30 minutes
- **Testing**: 2-3 hours
- **Total**: ~6-8 hours for MVP

## Success Metrics

- Deep links work for 100% of valid comment IDs
- Page load time increase <500ms for typical case (3-5 pages)
- Zero crashes from invalid anchors
- Scroll position accurate within viewport
