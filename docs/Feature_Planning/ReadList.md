# Read List Feature - Functional Specification

## Overview

The Read List (French: "Pile à Lire") is a core feature enabling users to bookmark stories they want to read later. It provides a personalized reading queue with progress tracking, chapter navigation, and filtering capabilities.

**Target Users**: Logged-in users only  
**Status**: Planning phase  
**Domain**: To be determined (likely `ReadList` or integrated into `Story`)

---

## Key Functional Requirements

### Access & Authentication

- **Logged-in users only**: All Read List features are restricted to authenticated users
- **Guest users**: No button on story pages, no menu item, no access to Read List page
- **Private feature**: Read Lists are always private to the user (no public/sharing functionality)

---

## Feature Components

### 1. Adding/Removing Stories

#### Story Page Button
- **Location**: Below the story cover on the story detail page
- **Visibility**: Only shown to users who are NOT authors of the story
- **States**:
  - **Add to Read List**: Shown when story is not in user's Read List
  - **Remove from Read List**: Shown when story is already in user's Read List
- **Behavior**: Toggle button that adds or removes the story from the user's Read List

#### Read List Counter Badge
- **Display**: Shows the number of users who have added this story to their Read List
- **Format**: Exact count (e.g., "42 people have this in their Read List")
- **Visibility**: Visible to everyone, including guests
- **Location**: Story page (position to be determined in UI phase)

---

### 2. Read List Page

#### Access
- **Navigation**: Accessible from the top bar menu (logged-in users only)
- **URL**: To be determined

#### Empty State
- **Message**: "This read list is totally empty... first and last time this happens"
- **Action**: "Browse Library" button leading to the story index page

#### Story Display
For each story in the Read List, display:
- **Cover image**
- **Title**
- **Authors**
- **Summary**
- **Genres**
- **Last update date**
- **Trigger warnings**
- **Reading progress**:
  - Progress bar (visual)
  - Text format: "12/48 chapters (25%)"
  - Calculation: Based on number of chapters read vs. total chapters
- **"Keep Reading" button**:
  - Leads to the first unread chapter
  - **Hidden** when user is up-to-date (has read all chapters)

#### Sorting & Pagination
- **Default sort**: Last updated (most recently updated stories first)
- **Manual reordering**: Not supported
- **Pagination**: Lazy loading, 10 stories at a time
- **No view options**: Single view mode (no grid/list toggle)

---

### 3. Expandable Chapter List

#### Behavior
- **Trigger**: User clicks to "open" a novel
- **Display**: Expands inline below the story entry
- **View**: Similar to `reader-list.blade.php` chapter list (may have fewer fields)

#### Truncated Display Logic
When a story has many chapters, show a truncated view to save space:

**Default view (when not up-to-date)**:
- Previous chapter (if exists) - already read, provides context
- First unread chapter
- Next 3 chapters after the first unread
- Dots ("...") indicating more chapters exist

**Dots behavior**: Clickable, navigates to the full story page

**Edge cases**:
- **No previous chapter** (first unread is chapter 1): Show 5 plain chapters
- **No next chapters** (at end of story): Show last 5 chapters
- **User is up-to-date** (all chapters read): Show last 5 chapters

#### Chapter Selection
- User can click any chapter in the list to navigate to it

---

### 4. Filtering

#### "Up to Date" Toggle
- **Label**: "Hide up to date stories" (or similar)
- **Default state**: ON (checked/enabled)
- **Behavior**: When enabled, hides stories where the user has read all available chapters
- **Definition**: "Up to date" means user has read all chapters currently published (not that the story is complete)

#### Genre Filter
- **Component**: `select-with-tooltips` (single-select, searchable dropdown)
- **Behavior**: Filter stories by a single selected genre
- **Combination**: Can be combined with the "up to date" toggle
- **Default**: All genres (no filter)

#### Filter Persistence
- **Session**: Filters are NOT persisted between sessions (reset on page reload)

#### No Search
- No text search functionality within the Read List

---

### 5. Reading Progress Tracking

#### Progress Calculation
- **Method**: Number of chapters read / total chapters
- **Display**: Progress bar + text (e.g., "12/48 chapters (25%)")
- **First unread logic**: The first chapter not flagged as read
- **Non-sequential reading**: If user reads chapters 1, 3, 5 (skipping 2, 4), the first unread is chapter 2

#### Progress Updates
- Progress tracked on the story/chapter relationship level (likely in Story domain)
- Last interaction time tracked via existing reading progress mechanism

---

### 6. Story Status & Availability

#### Deleted Stories
- **Behavior**: Automatically removed from all Read Lists
- **Notification**: Users are notified when a story in their Read List is deleted

#### Private Stories
- **Behavior**: Kept in Read List but **hidden** from the user's view
- **Visibility**: User cannot see or access private stories in their Read List
- **Return to public**: When story becomes public again, it reappears in the Read List
- **Notification**: Users are notified when a story becomes private

#### Unpublished Chapters
- **Behavior**: Affects progress calculation
- **Notification**: Users notified when chapters are unpublished/deleted

---

## Notifications

The Read List feature integrates with the existing Notification domain to send in-app notifications.

### For Readers (users with story in Read List)

**Trigger events**:
1. **New chapter published**: A story in their Read List has a new chapter
2. **Chapter unpublished/deleted**: A chapter is removed from a story in their Read List
3. **Story unpublished**: A story in their Read List becomes private
4. **Story deleted**: A story in their Read List is permanently deleted
5. **Story re-published**: A private story in their Read List becomes public again

### For Authors

**Trigger event**:
- **Story added to Read List**: When any user adds the author's story to their Read List

### Notification Preferences
- **Current phase**: Notifications are mandatory (not configurable)
- **Future**: Notification preferences will be handled by the Notification module later

---

## Constraints & Limits

- **No maximum**: Unlimited stories can be added to a Read List
- **No batch operations**: Users must remove stories one at a time
- **No export/import**: Not supported
- **No status states**: Binary state only (in Read List or not)
  - Note: Future categorization may be added later but is not designed for in this version

---

## Future Considerations (Not in Scope)

The following are explicitly OUT OF SCOPE for the initial implementation:

- Read List categorization/organization
- Public/shareable Read Lists
- Filter persistence across sessions
- Text search within Read List
- Batch operations (multi-select, bulk remove)
- Manual reordering
- View options (grid/list)
- Visual indicators for new unread chapters
- Export/Import functionality
- Configurable notification preferences
- Additional status states ("want to read", "on hold", etc.)
- Statistics/analytics dashboard

---

## User Stories

### Reader Stories
- **RL-001**: As a reader, I want to add a story to my Read List so I can save it for later reading
- **RL-002**: As a reader, I want to remove a story from my Read List when I'm no longer interested
- **RL-003**: As a reader, I want to see my reading progress for each story in my Read List
- **RL-004**: As a reader, I want to quickly continue reading from where I left off
- **RL-005**: As a reader, I want to browse chapters of a story in my Read List without leaving the page
- **RL-006**: As a reader, I want to filter my Read List to hide stories I'm up-to-date with
- **RL-007**: As a reader, I want to filter my Read List by genre
- **RL-008**: As a reader, I want to be notified when stories in my Read List have updates
- **RL-009**: As a reader, I want to see which stories were recently updated in my Read List

### Author Stories
- **RL-010**: As an author, I want to know how many readers have my story in their Read List
- **RL-011**: As an author, I want to be notified when someone adds my story to their Read List

### Guest Stories
- **RL-012**: As a guest, I want to see how popular a story is (Read List counter) to help me decide if I should read it

---

## Open Questions

*None at this time. All functional requirements have been clarified.*

---

## Next Steps

1. ✅ **Technical design**: Complete - see [ReadList_Architecture.md](../Technical/ReadList_Architecture.md)
2. **Implementation**: Create domain structure and models
3. **Story API extension**: Add `getStoriesForReadList()` method
4. **UI/UX implementation**: Build Read List page and components
5. **Event listeners**: Implement all notification triggers
6. **Testing**: Feature and unit tests

---

**Document Status**: Ready for Implementation  
**Last Updated**: 2025-11-04  
**Technical Design**: [ReadList_Architecture.md](../Technical/ReadList_Architecture.md)
