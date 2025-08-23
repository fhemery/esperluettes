# Story Domain Implementation Planning - Phase 3

This phase focuses on implementing the chapter management system for stories.

## **Prerequisites**

Phase 3 requires completion of Phase 1 (US-001 through US-008) and Phase 2 (US-009 through US-021).

---

## **US-030: Add Chapter to Story**

**As a story author, I want to add chapters to my story so that I can organize my content.**

## **US-031: Edit Chapter Content**

**As a story author, I want to edit chapter content with rich text so that I can format my writing.**

## **US-032: Publish/Unpublish Chapters**

**As a story author, I want to control chapter publication status so that I can manage what readers see.**

## **US-033: Read Published Chapters**

**As a reader, I want to read published chapters so that I can enjoy the story.**

## **US-034: Navigate Between Chapters**

**As a reader, I want next/previous navigation so that I can easily move through the story.**

## **US-035: Reorder Chapters with Sparse Ordering**

**As an author/co-author, I want to reorder chapters efficiently so that large stories don't require renumbering
everything.**

**Acceptance Criteria:**

- Drag-and-drop or bulk reorder updates `order` using sparse increments (e.g., 100)
- Reordering does not cause O(n) updates for all rows
- Readers see chapters in the new order immediately

**Implementation:**

- Implement reorder endpoint that assigns spaced order values to minimize churn

## **US-036: Increment Chapter Views Count**

**As an author, I want to see how many times a chapter was read so that I can understand engagement.**

**Acceptance Criteria:**

- When a published chapter page is viewed, `views_count` increments (anonymous + logged)
- Bot traffic mitigations can be added later; simple increment to start

**Implementation:**

- Increment `views_count` on chapter show action; avoid double count per quick refresh is optional for MVP

## **US-037: Manually Mark Chapter as Read (Logged Users)**

**As a logged-in reader, I want to manually mark a chapter as read so that I can track my progress.**

**Acceptance Criteria:**

- A "Mark as read" control is available on chapter pages for logged users
- Creates/updates an entry in `reading_progress (user_id, chapter_id)`
- UI reflects read status

**Implementation:**

- Add endpoint/action to toggle read status; add simple UI control
