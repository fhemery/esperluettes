# Story Covers Feature

## Overview

This feature allows story authors to customize the cover image displayed for their stories. Authors can choose between three options:
1. **Default cover** — The platform's themed SVG cover that adapts to seasonal themes
2. **Genre-based cover** — Pre-defined cover images based on the story's selected genres
3. **Custom cover** — User-uploaded cover image (feature-flipped, disabled by default)

## Current State

- All stories display the default cover (`<x-shared::default-cover>` component)
- The default cover is an inline SVG that follows theme changes
- A static SVG exists at `public/images/story/default-cover.svg` for SEO purposes
- No cover selection UI exists in story creation or editing forms
- The `Story` model has no cover-related fields

## Functional Requirements

### FR-1: Cover Type Selection

Authors can select one of three cover types for their story:

| Type | Description | Availability |
|------|-------------|--------------|
| `default` | Platform's themed SVG cover | Always available |
| `themed` | Pre-defined image based on story genre | When story has genres with covers |
| `custom` | User-uploaded image | When feature flag enabled |

### FR-2: Default Cover Behavior

- The default cover remains the platform's inline SVG component
- It adapts to seasonal theme changes automatically
- For SEO/Open Graph, the static SVG at `public/images/story/default-cover.svg` is used
- New stories default to `cover_type = 'default'`

### FR-3: Genre-Based (Themed) Covers

- Pre-defined cover images exist in `public/images/story/` directory
- File naming convention: `{genre-slug}.jpg` (300px) and `{genre-slug}-hd.jpg` (HD version)
- Not all genres have covers — controlled by `has_cover` boolean on `StoryRefGenre` model
- Admin can toggle `has_cover` in the genre administration screen
- Admin UI shows whether the corresponding image file exists on disk
- When selecting a themed cover, user chooses from a dropdown of their story's genres that have covers
- If user removes the genre associated with their selected cover, the cover silently reverts to default

### FR-4: Custom Covers (Feature-Flipped)

- Controlled by feature toggle: domain=`Story`, key=`customCovers.enabled`
- When enabled, users can upload their own cover image
- **Upload constraints:**
  - Maximum file size: 2MB
  - Accepted formats: JPG, PNG, WebP
  - Images are resized to two versions:
    - HD: 900px wide (maintaining aspect ratio, preferred 900×1200px)
    - Small: 300px wide (for list/card displays)
- **Storage:** `storage/app/public/covers/{story_id}/cover.jpg` and `cover-hd.jpg`
- One custom cover per story is stored, even if not currently selected
- Users can switch back to default/themed and later re-select their uploaded cover
- **Important notice:** Upload form must display a prominent warning that AI-generated covers are not allowed

### FR-5: Cover Display Component

A new `<x-story::cover>` component that:
- Accepts story model or cover data as props
- Renders the appropriate cover based on `cover_type`:
  - `default`: Renders the inline SVG component
  - `themed`: Renders the genre-based JPG image
  - `custom`: Renders the user-uploaded image
- Displays the small version (300px) by default, configurable width (150px or 300px typical)
- On click, opens a lightbox overlay displaying the HD version
- For default cover (SVG), no lightbox behavior (SVG scales infinitely)

### FR-6: SEO & Open Graph

- Story pages include `og:image` meta tag with the appropriate cover
- Uses the small version (300px) for Open Graph
- For default cover, uses `public/images/story/default-cover.svg`
- For themed covers, uses `public/images/story/{genre-slug}.jpg`
- For custom covers, uses the stored 300px version

### FR-7: Moderation

- Moderators see a "Revert cover to default" action button on story show page
- This action:
  - Sets `cover_type` to `default`
  - Deletes the custom cover files from storage
  - Clears `cover_genre_slug` if set
  - Emits a `Story.CoverRevertedByModerator` domain event
- Action appears in the existing moderation dropdown on `show.blade.php`

---

## UI/UX Specifications

### Form Layout Changes

The story creation/edit form layout is reorganized:

**General Panel (Collapsible, open by default):**
```
Row 1: [Title (3/4 width)] [Type (1/4 width)]
Row 2: [Genres (1/2 width)] [Status + "Story is complete" checkbox (1/2 width)]
Row 3: [Visibility (1/2 width)] [Copyright (1/2 width)]
```

**Présentation Panel (Collapsible, open by default):**
```
[Cover with "Change cover" button (left)] [Summary editor (right)]
```

**Audience Panel:** Unchanged

**Miscellaneous Panel:** Unchanged

### Cover Selection Pop-up

Triggered by "Changer la couverture" button below the current cover preview.

**Three tabs:**

#### Tab 1: "Par défaut"
- Left side: Preview of the default cover component
- Right side: 
  - Text: "La couverture par défaut du Jardin"
  - "Sélectionner" button

#### Tab 2: "Par thème"
- Left side: Preview of currently selected genre cover (or placeholder)
- Right side:
  - Text: "Choisissez parmi les thèmes de votre œuvre"
  - Dropdown listing story's genres that have covers
  - "Sélectionner" button
- If no genres have covers: Display only "Aucune couverture disponible pour les thèmes sélectionnés"

#### Tab 3: "Personnelle" (only visible when feature flag enabled)
- Left side: Preview of uploaded image (or current custom cover if exists)
- Right side:
  - Image upload component (`<x-shared::image-upload>`)
  - Recommended dimensions: 900×1200px
  - Max size: 2MB
  - **Warning notice:** "Les couvertures générées par IA ne sont pas autorisées"
  - "Sélectionner" button

**Behavior:**
- Cover preview updates immediately when user makes a selection in the pop-up
- No changes are persisted until the main form's "Save" button is clicked
- Selection state is preserved via `old()` values if form validation fails
- Closing the pop-up without clicking "Sélectionner" discards the selection

---

## Data Model Changes

### Story Model

Add the following fields to `stories` table:

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `cover_type` | enum('default','themed','custom') | 'default' | Type of cover selected |
| `cover_genre_slug` | string, nullable | null | Genre slug for themed covers |

### StoryRefGenre Model

Add the following field to `story_ref_genres` table:

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `has_cover` | boolean | false | Whether this genre has a pre-defined cover image |

### File Storage

Custom covers stored at:
```
storage/app/public/covers/{story_id}/
├── cover.jpg      (300px wide)
└── cover-hd.jpg   (900px wide)
```

---

## Domain Events

### Story.CoverRevertedByModerator

Emitted when a moderator reverts a story's cover to default.

**Payload:**
```php
[
    'story_id' => int,
    'story_title' => string,
    'previous_cover_type' => string,  // 'themed' or 'custom'
    'previous_cover_value' => ?string, // genre slug or 'custom'
    'moderator_user_id' => int,
]
```

**Summary:** "Cover reverted to default for story '{title}' (was: {previous_type})"

---

## Feature Toggle

| Domain | Key | Default | Description |
|--------|-----|---------|-------------|
| Story | customCovers.enabled | false | Enables custom cover upload functionality |

---

## Implementation Phases

Development is split into three phases, each building on the previous. Stories are small and incremental to minimize risk in this critical domain.

---

## Phase 1: Default Cover Foundation

**Goal:** Refactor to database-driven cover handling. No user-facing changes yet — all stories continue showing the default cover, but the infrastructure is in place.

### P1-US1: Add cover_type field to Story model
**As a** developer  
**I want to** add a `cover_type` field to the stories table  
**So that** cover information is stored in the database

**Acceptance Criteria:**
- Migration adds `cover_type` enum field with values: `default`, `themed`, `custom`
- Default value is `'default'`
- Story model has `cover_type` in fillable and casts
- All existing stories have `cover_type = 'default'` after migration
- Tests verify field exists and defaults correctly

---

### P1-US2: Create CoverService with URL resolution
**As a** developer  
**I want to** create a CoverService that resolves cover URLs  
**So that** cover URL logic is centralized and testable

**Acceptance Criteria:**
- `CoverService` created in Story domain
- Method `getCoverUrl(Story $story): string` returns appropriate URL
- Method `getCoverHdUrl(Story $story): ?string` returns HD URL (null for default)
- For `cover_type = 'default'`, returns `asset('images/story/default-cover.svg')`
- Unit tests cover all cover types (themed/custom return placeholders for now)

---

### P1-US3: Create cover display component
**As a** developer  
**I want to** create a `<x-story::cover>` component  
**So that** cover display logic is encapsulated and reusable

**Acceptance Criteria:**
- Component accepts `story` or `coverType` + `coverUrl` props
- Component accepts `width` prop (default: 150, options: 150, 300)
- For `cover_type = 'default'`, renders `<x-shared::default-cover>`
- For other types, renders `<img>` tag (placeholder behavior for now)
- Component has `aria-hidden="true"` (decorative image)
- Tests verify correct rendering for default type

---

### P1-US4: Replace hardcoded covers in story show page
**As a** developer  
**I want to** use the new cover component on the story show page  
**So that** covers are rendered from database state

**Acceptance Criteria:**
- `show.blade.php` uses `<x-story::cover :story="$viewModel->story" />`
- Removes direct usage of `<x-shared::default-cover>`
- Visual output is identical to before (no regression)
- Tests verify cover component is rendered

---

### P1-US5: Update Open Graph meta tags to use CoverService
**As a** developer  
**I want to** use CoverService for og:image meta tags  
**So that** SEO covers are consistent with display covers

**Acceptance Criteria:**
- `show.blade.php` meta tags use `$coverService->getCoverUrl($story)`
- ViewModel or controller passes cover URL to view
- Default cover still uses `asset('images/story/default-cover.svg')`
- Tests verify meta tag contains correct URL

---

### P1-US6: Replace covers in story list/card views
**As a** developer  
**I want to** use the cover component in all story listings  
**So that** covers are consistent across the platform

**Acceptance Criteria:**
- Identify all views displaying story covers (cards, lists, search results)
- Replace with `<x-story::cover>` component
- All displays use appropriate width (150 or 300)
- Visual regression tests or manual verification

---

## Phase 2: Themed Covers

**Goal:** Enable genre-based cover selection. Authors can choose covers matching their story's genres.

### P2-US1: Add has_cover field to StoryRefGenre
**As a** developer  
**I want to** add a `has_cover` boolean to genres  
**So that** we can track which genres have pre-defined covers

**Acceptance Criteria:**
- Migration adds `has_cover` boolean, default `false`
- StoryRefGenre model has field in fillable and casts
- Tests verify field behavior

---

### P2-US2: Admin UI for genre has_cover toggle
**As an** admin  
**I want to** toggle `has_cover` for each genre  
**So that** I can control which genres offer themed covers

**Acceptance Criteria:**
- Genre create/edit form has "Has cover" checkbox
- Form displays indicator if image file exists: `public/images/story/{slug}.jpg`
- Warning shown if `has_cover` is true but file doesn't exist
- Genre list shows cover status column
- Tests verify toggle saves correctly

### P2-US5: CoverService handles themed covers
**As a** developer  
**I want to** extend CoverService to resolve themed cover URLs  
**So that** genre-based covers display correctly

**Acceptance Criteria:**
- `getCoverUrl()` returns `asset("images/story/{slug}.jpg")` for themed
- `getCoverHdUrl()` returns `asset("images/story/{slug}-hd.jpg")` for themed
- Method `getAvailableThemedCovers(Story $story): array` returns genres with covers
- Checks both `has_cover = true` AND file existence
- Unit tests for all scenarios

---

### P2-US6: Cover component renders themed covers
**As a** developer  
**I want to** the cover component to display themed cover images  
**So that** genre covers appear correctly

**Acceptance Criteria:**
- Component renders `<img>` for `cover_type = 'themed'`
- Uses small version URL from CoverService
- Proper loading/error handling for images
- Tests verify themed cover rendering

---

### P2-US7: Reorganize story form layout
**As a** developer  
**I want to** reorganize the story form panels  
**So that** cover selection fits naturally in the form

**Acceptance Criteria:**
- General panel: Title, Type, Genres, Status + checkbox, Visibility, Copyright
- New "Présentation" panel: Cover area (left), Summary (right)
- Genres moved from Details to General panel
- Form still works identically (no functional changes yet)
- Tests verify form submission still works

---

### P2-US8: Add cover preview to story form
**As an** author  
**I want to** see my current cover in the story form  
**So that** I know what cover is currently selected

**Acceptance Criteria:**
- Présentation panel shows current cover using `<x-story::cover>`
- "Changer la couverture" button displayed below cover
- Button is disabled/hidden for now (no modal yet)
- Edit form shows story's current cover
- Create form shows default cover

---

### P2-US9: Create cover selection modal structure
**As a** developer  
**I want to** create the cover selection modal component  
**So that** users can choose their cover type

**Acceptance Criteria:**
- Modal component `<x-story::cover-selector>` created
- Three tabs: "Par défaut", "Par thème", "Personnelle"
- "Personnelle" tab hidden (Phase 3)
- Modal opens when "Changer la couverture" clicked
- Modal can be closed without changes
- Alpine.js state management for tab switching

---

### P2-US10: Default cover tab in modal
**As an** author  
**I want to** select the default cover from the modal  
**So that** I can use the platform's themed cover

**Acceptance Criteria:**
- "Par défaut" tab shows default cover preview (left)
- Text: "La couverture par défaut du Jardin" (right)
- "Sélectionner" button sets cover choice
- Form preview updates when selected
- Hidden inputs store `cover_type = 'default'`

---

### P2-US11: Themed cover tab in modal
**As an** author  
**I want to** select a genre-based cover from the modal  
**So that** my story has a thematic cover

**Acceptance Criteria:**
- "Par thème" tab shows cover preview (left) and controls (right)
- Dropdown lists story's genres that have covers (from CoverService)
- Selecting genre updates preview immediately
- "Sélectionner" button confirms choice
- If no genres have covers: "Aucune couverture disponible pour les thèmes sélectionnés"
- Hidden inputs store `cover_type = 'themed'` and `cover_genre_slug`

---

### P2-US12: Genre dropdown updates with form genres
**As an** author  
**I want** the themed cover dropdown to reflect my current genre selection  
**So that** I only see covers for genres I've selected

**Acceptance Criteria:**
- When genres change in form, available themed covers update
- Alpine.js watches genre multi-select changes
- Dropdown repopulates with matching genres that have covers
- If current themed cover's genre is removed, preview shows warning

---

### P2-US13: Save cover selection on form submit
**As an** author  
**I want** my cover selection to be saved when I submit the form  
**So that** my cover choice persists

**Acceptance Criteria:**
- StoryController handles `cover_type` and `cover_genre_slug` inputs
- Validation: `cover_type` must be valid enum value
- Validation: `cover_genre_slug` required if `cover_type = 'themed'`
- Validation: `cover_genre_slug` must be in story's selected genres
- StoryService updates cover fields
- Tests for create and update flows

---

### P2-US14: Genre removal resets themed cover
**As an** author  
**I want** my cover to reset to default if I remove its genre  
**So that** my story always has a valid cover

**Acceptance Criteria:**
- On save, if `cover_type = 'themed'` but genre not in selection
- Automatically set `cover_type = 'default'`, clear `cover_genre_slug`
- No error shown to user (silent fallback)
- Tests verify fallback behavior

---

### P2-US15: Preserve cover selection on validation error
**As an** author  
**I want** my cover selection preserved if form validation fails  
**So that** I don't lose my choice

**Acceptance Criteria:**
- `old('cover_type')` and `old('cover_genre_slug')` used in form
- Cover preview shows previously selected cover
- Modal state reflects previous selection
- Tests verify preservation on validation error

---

### P2-US16: Add lightbox for themed covers
**As a** reader  
**I want to** click a themed cover to see it larger  
**So that** I can appreciate the artwork

**Acceptance Criteria:**
- Cover component detects clickable covers (themed, later custom)
- Click opens lightbox overlay with HD image
- Lightbox closes on click outside, Escape key, or close button
- Default SVG cover is not clickable (no lightbox)
- Smooth fade-in/out animation

---

## Phase 3: Custom Covers

**Goal:** Allow users to upload their own cover images (feature-flipped).

### P3-US1: Create feature toggle for custom covers
**As a** developer  
**I want to** create a feature toggle for custom covers  
**So that** the feature can be enabled gradually

**Acceptance Criteria:**
- Feature toggle: domain=`Story`, key=`customCovers.enabled`
- Default: disabled
- Toggle accessible via Config domain's FeatureToggle system
- Tests verify toggle behavior

---

### P3-US2: CoverService handles custom cover URLs
**As a** developer  
**I want to** extend CoverService for custom cover URLs  
**So that** uploaded covers display correctly

**Acceptance Criteria:**
- `getCoverUrl()` returns storage URL for custom covers
- `getCoverHdUrl()` returns HD storage URL for custom covers
- Path: `storage/covers/{story_id}/cover.jpg` and `cover-hd.jpg`
- Method `hasCustomCover(Story $story): bool` checks file existence
- Method `getCustomCoverUrl(Story $story): ?string` for modal preview
- Unit tests for custom cover resolution

---

### P3-US3: Cover component renders custom covers
**As a** developer  
**I want to** the cover component to display custom covers  
**So that** uploaded covers appear correctly

**Acceptance Criteria:**
- Component renders `<img>` for `cover_type = 'custom'`
- Uses storage URL from CoverService
- Lightbox works for custom covers (HD version)
- Tests verify custom cover rendering

---

### P3-US4: Custom cover tab in modal (upload UI)
**As an** author  
**I want to** see the custom cover upload tab  
**So that** I can upload my own cover

**Acceptance Criteria:**
- "Personnelle" tab visible only when feature flag enabled
- Left side: preview of current/uploaded image
- Right side: `<x-shared::image-upload>` component
- Recommended dimensions: 900×1200px, max 2MB
- **Warning notice:** "Les couvertures générées par IA ne sont pas autorisées"
- "Sélectionner" button to confirm selection
- If story has existing custom cover, show it in preview

---

### P3-US5: Custom cover upload processing
**As a** developer  
**I want to** process uploaded cover images  
**So that** they are properly resized and stored

**Acceptance Criteria:**
- CoverService method `uploadCustomCover(Story $story, UploadedFile $file)`
- Resize to 900px wide (HD) maintaining aspect ratio
- Resize to 300px wide (small) maintaining aspect ratio
- Store as JPG in `storage/app/public/covers/{story_id}/`
- Delete previous custom cover files if they exist
- Uses Intervention Image library
- Tests verify upload, resize, and storage

---

### P3-US6: Save custom cover on form submit
**As an** author  
**I want** my uploaded cover to be saved with my story  
**So that** my custom cover persists

**Acceptance Criteria:**
- Form handles file upload for custom cover
- StoryController processes upload via CoverService
- Sets `cover_type = 'custom'` when custom cover uploaded
- Validation: file size ≤ 2MB, formats JPG/PNG/WebP
- Tests for upload flow

---

### P3-US7: Re-select existing custom cover
**As an** author  
**I want to** re-select my previously uploaded cover  
**So that** I don't have to re-upload it

**Acceptance Criteria:**
- "Personnelle" tab shows existing custom cover if present
- User can select it without uploading new file
- "Sélectionner" button works with existing cover
- Custom cover files retained when switching to default/themed

---

### P3-US8: Moderator revert cover action
**As a** moderator  
**I want to** revert a story's cover to default  
**So that** I can remove inappropriate covers

**Acceptance Criteria:**
- "Réinitialiser la couverture" action in moderation dropdown
- Action visible only for stories with non-default covers
- Sets `cover_type = 'default'`, clears `cover_genre_slug`
- Deletes custom cover files from storage
- Emits `Story.CoverRevertedByModerator` domain event
- Tests verify action and event emission

---

### P3-US9: Create CoverRevertedByModerator event
**As a** developer  
**I want to** create a domain event for cover moderation  
**So that** the action is auditable

**Acceptance Criteria:**
- Event implements `DomainEvent` and `AuditableEvent`
- Name: `Story.CoverRevertedByModerator`
- Payload: story_id, story_title, previous_cover_type, previous_cover_value, moderator_user_id
- Summary: "Cover reverted to default for story '{title}' (was: {type})"
- Event registered in StoryServiceProvider
- Tests verify event structure and emission

---

## Technical Implementation Notes

### Migration Strategy
- Add `cover_type` with default `'default'` — existing stories automatically use default
- Add nullable `cover_genre_slug`
- Add `has_cover` boolean to `story_ref_genres` with default `false`

### Cover Service
Create `CoverService` in Story domain to handle:
- Cover type resolution
- Custom cover upload/resize (using Intervention Image)
- Custom cover deletion
- URL generation for different cover types

### Genre Cover Detection
When loading cover selection UI:
1. Get story's selected genres
2. Filter to those with `has_cover = true`
3. For each, verify file exists at `public/images/story/{slug}.jpg`
4. Return only genres with both flag and file

### Component Architecture
- `<x-story::cover>` — Main display component with lightbox
- `<x-story::cover-selector>` — Pop-up modal for cover selection
- Reuse `<x-shared::image-upload>` for custom cover uploads

---

## Out of Scope

- Bulk cover operations
- Cover approval workflow (custom covers are immediately visible)
- Cover cropping tool in UI
- Multiple custom covers per story
- Cover templates or generators
