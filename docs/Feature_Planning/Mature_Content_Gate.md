# Mature Content Gate

## Overview

Protect minors from accidentally reading stories targeting mature audiences by displaying an age verification overlay on chapter pages.

---

## Functional Requirements

### 1. Audience Model Extension

Extend `StoryRefAudience` with two new fields:

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `threshold_age` | integer, nullable | `null` | Minimum age to access content (e.g., 16, 18) |
| `is_mature_audience` | boolean | `false` | Whether this audience requires age verification |

**Validation Rule:** If `is_mature_audience` is `true`, `threshold_age` must be set (not null).

**Existing Data:** No data migration needed. Current audiences will have `threshold_age = null` and `is_mature_audience = false`.

---

### 2. Age Verification Overlay

When a user navigates to a chapter (`/stories/{story}/chapters/{chapter}`), if the story's audience has `is_mature_audience = true`:

#### 2.1 Visual Design
- **Semi-transparent overlay** covering the chapter content
- **Blur effect** on the text behind the overlay
- **Prominent age badge** displaying `-{threshold_age}` (e.g., `-18`)
- **Centered dialog** with:
  - Warning message about mature content
  - Checkbox: "I am {threshold_age} years old or older"
  - Button: "Continue reading" (disabled until checkbox checked)
  - Link: "Take me out of here" → redirects to `/stories`

#### 2.2 Content Rendering
- Chapter content **is rendered in the HTML** (not hidden server-side)
- Content is only **visually obscured** by CSS blur + overlay
- This preserves SEO while protecting casual access

#### 2.3 User Flow
```
User arrives on chapter
    ↓
Is story audience mature?
    ├─ No → Show chapter normally
    └─ Yes → Has user confirmed this age threshold (or higher)?
                 ├─ Yes → Show chapter normally
                 └─ No → Display overlay
                            ↓
                    User checks "I am X or older"
                            ↓
                    Click "Continue reading"
                            ↓
                    Store confirmed age in sessionStorage
                            ↓
                    Remove overlay, show content
```

---

### 3. Age Confirmation Storage

#### 3.1 Storage Mechanism
- Use browser **sessionStorage** (not localStorage)
- Clears when browser session ends
- Key: `mature_content_confirmed_age`
- Value: numeric age (e.g., `18`)

#### 3.2 Max-Age Logic
- Store the **highest confirmed age**
- If user confirms 18+, they can access 16+ content without re-confirmation
- Example: User confirms 18 → can view both 16+ and 18+ content

#### 3.3 Scope
- Applies to **all users** (authenticated and anonymous)
- We do not store user age in the database
- Confirmation is per browser session only

---

### 4. Edge Cases

| Scenario | Behavior |
|----------|----------|
| `is_mature_audience = true`, `threshold_age = null` | Invalid data. Admin validation prevents this. If encountered, treat as non-mature (fail-open). |
| User disables JavaScript | Content visible (blurred CSS may not work). Accept this limitation. |
| User clears sessionStorage | Must re-confirm age on next mature chapter. |
| Direct URL access | Same behavior as navigation. |
| Story listing pages | No gate. Covers and synopses must be "all audiences" per site rules. |

---

## Admin Interface

### Migrate to Custom Admin System

Following the [Custom Admin System](./Custom_Admin_System.md) architecture, create a new admin page for managing audiences.

#### Location
```
app/Domains/StoryRef/Private/
  Controllers/Admin/
    AudienceController.php
  Resources/views/pages/admin/
    audiences/
      index.blade.php
      create.blade.php
      edit.blade.php
```

#### Features
- **List audiences** with columns: name, slug, order, threshold_age, is_mature_audience, is_active
- **Create/Edit form** with:
  - Name (text, required)
  - Slug (text, auto-generated from name, editable)
  - Order (number)
  - Is Active (checkbox)
  - Is Mature Audience (checkbox)
  - Threshold Age (number, required if Is Mature Audience is checked, hidden otherwise)
- **Validation**: Conditional validation on threshold_age based on is_mature_audience
- **Reorder** capability (drag-drop or manual order field)

#### Navigation Registration
- Group: "Content"
- Label: "Audiences"
- Icon: `users` or `shield-alert`
- Permissions: admin role
- Order: after other StoryRef admin pages

---

## User Stories (BDD)

### US-MCG-001: Display Age Verification Overlay

```gherkin
Feature: Mature content age verification overlay

  Scenario: User views chapter of mature story without prior confirmation
    Given a story with audience "18+" (threshold_age=18, is_mature_audience=true)
    And the story has a published chapter
    And I have not confirmed any age this session
    When I navigate to the chapter page
    Then I should see a semi-transparent overlay
    And the chapter text should be blurred behind the overlay
    And I should see a "-18" age badge
    And I should see a checkbox "I am 18 years old or older"
    And I should see a disabled "Continue reading" button
    And I should see a "Take me out of here" link

  Scenario: User confirms age and accesses content
    Given I am on a mature chapter page with the overlay displayed
    When I check "I am 18 years old or older"
    And I click "Continue reading"
    Then the overlay should disappear
    And I should see the chapter content clearly
    And "18" should be stored in sessionStorage as confirmed age

  Scenario: User chooses to leave
    Given I am on a mature chapter page with the overlay displayed
    When I click "Take me out of here"
    Then I should be redirected to "/stories"
```

### US-MCG-002: Age Confirmation Persistence

```gherkin
Feature: Age confirmation persists within session

  Scenario: User already confirmed higher age
    Given I have previously confirmed age 18 in this session
    And a story with audience "16+" (threshold_age=16, is_mature_audience=true)
    When I navigate to a chapter of this story
    Then I should see the chapter content directly
    And no overlay should be displayed

  Scenario: User confirmed lower age still sees gate for higher
    Given I have previously confirmed age 16 in this session
    And a story with audience "18+" (threshold_age=18, is_mature_audience=true)
    When I navigate to a chapter of this story
    Then I should see the age verification overlay
    And I should see a "-18" age badge

  Scenario: New browser session requires re-confirmation
    Given I confirmed age 18 in a previous session
    And I closed my browser and opened a new session
    When I navigate to a chapter of a mature story
    Then I should see the age verification overlay
```

### US-MCG-003: Non-Mature Content Unaffected

```gherkin
Feature: Non-mature content displays normally

  Scenario: Chapter of non-mature story
    Given a story with audience "All ages" (is_mature_audience=false)
    When I navigate to a chapter of this story
    Then I should see the chapter content directly
    And no overlay should be displayed

  Scenario: Story listing shows mature stories
    Given a story with audience "18+" (is_mature_audience=true)
    When I visit the stories listing page "/stories"
    Then I should see the story in the list
    And the story cover and synopsis should be visible
    And no age gate should be displayed on the listing
```

### US-MCG-004: Admin Manages Audiences

```gherkin
Feature: Admin manages story audiences

  Background:
    Given I am logged in as an admin

  Scenario: View audience list
    When I navigate to the audiences admin page
    Then I should see a table of audiences
    And each row should show name, threshold_age, and mature status

  Scenario: Create mature audience
    Given I am on the create audience page
    When I fill in "Name" with "Adults Only"
    And I check "Is Mature Audience"
    And I fill in "Threshold Age" with "18"
    And I click "Save"
    Then a new audience should be created
    And it should have is_mature_audience=true and threshold_age=18

  Scenario: Validation prevents incomplete mature audience
    Given I am on the create audience page
    When I fill in "Name" with "Incomplete Mature"
    And I check "Is Mature Audience"
    And I leave "Threshold Age" empty
    And I click "Save"
    Then I should see a validation error
    And no audience should be created

  Scenario: Edit existing audience to add maturity
    Given an audience "Teens" exists with is_mature_audience=false
    When I edit the "Teens" audience
    And I check "Is Mature Audience"
    And I fill in "Threshold Age" with "16"
    And I click "Save"
    Then the audience should be updated
    And it should have is_mature_audience=true and threshold_age=16
```

---

## Technical Notes

### Migration
```
2024_XX_XX_XXXXXX_add_maturity_fields_to_story_ref_audiences
```
- Add `threshold_age` (unsigned tinyint, nullable)
- Add `is_mature_audience` (boolean, default false)

### Frontend Implementation
- Alpine.js component for overlay interaction
- CSS blur filter for content obscuring
- sessionStorage for age persistence
- Check age on page load (Alpine `x-init`)

### i18n Keys
```
story.mature_gate.title
story.mature_gate.badge_prefix (the "-" in "-18")
story.mature_gate.checkbox_label (with :age placeholder)
story.mature_gate.continue_button
story.mature_gate.leave_link
```

---

## Out of Scope

- Server-side content blocking (content is rendered, just visually hidden)
- Permanent age storage in user profile
- IP-based or geo-based age verification
- Integration with external age verification services
- Blurring/hiding stories on listing pages

---

## Dependencies

- `StoryRefAudience` model (StoryRef domain)
- Chapter view (Story domain)
- Custom Admin System infrastructure (Admin domain)
- Alpine.js, sessionStorage (frontend)

---

## Status

| Item | Status |
|------|--------|
| Specification | ✅ Complete |
| Migration | ✅ Complete |
| Admin UI (US-MCG-004) | ✅ Complete |
| Frontend Overlay (US-MCG-001/002/003) | ⏳ Pending |
| Tests | ✅ Admin tests complete |
