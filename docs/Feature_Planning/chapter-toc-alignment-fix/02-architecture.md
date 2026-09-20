# Fix chapter table of contents alignment — architecture

> DESIGN output. Describes **how** the feature is built and **where**.

## 1. Placement

Fix applied in the Story domain's chapter list view templates. No new domain,
no new tables, no new extension points. Purely a Tailwind CSS correction in
existing Blade components.

## 2. Data model

No data model changes. The alignment issue is visual only — the underlying data
and database schemas remain unchanged.

## 3. Components and files affected

| Component | File | Change |
|-----------|------|--------|
| Author chapter list | `app/Domains/Story/Private/Resources/views/chapters/partials/chapter-list/author-list.blade.php` | Add `h-full items-center` classes to chapter info flex container (line ~4) |
| Reader chapter list | `app/Domains/Story/Private/Resources/views/chapters/partials/chapter-list/reader-list.blade.php` | Add `h-full items-center` classes to chapter info flex container (line ~24) |

## 4. Architecture decisions

### 4.1 Method: Add Tailwind centering classes

The chapter info container (`flex flex-col` with chapter name and date) lacks
the vertical centering that the button container has (`h-full flex items-center
justify-center`). The fix adds `h-full` and `items-center` to the chapter info
container to center it vertically within the grid row, matching the button alignment.

### 4.2 Scope: Both author and reader views

Both templates follow the same grid pattern and both have the same misalignment.
Fixing both ensures consistency across all chapter list contexts (author editing
and reader browsing).

## 5. Deptrac and domain edges

No new edges. Changes are internal to the Story domain's view layer only.

## 6. Testing strategy

Visual verification in both contexts:
- Author view with edit/delete buttons
- Reader view with read/comment/statistics buttons

No unit tests needed for CSS changes. Integration tests do not cover layout.

## 7. Tradeoffs recorded

| # | Question | Decision | Rejected option | Reason |
|---|----------|----------|-----------------|--------|
| 1 | Alignment method | Add `h-full items-center` to flex container | Use `items-start` for top alignment | Center alignment matches button container's centering, creating visual consistency |
| 2 | Scope | Fix both author and reader templates | Fix only the most-visible one | Both follow identical patterns; fixing one but not the other creates inconsistency |
| 3 | Implementation | Modify existing Blade templates | Extract to a reusable component | No performance or reusability benefit for a two-line CSS fix |

## 8. Out of scope

- Layout changes to other grid columns
- Button styling or arrangement
- Chapter list sorting, filtering, or data changes
- Mobile-specific layout adjustments (standard grid should work on all viewports)

## 9. Open questions

None. The fix is straightforward: add two Tailwind classes to two containers.
