# Story Collaborators (Co-authors and Beta-readers)

## Summary

This feature allows story authors to add collaborators with different roles: **author** (co-author) or **beta-reader**. Authors have full editing access, while beta-readers have read access to private stories.

---

## Functional Rules

### Available Roles

| Role | Slug | Permissions | Eligibility |
|------|------|-------------|-------------|
| **Author** | `author` | Full editing (story, chapters), collaborator management, deletion | `USER_CONFIRMED` only |
| **Beta-reader** | `beta-reader` | Read access to private stories, access to unpublished chapters | `USER` or `USER_CONFIRMED` |

### Role Management

- **One user = one role per story**: The unique constraint `(story_id, user_id)` is maintained.
- **Upgrade allowed**: A beta-reader can be promoted to author (role replacement).
- **Downgrade forbidden**: Assigning beta-reader to an author is a no-op (no change).
- **Author role is irreversible**: Once an author, only voluntary departure removes the role.

### Adding Collaborators

- Only **authors** can add collaborators.
- Adding an **author** displays a confirmation:
  > "Granting author access is irreversible. The Esperluette with this right will be the only one able to leave the project. Are you sure?"
- Adding a **beta-reader** is immediate (no confirmation).
- The back-end validates role eligibility based on the target user's status.

### Removing Collaborators

- **Authors cannot be removed** by other authors.
- **Beta-readers can be removed** by any author.
- An **author can leave** the story voluntarily, unless they are the only author.

### Author Departure

- The author loses all access to the story.
- Chapters created by this author remain in the story.
- Other authors retain their rights.
- No ownership transfer is performed.

### Beta-reader Access

- Access to stories with `private` visibility where they are collaborators.
- Access visible via a dedicated tab in their profile (accessible private stories).
- Stories in reading list remain accessible if the beta-reader has access.

---

## Current Implementation Status

### What Already Exists

| Element | File | Description |
|---------|------|-------------|
| `StoryCollaborator` model | `app/Domains/Story/Private/Models/StoryCollaborator.php` | Fields: `story_id`, `user_id`, `role`, `invited_by_user_id`, `invited_at`, `accepted_at` |
| `story_collaborators` table | `app/Domains/Story/Database/Migrations/2025_08_17_000001_create_story_collaborators_table.php` | Unique constraint on `(story_id, user_id)` |
| `Story` model | `app/Domains/Story/Private/Models/Story.php` | Methods: `collaborators()`, `authors()`, `isCollaborator()`, `isAuthor()` |
| Visibility policy | `app/Domains/Story/Private/Policies/StoryPolicy.php` | Checks `isCollaborator()` for private stories |
| Profile search API | `app/Domains/Shared/Contracts/ProfilePublicApi.php` | Methods: `searchDisplayNames()`, `searchPublicProfiles()` |
| Author display | `app/Domains/Story/Private/Resources/views/show.blade.php` | Component `<x-profile::inline-names>` |
| ViewModel | `app/Domains/Story/Private/ViewModels/StoryShowViewModel.php` | Property `authors`, method `isAuthor()` |
| User roles | `app/Domains/Auth/Public/Api/Roles.php` | Constants `USER`, `USER_CONFIRMED` |

### What Is Missing

- Collaborator management page
- Routes for collaborator CRUD
- "Group" icon on story page
- Badge showing collaborator count
- Role assignment UI with profile search
- Leave functionality for authors
- Badge for non-author collaborators with leave popover
- Profile tab for accessible private stories

---

## User Stories

### US-COLLAB-01: Display collaborator management icon

**As** a story author,  
**I want** to see an icon representing a group of people next to the edit icon,  
**So that** I can access collaborator management.

**Acceptance Criteria:**
- The icon appears only for authors
- If more than one collaborator exists, a badge with the total count appears (tertiary color)
- The icon is clickable and redirects to the management page

---

### US-COLLAB-02: Collaborator management page

**As** a story author,  
**I want** to access a page listing all collaborators,  
**So that** I can view and manage access to my story.

**Acceptance Criteria:**
- Authors are listed first, then other collaborators
- Each row displays: avatar, display name (from Profile domain), translated role
- Non-authors have a delete button
- Authors do not have a delete button
- If the current user is an author and there are other authors, a leave icon is displayed on their own row
- The leave icon does not appear if the user is the only author

---

### US-COLLAB-03: Search and add a collaborator

**As** a story author,  
**I want** to search for a profile and assign a role to it,  
**So that** I can add a new collaborator to my story.

**Acceptance Criteria:**
- A search field allows finding profiles by display name
- A dropdown allows selecting the role (Author, Beta-reader)
- Adding an author displays a confirmation modal with the defined message
- Adding a beta-reader is immediate
- If the profile already has the same role or a higher role, it's a no-op
- The back-end rejects the addition if the target user is not eligible for the role

---

### US-COLLAB-04: Remove a beta-reader

**As** a story author,  
**I want** to be able to remove a beta-reader,  
**So that** I can revoke their access to my story.

**Acceptance Criteria:**
- A delete button is available on the beta-reader's row
- Confirmation is requested before deletion
- After deletion, the beta-reader no longer has access to the private story

---

### US-COLLAB-05: Leave a story as an author

**As** an author of a story with other authors,  
**I want** to be able to leave the story,  
**So that** I no longer have access or responsibility for it.

**Acceptance Criteria:**
- The leave icon appears only if other authors exist
- Confirmation is requested
- After leaving, the user no longer has access to the story
- Chapters created by the user remain in the story

---

### US-COLLAB-06: Collaborator badge for non-authors

**As** a non-author collaborator (beta-reader) of a story,  
**I want** to see a badge indicating my role on the story page,  
**So that** I know I have special access and can leave if I wish.

**Acceptance Criteria:**
- The badge appears at the top right of the page, near the edit icons
- The badge displays the translated role name
- A popover contains a button to leave the story
- The badge does not display the collaborator count
- After leaving, the user loses all access to the story

---

### US-COLLAB-07: Accessible private stories tab in profile

**As** a beta-reader of private stories,  
**I want** to see a tab in my profile listing the private stories I have access to,  
**So that** I can easily find them.

**Acceptance Criteria:**
- The tab only appears if the user has access to at least one private story as a non-author collaborator
- Stories are listed with the usual information (title, authors, etc.)
- Clicking on a story redirects to its page

---

### US-COLLAB-08: Access to stories in reading list

**As** a user with a story in my reading list,  
**I want** to continue accessing it if I am a collaborator,  
**So that** I don't lose my ongoing reads.

**Acceptance Criteria:**
- If a story in the reading list is private, it remains accessible if the user is a collaborator
- If the user loses access (removal or departure), the story remains in the list but is no longer accessible
