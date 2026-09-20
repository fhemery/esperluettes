# Fix chapter table of contents alignment — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Fix vertical alignment of chapter names and dates relative to action buttons on the story table of contents page. The chapter info (title and publication date) is currently offset upward compared to the action buttons on the right, creating visual misalignment in both author and reader chapter list views.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Table of contents | The page displaying the list of chapters in a story, with chapter names, dates, and action buttons |
| Author view | Chapter list shown to the story author, with edit/delete buttons |
| Reader view | Chapter list shown to readers, with read/comment/statistics buttons |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Reader view (if story is public) | Read chapters |
| `user` (non-confirmed) | Reader view (if allowed by story) | Read chapters |
| `user-confirmed` | Reader view | Read chapters |
| Author / co-author | Author view with edit/delete buttons | Manage chapters |
| Moderator | Reader view | Read chapters |
| Admin | Both views depending on context | Read chapters |

## 4. Functional requirements

### 4.1 Chapter list alignment

Chapter names and publication dates align vertically with action buttons to the right:
- Both chapter info and action buttons share the same baseline
- Visual hierarchy is clear and consistent
- Works in both author view (edit/delete buttons) and reader view (read/comment/stats buttons)

## 5. Lifecycle

N/A — this is a visual fix with no data model or state changes. Existing chapters display correctly after the fix is deployed.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Not applicable — visual fix affects all roles viewing the chapter list equally |
| Visibility / privacy | N/A — no visibility changes |
| Settings | N/A — no new settings |
| Notifications | N/A — no notifications involved |
| Domain events | N/A — no data model changes |
| Statistics | N/A — no impact on statistics |
| Moderation | N/A — no moderation impact |
| Lifecycle / cascade | N/A — no cascade behavior |
| Media | N/A — no media handling |
| Search | N/A — no search impact |
| i18n | N/A — purely visual fix |
| Mobile | Should maintain alignment on mobile viewports |
| Accessibility | Alignment does not affect semantic structure or screen readers |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Scope of fix | Fix both author-list and reader-list components |
| 2 | Method | Add vertical centering classes to chapter info containers to match button alignment |

## 8. Out of scope

- Adding new columns or data to the chapter list
- Changing button arrangement or styling
- Modifying chapter list sorting or filtering
- Changes to chapter visibility or access control

## 9. Open questions

None. This is a straightforward visual alignment fix with clear remediation.
