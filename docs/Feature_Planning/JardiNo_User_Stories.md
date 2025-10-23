# JardiNo Activity - User Stories

## Overview

User stories for the JardiNo collaborative writing challenge. Each story includes a brief description and UI impact summary.

---

## 1. Discovery & Registration

### [DONE] US-01: View JardiNo Activity Information

**As a** confirmed user  
**I want to** view information about the JardiNo activity  
**So that** I understand the rules and decide whether to participate

**UI Impact:** Link to a static explanation page with rules, flower mechanics, and garden concept.

---

### US-02: Create My Writing Goal

**As a** confirmed user without a JardiNo goal  
**I want to** create my writing goal by selecting a story and setting a word count target  
**So that** I can start participating in the challenge

**UI Impact:** Goal creation form with story dropdown (all stories including private) and word count input field.

---

## 2. Dashboard & Progress Tracking

### US-03: View My Progress Dashboard

**As a** JardiNo participant  
**I want to** see my current progress, target, and flower inventory  
**So that** I can track my writing achievements

**UI Impact:** Dashboard displays current story name, target word count, words written, progress percentage, total flowers earned, flowers planted, and flowers available.

---

### US-04: See Real-Time Progress Updates

**As a** JardiNo participant  
**I want to** see my progress update automatically when I save chapters  
**So that** I can immediately see the impact of my writing

**UI Impact:** Dashboard reflects updated word count and progress percentage after chapter saves (may require page refresh initially, polling later).

---

### US-05: Understand Flower Earning Mechanics

**As a** JardiNo participant  
**I want to** understand how many flowers I've earned and why  
**So that** I can see the correlation between my progress and rewards

**UI Impact:** Dashboard shows milestone markers (5%, 10%, etc.) and indicates daily earning limit (e.g., "2 flowers earned today, next available tomorrow").

---

## 3. Goal Management

### US-06: Modify My Target Story

**As a** JardiNo participant  
**I want to** change which story I'm tracking for the challenge  
**So that** I can switch focus while preserving my progress

**UI Impact:** "Modify" button unlocks story dropdown; cumulative progress is preserved and displayed across all stories.

---

### US-07: Adjust My Word Count Target

**As a** JardiNo participant  
**I want to** increase or decrease my target word count  
**So that** I can adapt my goal to my actual writing capacity

**UI Impact:** "Modify" button unlocks target word count field; progress percentage recalculates based on new target.

---

### US-08: Handle Deleted Target Story

**As a** JardiNo participant whose target story was deleted  
**I want to** be prompted to select a new story  
**So that** I can resume earning flowers

**UI Impact:** Dashboard shows warning message "Your target story was deleted. Progress is paused until you select a new story." The user can still view the garden and can plant/unplant any available flowers. Selecting a new story resumes progress accumulation.

---

## 4. Garden Viewing

### US-09: View the Collaborative Garden

**As a** JardiNo participant  
**I want to** view the shared garden grid with all planted flowers  
**So that** I can see the collective artwork being created

**UI Impact:** Garden page displays grid (60x60) with planted flowers as small images, blocked cells in dark color, empty cells with transparent dots, and coordinate labels (X, Y).

---

### US-10: Identify Flower Owners

**As a** JardiNo participant  
**I want to** see who planted each flower  
**So that** I can coordinate with others on Discord for artistic patterns

**UI Impact:** Hovering over a flower displays tooltip with owner's display name (from Profile domain).

---

### US-11: Identify My Own Flowers

**As a** JardiNo participant  
**I want to** easily spot my planted flowers on the grid  
**So that** I can see my contribution to the garden

**UI Impact:** User's own flowers are highlighted with a golden border and subtle glow effect.

---

### US-12: Toggle Coordinate Display

**As a** JardiNo participant  
**I want to** show/hide cell coordinates on the garden grid  
**So that** I can communicate positions to others without visual clutter

**UI Impact:** Toggle button shows/hides small coordinate labels (X,Y) in each cell corner.

---

## 5. Flower Planting

### US-13: Plant a Flower in Empty Cell

**As a** JardiNo participant with available flowers  
**I want to** plant a flower in an empty cell  
**So that** I can contribute to the garden artwork

**UI Impact:** Clicking empty cell opens modal with 28 flower thumbnails; selecting one plants it immediately and updates available flower count.

---

### US-14: Browse Flower Library

**As a** JardiNo participant  
**I want to** see all available flower types before selecting  
**So that** I can choose the one that fits the pattern I want to create

**UI Impact:** Modal displays 28 flower thumbnails in a grid layout for easy browsing.

---

### US-15: Receive Feedback on Planting Actions

**As a** JardiNo participant  
**I want to** get immediate feedback when I plant a flower  
**So that** I know the action succeeded

**UI Impact:** Flower appears instantly in the selected cell, modal closes, and available flower count decreases by 1.

---

### US-16: Cannot Plant Without Available Flowers

**As a** JardiNo participant with zero available flowers  
**I want to** be prevented from planting  
**So that** I don't attempt invalid actions

**UI Impact:** Clicking empty cells shows message "No flowers available. Keep writing to earn more!"

---

### US-17: Cannot Plant in Occupied or Blocked Cells

**As a** JardiNo participant  
**I want to** be prevented from planting in unavailable cells  
**So that** I don't waste time on invalid actions

**UI Impact:** Occupied and blocked cells have "not-allowed" cursor; clicking them does nothing or shows brief message.

---

## 6. Flower Management

### US-18: Remove My Own Flower

**As a** JardiNo participant  
**I want to** remove a flower I previously planted  
**So that** I can replant it in a better position

**UI Impact:** Clicking own flower shows "Remove" button/confirmation; removal makes cell empty and increases available flower count by 1.

---

### US-19: Replant Removed Flower

**As a** JardiNo participant who removed a flower  
**I want to** plant it again (potentially different type) in another cell  
**So that** I can improve the garden composition

**UI Impact:** After removal, user follows normal planting flow (US-13) to place flower elsewhere, with option to choose a different flower type.

---

### US-20: Cannot Remove Other Users' Flowers

**As a** JardiNo participant  
**I want to** be prevented from removing flowers planted by others  
**So that** everyone's contributions are protected

**UI Impact:** Clicking other users' flowers does nothing or shows tooltip with owner name only; no remove action available.

---

## 7. Edge Cases & Special States

### US-21: View Garden When Activity Has Ended

**As a** JardiNo participant after the event ends  
**I want to** view the completed garden artwork  
**So that** I can appreciate the final result

**UI Impact:** Garden remains accessible in read-only mode; all planting/removing actions are disabled; dashboard shows final stats.

---

### US-22: Cannot Access JardiNo Without Confirmation Role

**As a** user  
**I want to** have access controlled by the activity's configured rules  
**So that** visibility and participation follow the event settings

**UI Impact:** Access to the garden and participation (goal creation/updates) are allowed or denied based on the Calendar activity configuration (e.g., may require confirmed role). If not allowed, pages return 403 or redirect with an appropriate message.

---

### US-23: Access JardiNo from Calendar

**As a** confirmed user  
**I want to** access JardiNo through the Calendar activity list  
**So that** I can find it easily among other events

**UI Impact:** Calendar activity page shows JardiNo with icon, dates, and "Participate" button linking to dashboard or goal creation form.

---

### US-24: View Empty Garden at Activity Start

**As a** JardiNo participant at the beginning of the event  
**I want to** see an empty garden grid  
**So that** I understand the canvas we're collectively painting

**UI Impact:** Garden displays grid with only blocked cells and empty cell indicators; no flowers planted yet.

---

### US-25: Understand Daily Earning Cap (No Queue)

**As a** JardiNo participant who writes a lot in one day  
**I want to** understand why I didn't get all my eligible flowers immediately  
**So that** I know more flowers will become available as days pass

**UI Impact:** Dashboard shows message like "Daily cap reached (2 flowers/day). Your progress exceeds today's cap; additional flowers will become available on subsequent days." No queue is displayed.

---

### US-26: See Available Flowers Increase As Days Pass

**As a** JardiNo participant who exceeded today's cap  
**I want to** see my available flower count increase on subsequent days  
**So that** I benefit from my prior progress without any manual action

**UI Impact:** Dashboard updates available flower count automatically when new days begin; no user action required. No queue is shown.

---

## 8. Administrative Setup (Out of User Scope)

These stories are for admin/developer reference, not end-user facing:

### US-27: Seed Blocked Garden Cells

**As an** administrator  
**I want to** define blocked cells in the garden grid via database  
**So that** specific patterns or borders can be reserved

**UI Impact:** N/A (database seeding/manual SQL)

---

### US-28: Configure Activity Dates and Rules

**As an** administrator  
**I want to** create a JardiNo activity with start/end dates and role restrictions  
**So that** the event runs within defined parameters

**UI Impact:** Standard Calendar Activity configuration in admin panel (already exists).

---

## Summary

**Total User Stories:** 28  
**Core User Flows:** 7 (Discovery, Dashboard, Goal Management, Garden Viewing, Planting, Flower Management, Edge Cases)  
**Admin Stories:** 2

All stories focus on creating a low-pressure, collaborative writing experience where users contribute to a shared artistic garden while pursuing personal writing goals.
