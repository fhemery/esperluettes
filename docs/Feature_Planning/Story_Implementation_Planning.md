# Story Domain Implementation Planning

## User Story-Based Feature Slices

This document breaks down the Story domain implementation into atomic user stories with associated feature tests. Each
user story is:

- **A single, testable user capability**
- **Independently valuable**
- **Small enough to implement and validate quickly**
- **Backed by comprehensive feature tests**

## User Stories - Phase 1: Basic Story Management

### **[DONE] US-001: Access Story Creation**

**As a logged-in user, I want to access the story creation page so that I can start writing a new story.**

**Acceptance Criteria:**

**Scenario 1: Authenticated user accesses story creation**

- **Given** I am a logged-in user
- **When** I navigate to `/stories/create`
- **Then** I should see the story creation page
- **And** I should see "Create New Story" heading

**Scenario 2: Unauthenticated user tries to access story creation**

- **Given** I am not logged in
- **When** I navigate to `/stories/create`
- **Then** I should be redirected to the login page

**Implementation:**

- Create basic Story model and migration (id, created_by_user_id, title, created_at, updated_at)
- Create StoryController with create() method
- Add authentication middleware
- Create basic create.blade.php view

---

### ** [DONE] US-002: View Basic Story Form**

**As a logged-in user, I want to see a story creation form with basic fields so that I can enter my story details.**

**Acceptance Criteria:**

**Scenario: Story creation form displays all required fields**

- **Given** I am a logged-in user
- **When** I am on the story creation page
- **Then** I should see a "Title" input field
- **And** I should see a "Description" textarea
- **And** I should see a "Visibility" dropdown with options: Public, Community, Private
- **And** I should see a "Create Story" submit button

**Implementation:**

- Add description and visibility fields to stories migration
- Update create.blade.php with form fields
- Add visibility enum to Story model

---

### **[DONE] US-003: Create Basic Story**

**As a logged-in user, I want to submit the story form so that my story is saved to the database.**

**Acceptance Criteria:**

**Scenario: Successfully create a story with valid data**

- **Given** I am a logged-in user
- **And** I am on the story creation page
- **When** I fill in "Title" with "My First Story"
- **And** I fill in "Description" with "This is a great story"
- **And** I select "Public" from "Visibility"
- **And** I click "Create Story"
- **Then** I should be redirected to the story detail page
- **And** I should see a success message
- **And** I should see the story title and description on that page
- **And** I should see the selected visibility label (Public, Community, or Private)
- **And** the URL should follow the pattern `/stories/{slug-with-id}` (e.g., `my-first-story-123`)
- **And** I should see an "Edit" action available (since I'm an author)

**Implementation:**

- Add store() method to StoryController
- Create StoryRequest for validation
- Add slug generation to Story model
- Persist story with created_by_user_id = auth()->id()
- Insert `story_collaborators` row for creator with role = author, invited_by_user_id = created_by_user_id, invited_at =
  now, accepted_at = now
- Create success redirect logic

---

### **[DONE] US-004: Validate Story Form**

**As a user, I want form validation so that I cannot submit invalid story data.**

**Acceptance Criteria:**

**Scenario 1: Submit form with missing required fields**

- **Given** I am a logged-in user
- **And** I am on the story creation page
- **When** I click "Create Story" without filling any fields
- **Then** I should see validation errors for "Title" and "Visibility" (Description is optional)
- **And** I should remain on the story creation page

**Scenario 2: Submit form with title too long**

- **Given** I am a logged-in user
- **And** I am on the story creation page
- **When** I fill in "Title" with a string longer than 255 characters
- **And** I fill in other required fields correctly
- **And** I click "Create Story"
- **Then** I should see a validation error for "Title"
- **And** my other field values should be preserved

**Scenario 3: Submit form with description too long**

- **Given** I am a logged-in user
- **And** I am on the story creation page
- **When** I fill in "Description" with content longer than 3000 characters
- **And** I click "Create Story"
- **Then** I should see a validation error for "Description"

**Scenario 4: Submit form with invalid visibility**

- **Given** I am a logged-in user
- **And** I am on the story creation page
- **When** I select an invalid value for "Visibility"
- **And** I click "Create Story"
- **Then** I should see a validation error for "Visibility"

**Implementation:**

- Update `StoryRequest` with validation rules
    - Title: required, trimmed, min 1, max 255
    - Description: optional, max 3000
    - Visibility: required, in [public, community, private]
- Add custom validation messages (keys) returned from `StoryRequest::messages()`
- Ensure error display with `<x-input-error>` (already present)
- Ensure old inputs preserved (already present)

---

### **[DONE] US-005: Generate Story Slug (with ID suffix)**

**As a user, I want my story to have a unique URL so that it can be accessed via a readable link.**

**Acceptance Criteria:**

**Scenario 1: Generate slug from story title with id suffix**

- **Given** I am creating a story
- **When** I enter the title "My Amazing Story!"
- **And** I submit the form
- **Then** the story should have a slug like "my-amazing-story-123" (slugified title + numeric id)
- **And** the slug should contain only URL-safe characters

**Scenario 2: Duplicate titles still produce unique slugs via id**

- **Given** a story already exists with the title "Same Title" and slug "same-title"
- **When** I create a new story with the title "Same Title"
- **And** I submit the form
- **Then** the new story should have a slug like "same-title-456" (different id ensures uniqueness)

**Implementation:**

- Add `slug` to stories migration
- Generate base slug from title, persist record, then append `-{$id}` and save
    - Optional: keep base part for readability and id for uniqueness
- Update route-model resolution to extract id from slug suffix when needed

---

### **[DONE] US-006: View Public Stories List**

**As any visitor, I want to see a list of public stories so that I can discover content to read.**

**Acceptance Criteria:**

**Scenario 1: View public stories (no chapter requirement)**

- **Given** there are public, community, and private stories
- **When** I visit `/stories`
- **Then** I should see only public stories (even if they have no chapters yet)
- **And** I should not see private stories
- **And** I should not see community stories
- **And** each story card shows: cover (default cover if none), title, author name
- **And** the description is visible on hover or via a help icon tooltip/popover

**Scenario 2: Stories list pagination and ordering**

- **Given** there are more than 24 public stories
- **When** I visit `/stories`
- **Then** I should see the first 24 stories ordered by creation date (descending)
- **And** I should see pagination controls
- **And** I should be able to navigate to the next page

**Scenario 3: Empty state**

- **Given** there are no public stories
- **When** I visit `/stories`
- **Then** I should see an empty-state message using scoped Story translations (e.g., `story::index.empty`)

**Implementation:**

- Add `index()` method to `StoryController`
- Create `app/Domains/Story/Views/index.blade.php` grid (4 per row)
- Use default cover asset when story has none (extract a default cover asset)
- Query: `Story` where `visibility = public`
- Order by `created_at` desc
- Paginate: 24 per page
- Translations: use Story-scoped keys (e.g., `story::index.title`, `story::index.empty`), not Shared JSON
- Route: `GET /stories`

---

### **[DONE] US-006-SEO: SEO for Stories Index and Details**

**As a visitor coming from search engines, I want meaningful titles and meta descriptions so that I can understand the
page content.**

**Acceptance Criteria:**

- Index `/stories` sets `<title>` and meta description via localized, Story-scoped translations (e.g.,
  `story::seo.index.title`, `story::seo.index.description`).
- Show `/stories/{slug-with-id}` sets `<title>` as "{story title} – SiteName" and meta description as a short excerpt of
  the description (localized labels via `story::seo.show.*`).
- Open Graph/Twitter basic tags present (title, description, image = story cover or default cover).

**Implementation:**

- Add Blade sections/components to inject `<title>` and meta tags.
- Define Story-scoped translation keys under `app/Domains/Story/Resources/lang/*`.
- Reuse default cover for OG image when story has none.

---

### **[DONE]US-007: View Story Details (slug-with-id)**

**As any visitor, I want to view a public story's details so that I can read its information.**

**Acceptance Criteria:**

**Scenario 1: View public story details via slug-with-id**

- **Given** there is a public story
- **When** I visit `/stories/{slug-with-id}` (e.g., `/stories/my-story-title-123`)
- **Then** I should see the story's title
- **And** I should see the story's description
- **And** I should see the author's name
- **And** I should see the creation date

**Scenario 2: Try to view private story as non-author**

- **Given** there is a private story that I did not create
- **When** I visit `/stories/{slug-with-id}`
- **Then** I should see a 404 error page

**Scenario 3: Try to view community story as guest**

- **Given** there is a community story
- **And** I am not logged in
- **When** I visit `/stories/{slug-with-id}`
- **Then** I should be redirected to the login page

**Implementation:**

- Add show() method to StoryController
- Create show.blade.php view
- Add visibility authorization logic
- Add route for `/stories/{slug}` and resolve model by extracting id from slug suffix

---

### **US-008: Edit Own Stories**

**As a story author, I want to edit my own stories so that I can update the content.**

**Acceptance Criteria:**

**Scenario 1: Author edits their own story**

- **Given** I am the author of a story
- **When** I visit `/stories/{slug-with-id}/edit`
- **Then** I should see the edit form
- **And** the form should be pre-filled with the current title
- **And** the form should be pre-filled with the current description
- **And** the form should be pre-filled with the current visibility

**Scenario 1b: Co-author (role=author) edits the story**

- **Given** I am a collaborator on the story with role = author
- **When** I visit `/stories/{slug-with-id}/edit`
- **Then** I should see the edit form (same as Scenario 1)

**Scenario 2: Non-author tries to edit story**

- **Given** I am not the author of a story
- **When** I try to visit `/stories/{slug-with-id}/edit`
- **Then** I should see a 404 error page

**Scenario 2b: Collaborator without author role tries to edit**

- **Given** I am a collaborator on the story but my role is not author
- **When** I try to visit `/stories/{slug-with-id}/edit`
- **Then** I should see a 404 error page

**Scenario 3: Successfully update story**

- **Given** I am editing my own story
- **When** I change the title and description
- **And** I submit the form
- **Then** the story should be updated with the new information
- **And** I should be redirected to the story detail page
- **And** I should see a success message
- **And** visiting the previous slug base (same id suffix) should 301 redirect to the new canonical slug

**Implementation:**

- Add edit() and update() methods to StoryController
- Create edit.blade.php view
- Add authorization policy that checks membership in `story_collaborators` with role = author
- Add update validation
- Implement 301 redirect in `show()` when the requested slug ends with the correct `-id` but the base changed

---

---

## User Stories - Phase 2: Story Reference Data

### **US-009: Add Story Type Selection**

**As a user creating a story, I want to select a story type so that readers know what kind of story it is.**

### **US-010: Add Audience Selection**

**As a user creating a story, I want to select target audience so that appropriate readers can find my story.**

### **US-011: Add Copyright Selection**

**As a user creating a story, I want to select copyright terms so that my rights are clearly defined.**

### **US-012: Add Genre Selection (1-3)**

**As a user creating a story, I want to select 1-3 genres so that my story is properly categorized.**

## User Stories - Phase 3: Chapters

### **US-013: Add Chapter to Story**

**As a story author, I want to add chapters to my story so that I can organize my content.**

### **US-014: Edit Chapter Content**

**As a story author, I want to edit chapter content with rich text so that I can format my writing.**

### **US-015: Publish/Unpublish Chapters**

**As a story author, I want to control chapter publication status so that I can manage what readers see.**

### **US-016: Read Published Chapters**

**As a reader, I want to read published chapters so that I can enjoy the story.**

### **US-017: Navigate Between Chapters**

**As a reader, I want next/previous navigation so that I can easily move through the story.**

### **US-018: Reorder Chapters with Sparse Ordering**

**As an author/co-author, I want to reorder chapters efficiently so that large stories don't require renumbering
everything.**

**Acceptance Criteria:**

- Drag-and-drop or bulk reorder updates `order` using sparse increments (e.g., 100)
- Reordering does not cause O(n) updates for all rows
- Readers see chapters in the new order immediately

**Implementation:**

- Implement reorder endpoint that assigns spaced order values to minimize churn

### **US-019: Increment Chapter Views Count**

**As an author, I want to see how many times a chapter was read so that I can understand engagement.**

**Acceptance Criteria:**

- When a published chapter page is viewed, `views_count` increments (anonymous + logged)
- Bot traffic mitigations can be added later; simple increment to start

**Implementation:**

- Increment `views_count` on chapter show action; avoid double count per quick refresh is optional for MVP

### **US-020: Manually Mark Chapter as Read (Logged Users)**

**As a logged-in reader, I want to manually mark a chapter as read so that I can track my progress.**

**Acceptance Criteria:**

- A "Mark as read" control is available on chapter pages for logged users
- Creates/updates an entry in `reading_progress (user_id, chapter_id)`
- UI reflects read status

**Implementation:**

- Add endpoint/action to toggle read status; add simple UI control

## User Stories - Phase 4: Advanced Features

### **US-018: Add Trigger Warnings**

**As a story author, I want to add trigger warnings so that readers can make informed decisions.**

### **US-019: Upload Story Cover**

**As a story author, I want to upload a cover image so that my story is visually appealing.**

### **US-020: Filter Stories by Genre**

**As a reader, I want to filter stories by genre so that I can find content I enjoy.**

### **US-021: Co-Author Management (Invite/Leave, Equal Rights)**

**As an author or co-author, I want to invite co-authors and manage collaboration with equal rights.**

**Acceptance Criteria:**

- Any author or co-author can invite additional co-authors
- No one can remove co-authors after acceptance
- Any co-author, including the initial author, can leave the story
- Co-authors have the same editing/publishing permissions as the initial author

**Implementation:**

- Actions for invite and leave; permissions aligned across authors/co-authors; no removal action

## Implementation Strategy

### **User Story Development Process**

1. **Write Feature Test**: Start with the test that defines the behavior
2. **Implement Minimum Code**: Write just enough code to make the test pass
3. **Refactor**: Clean up code while keeping tests green
4. **Manual Validation**: Test the feature in browser
5. **Move to Next Story**: Only after current story is complete

### **Testing Approach**

- **Feature Tests**: Primary testing method for user stories
- **Unit Tests**: Added for complex business logic
- **Browser Tests**: Only for complex UI interactions

### **Validation Criteria Per Story**

✅ Feature test passes  
✅ Manual browser test works  
✅ No existing tests broken  
✅ Code is clean and documented  
✅ Story is demonstrable to stakeholders

### **Benefits of User Story Approach**

- **User-Focused**: Each story delivers value to a specific user type
- **Testable**: Clear acceptance criteria translate to feature tests
- **Incremental**: Each story builds on previous functionality
- **Demonstrable**: Stakeholders can see and validate each capability
- **Flexible**: Stories can be reordered based on priorities
- **Traceable**: Easy to track which features are complete

### **Story Estimation**

- **Small Story**: 1-2 hours (US-001, US-002)
- **Medium Story**: 2-4 hours (US-003, US-006)
- **Large Story**: 4-6 hours (US-013, US-014)

**Each story should be completable in a single focused session.**
