# Story Domain Implementation Planning

## User Story-Based Feature Slices

This document breaks down the Story domain implementation into atomic user stories with associated feature tests. Each
user story is:

- **A single, testable user capability**
- **Independently valuable**
- **Small enough to implement and validate quickly**
- **Backed by comprehensive feature tests**

This file has been broken down into phases to keep track of the status :

- [Phase 1 : Basic Story Management](./Story_Implementation_Planning_Phase1.md)
- [Phase 2 : Story Details Management](./Story_Implementation_Planning_Phase2.md)
- [Phase 3 : Chapter Management](./Story_Implementation_Planning_Phase3.md)
- [Phase 4 : Story Publishing Management](./Story_Implementation_Planning_Phase4.md)

## Implementation Strategy

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

### **User Story Development Process**

1. **Write Feature Test**: Start with the test that defines the behavior
2. **Implement Minimum Code**: Write just enough code to make the test pass
3. **Refactor**: Clean up code while keeping tests green
4. **Manual Validation**: Test the feature in browser
5. **Move to Next Story**: Only after current story is complete

### **Benefits of User Story Approach**

- **User-Focused**: Each story delivers value to a specific user type
- **Testable**: Clear acceptance criteria translate to feature tests
- **Incremental**: Each story builds on previous functionality
- **Demonstrable**: Stakeholders can see and validate each capability
- **Flexible**: Stories can be reordered based on priorities
- **Traceable**: Easy to track which features are complete

## Future: Comments Annotations (V2)

- **Composition model**: Story composes its own tabs (e.g., "General" and "Annotations") around the headless Comment provider (`comment::provider`). The Comment domain ships only primitives (`provider`, `list`, `editor`) and no tabs.
- **General tab**: Uses `comment::list` and `comment::editor` to render and post general comments.
- **Annotations tab (Story-owned)**:
  - Implemented in Story as a separate panel (e.g., `app/Domains/Story/Resources/views/components/annotations-panel.blade.php`).
  - Fetches from Story endpoints (e.g., `GET /stories/{story}/chapters/{chapter}/annotations[?commentId=...]`).
  - Shows annotation items with excerpt, linked `comment_html`, and actions like "Jump to location".
- **Per-comment hook**: In the General tab, Story may inject an "Annotations (n)" pill via the `meta` slot of `comment::list` to switch to the Annotations tab filtered to that comment.
- **Inline marks**: Controlled by Story; visible only to authors/co-authors on the reading page. Readers can open the Annotations tab without inline marks.
- **Contracts/Adapters**: Story implements Comment target adapters indicating `supportsAnnotations = true` without changing the Comment core.
- **Testing**: Add feature tests for tabs visibility, permissions, annotation listing, and the per-comment pill behavior. Keep browser tests minimal (tab switching, anchor jump).
