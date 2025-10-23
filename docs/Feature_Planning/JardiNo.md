# JardiNo Activity - Functional Specification

## Overview

**JardiNo** is a collaborative writing challenge inspired by Nanowrimo, adapted for the French writing community "Les Esperluettes". It transforms individual writing goals into a collective artistic creation: a pixelated garden where participants plant virtual flowers as they achieve milestones.

### Core Concept

- **Individual Goals:** Each participant sets their own word count target (no pressure for 50k words)
- **Milestone Rewards:** Writers earn flowers for every 5% of their target achieved
- **Collaborative Art:** All earned flowers are planted on a shared garden grid, creating a community artwork
- **Low Pressure:** Flexible targets, flexible story choices, and cumulative progress encourage participation over perfection

---

## Event Structure

### Recurring Activity

- JardiNo is a **yearly recurring event** (typically in November)
- Each instance is a separate Calendar Activity with its own configuration
- Future iterations may reuse the same garden (expanding it) or create new gardens (to be decided)

### Timing & Registration

- **Event Dates:** Configured via the Calendar Activity system (start/end dates, timezone)
- **Registration Period:** Users can register anytime during the active event period
- **Access:** Registration happens on the JardiNo activity page
- **Eligibility:** Only users with `user-confirmed` role can participate (defined at Activity level)

---

## Participant Setup

### Initial Registration

When a user joins the JardiNo event, they must configure:

1. **Target Story:** Select one story from their collection (published or private)
2. **Word Count Target:** Set their personal goal (any positive number)

### Story & Target Selection UI

- After initial selection, the story dropdown and word count field become **read-only**
- An explicit **"Modify" button** allows users to edit their choices
- Users can change their target story or word count **unlimited times** during the event

### Story Change Behavior

When a user changes their target story:

- **Progress is preserved and cumulative** across all stories
- The system tracks: `total_words_written = sum_of_all_stories(current_word_count - initial_word_count)`
- **Example:**
  - Story A: starts at 1000 words, writes 300 → contributes 300 words
  - Changes to Story B: starts at 2000 words, writes 100 → contributes 100 words
  - **Total progress:** 400 words toward target
- Note: User could go back to Story A again.

### Story Deletion Edge Case

- If a user **deletes their target story** mid-event:
  - Their goal is **stalled** (no further progress possible)
  - Progress is retained (as it was a word different stored)
  - Previously earned flowers are **retained**
  - User must select a **new target story** to resume progress

---

## Word Count Tracking

### Baseline Measurement

- **Baseline:** Total word count of the story at the moment it becomes the target
- **Progress:** Current story word count minus baseline word count
- **Cumulative:** Progress from all stories (past and current) adds up toward the single target

### Tracking Rules

- **Chapter Updates:** Every save/update to any chapter triggers word count recalculation
- **Private Chapters:** Include in word count (users can track private work)
- **Old Chapters:** Count all chapters, regardless of creation date
- **Word Count Decreases:** Do NOT reduce earned flowers (progress can only increase or stay the same)

### Implementation Note (Event-Driven)

- Story domain emits word count change events
- JardiNo listens to these events and updates progress
- **Story domain has no knowledge of JardiNo**

---

## Flower Earning Mechanics

### Earning Thresholds

- Flowers are earned at **5% increments** of the target word count
- **Milestones:** 5%, 10%, 15%, 20%, 25%, 30%, 35%, 40%, 45%, 50%, 55%, 60%, 65%, 70%, 75%, 80%, 85%, 90%, 95%, 100%, 105%, 110%, 115%, 120%, 125%
- **Maximum:** 25 flowers total (at 125% of target)

### Daily Limit Rule

- Users can earn **maximum 2 flowers per day**
- If a user crosses multiple thresholds in one day, excess flowers **queue up** for future days
- Queued flowers are distributed automatically on subsequent days (up to 2 flowers per day until queue is cleared)

### Award Timing

- Flowers are awarded when a chapter's word count changes
- Award happens via **event-driven system** (JardiNo listens to Story domain events)
- Award is immediate but respects daily limit (excess queued)

### Example Scenario

**Day 1:**
- User writes 1000 words, crossing 5%, 10%, 15%, 20%, 25% thresholds (5 flowers eligible)
- System awards **2 flowers** immediately
- **3 flowers queued** for future days

**Day 2:**
- User writes nothing
- System awards **2 flowers from queue** automatically
- **1 flower remains queued**

**Day 3:**
- User writes 500 words, crossing 30%, 35% thresholds (2 more flowers eligible)
- System awards **1 flower from queue + 2 new flowers = 3 flowers**
- But daily limit is 2, so user gets **2 flowers**
- **1 flower queued**

---

## Garden Board

### Grid Structure

- **Size:** 50x50 or 75x75 cells (exact size to be finalized during implementation)
- **Cell Occupancy:** One flower per cell maximum
- **Coordinate System:** X (horizontal) and Y (vertical) coordinates for cell identification
  - Displayed to allow users to communicate positions (e.g., "X:25, Y:10")
- **Blocked Cells:** Some cells are reserved/blocked by administrators (via database configuration)

### Visual Elements

- **Empty Cells:** Transparent black dot indicator
- **Blocked Cells:** Visually distinct (different background/pattern)
- **Planted Flowers:** Display the selected flower image
- **User's Own Flowers:** Highlighted with a colored border

### Flower Library

- **28 flower images** pre-packaged with the system
- Naming convention: `01.png` to `28.png`
- No categorization or filtering (users browse all 28 options)

### Visibility

- **Who Can View:** Only users with `user-confirmed` role (Activity-level restriction)
- **Real-time Updates:** Static page initially; polling may be added later
- **Owner Display:** Hovering over a flower shows the owner's **display name** (from Profile domain)

---

## Flower Planting Workflow

### Planting a Flower

**User Action:**

1. Click on an **empty cell** on the garden grid
2. A popup/modal displays thumbnails of **all 28 flower types**
3. User selects one flower
4. Flower is **immediately planted** in the selected cell
5. Flower count decreases by 1

**Rules:**

- Only **available flowers** (earned but not yet planted) can be used
- Users can plant multiple flowers in one session (repeat process for each flower)
- **First-come-first-served:** Once a cell is occupied, no one else can use it

### Moving a Flower

**User Action:**

1. Click on **one of their own flowers** (anywhere on the grid)
2. A "Remove" button appears
3. Click "Remove" → flower is removed from the grid
4. Available flower count increases by 1
5. User can now plant it elsewhere (follow planting workflow above)

**Rules:**

- Users can **only remove their own flowers**
- No limit on number of moves
- When replanting, user can **choose a different flower type**
- Moving does not count against daily earning limit

---

## User Dashboard

### Information Displayed

Participants see a personal progress dashboard showing:

1. **Target Configuration:**
   - Current target story name
   - Word count target
   - "Modify" button (to change story or target)

2. **Progress Tracking:**
   - Current word count written (cumulative across all stories)
   - Progress percentage (e.g., "42% of target")

3. **Flower Inventory:**
   - Total flowers earned
   - Flowers planted
   - Flowers available (earned - planted)

4. **Garden Access:**
   - Link/button to view the garden board
   - Visual indication of their own flowers on the grid (highlighted)

---

## Edge Cases & Special Situations

### Garden Full

- Extremely unlikely given grid size (2,500+ cells for 50x50 grid)
- If it happens: users with unplanted flowers simply cannot plant them
- No special mechanism needed

### Account Deletion

- If a user deletes their account mid-event:
  - Their planted flowers are **removed from the garden** (cells become empty)
  - This is an unlikely scenario (no strong motivation to delete mid-challenge)

### Target Change Impact

- Users can freely adjust their word count target up or down
- **Flowers are never lost** even if target changes make progress percentage decrease
- Already-earned flowers remain available for planting

### Story Word Count Decrease

- If editing reduces a chapter's word count:
  - Progress calculation may decrease
  - **Flowers already earned are never lost**
  - User keeps all flowers (earned and planted)

---

## Post-Event Behavior

### After Event Ends

- **Flower Planting:** Disabled (users can no longer plant or move flowers)
- **Garden Viewing:** Remains open for viewing for some time (duration TBD)
- **Archival:** Once activity is archived, users cannot access it anymore
- **Future Iteration:** Garden may be preserved, expanded, or reset (to be decided)

### No Notifications

- The website does not have a notification system yet
- Users must check the garden and dashboard manually for updates
- Communication about garden decoration happens on Discord

---

## Administrative Controls

### Administrator Capabilities

- **Activity Configuration:** Standard Calendar Activity settings (dates, roles, etc.)
- **Blocked Cells:** Define non-plantable zones via direct database manipulation

### Administrator Restrictions

- Admins **cannot** manually adjust flower counts
- Admins **cannot** remove or move user flowers
- All progress is system-calculated based on word count tracking

---

## Summary

JardiNo transforms the individual challenge of writing into a collective art project. By focusing on personal goals, reducing pressure, and encouraging collaboration through the shared garden, it fosters community engagement while celebrating each writer's unique contribution. The flower-earning mechanics ensure consistent participation, while the flexible story and target selection accommodate different writing styles and schedules.
