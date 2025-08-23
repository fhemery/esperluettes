# Story Domain Implementation Planning - Phase 2

This phase focuses on integrating story reference data and implementing comprehensive filtering capabilities. Each field
implementation is immediately followed by its corresponding filter implementation to maintain focus and handle
consequences properly.

## **Prerequisites**

Phase 2 requires completion of Phase 1 (US-001 through US-008).

---

## **US-009: Collapsible Tabs Form UI (Prerequisite)**

**As a user creating or editing a story, I want the form organized in collapsible tabs so that I can easily navigate
between different sections of story configuration.**

**Acceptance Criteria:**

**Scenario 1: Form displays with collapsible tabs**

- **Given** I am on the story creation or edit page
- **When** the page loads
- **Then** I should see the form organized into collapsible tabs/sections
- **And** the first tab should be expanded by default
- **And** I should be able to click tab headers to expand/collapse sections
- **And** multiple tabs can be open simultaneously

**Scenario 2: Basic Information tab**

- **Given** I am on the story form
- **When** I look at the "Basic Information" tab
- **Then** it should contain: Title, Description, Visibility
- **And** this tab should be expanded by default

**Scenario 3: Form validation works across tabs**

- **Given** I am submitting the story form
- **When** there are validation errors in any tab
- **Then** the tabs containing errors should be visually indicated (e.g., red indicator)
- **And** the first tab with errors should be automatically expanded
- **And** I should see error messages within the appropriate tab

**Implementation:**

- Create collapsible tabs component for story forms
- Organize existing form fields into Basic Information tab
- Create empty placeholder tabs for future fields
- Add JavaScript for tab expand/collapse functionality
- Implement error state indicators for tabs
- Auto-expand first tab with validation errors
- Ensure form submission works regardless of tab state
- Add appropriate styling for content warnings tab
- Maintain accessibility standards for tab navigation

---

## **US-010: Add Story Type Selection**

**As a user creating a story, I want to select a story type so that readers know what kind of story it is.**

**Acceptance Criteria:**

**Scenario 1: Story type field appears in creation form**

- **Given** I am a logged-in user
- **When** I am on the story creation page
- **Then** I should see a "Story Type" dropdown field in the "Categorization" tab
- **And** the dropdown should be populated with active story types from `StoryRefType`
- **And** the field should be marked as required
- **And** the dropdown should show types ordered by their `order` field

**Scenario 2: Story type is required for creation**

- **Given** I am creating a story
- **When** I fill in all other required fields but leave "Story Type" empty
- **And** I submit the form
- **Then** I should see a validation error for "Story Type"
- **And** the error should indicate the field is required
- **And** the "Categorization" tab should be visually indicated as having errors

**Scenario 3: Successfully create story with story type**

- **Given** I am creating a story
- **When** I select a story type and fill in other required fields
- **And** I submit the form
- **Then** the story should be created successfully
- **And** the story detail page should display the selected story type
- **And** the story type should be stored in the database

**Scenario 4: Story type appears in edit form**

- **Given** I am editing my own story
- **When** I visit the story edit page
- **Then** the "Story Type" dropdown should be pre-selected with the current value
- **And** I should be able to change the story type
- **And** saving should update the story type

**Scenario 5: Story type displays on story detail page**

- **Given** a story has a story type
- **When** I view the story detail page
- **Then** I should see the story type displayed prominently in the story metadata section
- **And** the story type should be clearly labeled

**Scenario 6: Story type displays on story index cards**

- **Given** there are stories with story types
- **When** I view the stories index page
- **Then** each story card should display the story type
- **And** the story type should be visually distinct from other metadata

**Implementation:**

- Add `story_ref_type_id` foreign key to stories table migration
- Update Story model with `belongsTo` relationship to `StoryRefType`
- Add story type dropdown to "Categorization" tab in create/edit forms
- Update `StoryRequest` validation to require `story_ref_type_id`
- Display story type on story detail page in metadata section
- Add story type to story cards on index page
- Ensure proper error handling and tab error indicators

---

## **US-011: Filter Stories by Story Type**

**As a reader, I want to filter stories by story type so that I can find the specific kind of content I'm looking for.**

**Acceptance Criteria:**

**Scenario 1: Story type filter appears on stories index**

- **Given** I am on the stories index page
- **When** I look at the filtering options
- **Then** I should see a "Story Type" filter dropdown
- **And** the dropdown should include "All Types" as the default option
- **And** the dropdown should list all active story types from `StoryRefType`
- **And** the types should be ordered by their `order` field

**Scenario 2: Filter stories by specific type**

- **Given** there are stories with different story types
- **When** I select a specific story type from the filter
- **And** I apply the filter
- **Then** I should see only stories that have the selected story type
- **And** the URL should reflect the filter parameter (e.g., `?type=fiction`)
- **And** the filter should persist when navigating between pages

**Scenario 3: Clear story type filter**

- **Given** I have applied a story type filter
- **When** I select "All Types" from the dropdown
- **Then** I should see all public stories again
- **And** the filter parameter should be removed from the URL

**Scenario 4: Filter state persists across navigation**

- **Given** I have applied a story type filter
- **When** I navigate to page 2 of results
- **Then** the filter should still be active
- **And** the URL should contain both pagination and filter parameters

**Implementation:**

- Add story type filter dropdown to stories index page
- Update `StoryController@index` to handle `type` query parameter
- Modify story query to filter by `story_ref_type_id` when parameter is present
- Ensure filter state persists in pagination links
- Add filter state indicators to UI
- Update URL parameters for bookmarkable filtered views
- Handle empty results with appropriate messaging

---

## **US-012: Add Target Audience Selection**

**As a user creating a story, I want to select target audience so that appropriate readers can find my story.**

**Acceptance Criteria:**

**Scenario 1: Audience field appears in creation form**

- **Given** I am a logged-in user
- **When** I am on the story creation page
- **Then** I should see a "Target Audience" dropdown field in the "Categorization" tab
- **And** the dropdown should be populated with active audiences from `StoryRefAudience`
- **And** the field should be marked as required
- **And** the dropdown should show audiences ordered by their `order` field

**Scenario 2: Audience is required for creation**

- **Given** I am creating a story
- **When** I fill in all other required fields but leave "Target Audience" empty
- **And** I submit the form
- **Then** I should see a validation error for "Target Audience"

**Scenario 3: Successfully create story with audience**

- **Given** I am creating a story
- **When** I select a target audience and fill in other required fields
- **And** I submit the form
- **Then** the story should be created successfully
- **And** the story detail page should display the selected audience
- **And** the audience should be stored in the database

**Scenario 4: Audience appears in edit form**

- **Given** I am editing my own story
- **When** I visit the story edit page
- **Then** the "Target Audience" dropdown should be pre-selected with the current value
- **And** I should be able to change the audience
- **And** saving should update the audience

**Scenario 5: Audience displays on story detail page**

- **Given** a story has a target audience
- **When** I view the story detail page
- **Then** I should see the target audience displayed in the story metadata section
- **And** the audience should be clearly labeled

**Scenario 6: Audience displays on story index cards**

- **Given** there are stories with target audiences
- **When** I view the stories index page
- **Then** each story card should display the target audience
- **And** the audience should be visually distinct from other metadata

**Implementation:**

- Add `story_ref_audience_id` foreign key to stories table migration
- Update Story model with `belongsTo` relationship to `StoryRefAudience`
- Add audience dropdown to "Categorization" tab in create/edit forms
- Update `StoryRequest` validation to require `story_ref_audience_id`
- Display audience on story detail page
- Add audience to story cards on index page

---

## **US-013: Filter Stories by Target Audience**

**As a reader, I want to filter stories by target audience so that I can find age-appropriate content.**

**Acceptance Criteria:**

**Scenario 1: Audience filter appears on stories index**

- **Given** I am on the stories index page
- **When** I look at the filtering options
- **Then** I should see a "Target Audience" filter dropdown
- **And** the dropdown should include "All Audiences" as the default option
- **And** the dropdown should list all active audiences from `StoryRefAudience`
- **And** the audiences should be ordered by their `order` field

**Scenario 2: Filter stories by specific audience**

- **Given** there are stories with different target audiences
- **When** I select a specific audience from the filter
- **And** I apply the filter
- **Then** I should see only stories that have the selected target audience
- **And** the URL should reflect the filter parameter (e.g., `?audience=adult`)
- **And** the filter should persist when navigating between pages

**Scenario 3: Clear audience filter**

- **Given** I have applied an audience filter
- **When** I select "All Audiences" from the dropdown
- **Then** I should see all public stories again
- **And** the filter parameter should be removed from the URL

**Scenario 4: Combine with existing filters**

- **Given** I have applied a story type filter
- **When** I also apply an audience filter
- **Then** I should see stories that match both criteria
- **And** the URL should contain both filter parameters

**Implementation:**

- Add audience filter dropdown to stories index page
- Update `StoryController@index` to handle `audience` query parameter
- Modify story query to filter by `story_ref_audience_id` when parameter is present
- Ensure filter state persists in pagination links
- Update URL parameters for bookmarkable filtered views
- Handle multiple active filters in UI

---

*[Continue with remaining user stories US-014 through US-021 following the same pattern...]*
