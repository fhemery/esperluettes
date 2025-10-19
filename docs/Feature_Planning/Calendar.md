# Calendar & Activities Feature Specification

## Overview
The Calendar module provides a flexible system for managing time-bound activities and events on the platform. Activities can take various forms (contests, writing challenges, collaborative projects) and integrate with other domains through a plugin-based registry system. The Calendar provides unified visibility and access control while allowing each activity type to implement its own specific logic, data structures, and user interfaces.

## Core Features

### Activity Management

#### Base Activity Fields
- **Name**: Display name of the activity (string, required)
- **Description**: Rich text description explaining the activity, rules, and objectives
- **Image**: A picture to put for the Activity card in the main activity widget
- **Activity Type**: References the registered activity type in the CalendarRegistry
- **Role Restrictions**: Array of roles allowed to participate (e.g., `['user-confirmed']`)
- **Slug**: URL-friendly identifier (unique, includes numeric ID suffix). The slug will be derived from the name directly.

#### Activity Timeline (Date Fields)
- **Preview Start Date** (`preview_starts_at`): When the activity becomes visible in listings (optional, nullable)
- **Activity Start Date** (`active_starts_at`): When users can begin participating (optional, nullable)
- **Activity End Date** (`active_ends_at`): When participation closes (optional, nullable)
- **Archive Date** (`archived_at`): When the activity is removed from active listings (optional, nullable)

**Date Field Rules:**
- All dates are optional and can be filled/updated later
- Dates are used to derive activity state dynamically (no cron jobs)
- Activities without dates remain in "draft" state until dates are configured

#### Activity States (Derived from Dates)
States are computed dynamically based on current timestamp and date fields:

- **Draft**: No preview start date set, or preview start date is in the future
- **Preview**: Current time >= `preview_starts_at` but < `active_starts_at` (or `active_starts_at` is null)
- **Active**: Current time >= `active_starts_at` and < `active_ends_at` (or `active_ends_at` is null)
- **Ended**: Current time >= `active_ends_at` but < `archived_at` (or `archived_at` is null)
- **Archived**: Current time >= `archived_at`

**Visibility by State:**
- **Draft**: Admin only
- **Preview**: Visible to eligible users (based on role restrictions), but participation disabled
- **Active**: Visible and participatable by eligible users
- **Ended**: Visible to all, shows results/final state, participation disabled
- **Archived**: Hidden from main listings, accessible via direct URL with "archived" indicator

#### Activity Configuration
- **Requires Subscription** (`requires_subscription`): Boolean flag indicating whether users must explicitly enroll
  - `false`: Implicit participation (e.g., voting contests, JardiNo where any eligible user can participate)
  - `true`: Explicit enrollment required (future feature, subscription/participant management not implemented yet)
- **Participant Limit** (`max_participants`): Optional maximum number of participants (nullable, only relevant when subscriptions are implemented)

### Activity Registry & Plugin System

#### CalendarRegistry
A registry service that maintains a list of available activity types. Each activity type must implement a defined interface to integrate with the Calendar system.

**Registry Functions:**
- Register activity types with their implementing classes
- Provide list of available types for Filament admin dropdown
- Instantiate activity handlers based on type
- Route rendering requests to activity-specific controllers/views

#### ActivityTypeInterface
Each registered activity type must implement:
```php
interface ActivityTypeInterface
{
    // Human-readable name for admin dropdown
    public function getTypeName(): string;
    
    // Returns the name of the component to pass the activity to for the details page
    public function getMainComponent(): string;
    
    // Register event listeners specific to this activity type
    public function registerListeners(EventBus $eventBus): void;
    
    // Optional: Validate if user can participate
    public function canUserParticipate(User $user, Activity $activity): bool;
}
```

#### Activity-Specific Implementation
Each activity type implements its own:
- **Data Storage**: Uses `activity_id` to store/retrieve activity-specific data in dedicated tables
- **Controllers**: Handle activity-specific pages and interactions
- **Views**: Custom UI for activity participation and results
- **Event Listeners**: Register listeners for domain events they care about (e.g., JardiNo listens to Story word count changes)
- **Business Logic**: Validation, scoring, rewards, progress calculation

**Example Activity Types:**
- `JardiNoActivity`: Writing challenge with word targets and flower garden visualization
- `NominationContestActivity`: Multi-phase contest with nominations and voting
- `DailyChallengeActivity`: Daily prompts/themes for a month-long challenge

### Dashboard Widget

#### Widget Display
A widget appears on the main dashboard showing:
- **Upcoming Activities**: Activities in "preview" state (starting soon)
- **Ongoing Activities**: Activities in "active" state (participation open)
- **Recently Finished**: Activities in "ended" state (recently concluded, showing results)

**Widget Content per Activity:**
- Activity name (linked to activity page)
- Brief description (truncated)
- State indicator (badge: "Preview", "Active", "Ended")
- Time information:
  - Preview: "Starts in X days"
  - Active: "Ends in X days"
  - Ended: "Ended X days ago"
- Activity-specific widget content (via `getWidgetView()`)

**Widget Sorting:**
- Active activities first (sorted by end date, soonest first)
- Preview activities second (sorted by start date, soonest first)
- Ended activities last (sorted by end date, most recent first)
- Limit to 5 total activities in widget

### Admin Management

#### Filament Resource
Admin panel for managing activities with:

**List View:**
- Activity name
- Activity type (from registry)
- Current state (computed badge)
- Role restrictions
- Created date
- Actions: View, Edit, Delete

**Create/Edit Form:**
- Name (text input, required)
- Activity Type (dropdown populated from CalendarRegistry, required)
- Description (rich text editor)
- Role Restrictions (multi-select from available roles: `['user', 'user-confirmed', 'admin']`)
- Requires Subscription (toggle, default: false)
- Max Participants (number input, nullable, only shown if subscription toggle is on)
- **Timeline Section:**
  - Preview Start Date (datetime picker, nullable)
  - Activity Start Date (datetime picker, nullable)
  - Activity End Date (datetime picker, nullable)
  - Archive Date (datetime picker, nullable)
- Slug (auto-generated from name + ID, non-editable)

**Filters:**
- State filter (Draft, Preview, Active, Ended, Archived)
- Activity Type filter
- Role restrictions filter

**Validation:**
- If dates are provided, validate logical order: preview <= active start <= active end <= archive
- Warn if activity has no dates configured

**Permissions:**
- Only admin and tech admin role users can create/edit/delete activities

### Activity Pages

#### Activity Listing Widget
Widget added to the dashboard page showing visible activities (based on user role and activity state):

**Filters:**
- Activity status: Preview, Active, Ended. Default none selected.

**Order:**
If no status filter is selected:
- First ongoing activities (sorted by end date, soonest first)
- Then preview activities (sorted by start date, soonest first)
- Then ended activities (sorted by end date, most recent first)

**Activity Cards:**
- Activity name, with image in the background
- State badge
- Brief description
- Timeline information
- "View Details" button

#### Individual Activity Page (`/calendar/{slug}`)
Rendered by the activity main page, it shows a header with the activity information specified in the card above, and then the main activity component (resolved through registry):

**Access Control:**
- Check user role against activity role restrictions
- 404 for draft activities unless admin

### Cross-Domain Integration

#### Events Emitted by Calendar
```php
// When admin actions occur
ActivityCreated::dispatch($activity, $admin);
ActivityUpdated::dispatch($activity, $admin);
ActivityDeleted::dispatch($activity, $admin);
```

No activity is currently registered when activity changes state, as the badge is computed on the fly.

#### Events Consumed by Activity Types
Activity types register their own listeners for relevant domain events:

## User Stories

### General User Stories
- As a user, I can view all activities I'm eligible for based on my role
- As a user, I can see upcoming, active, and recently ended activities on my dashboard
- As a user, I can view activity details including timeline and rules
- As a user, I can see when activities will start/end
- As a user, I cannot access activities restricted to higher role levels

### Activity Participation Stories (Varies by Type)
- As a JardiNo participant, I can select a story and set a word target
- As a JardiNo participant, I can see my progress update automatically as I write
- As a JardiNo participant, I can earn flowers and place them on my garden map
- As a contest participant, I can nominate stories in various categories
- As a contest participant, I can vote on nominated stories
- As a challenge participant, I can submit daily responses to prompts

### Admin User Stories
- As an admin, I can create new activities with any registered activity type
- As an admin, I can configure activity dates and visibility
- As an admin, I can set role restrictions for activity participation
- As an admin, I can view all activities regardless of state
- As an admin, I can edit activity details (except type after creation)
- As an admin, I can see which activity types are available in the registry

### Activity Type Developer Stories
- As a developer, I can create new activity types by implementing ActivityTypeInterface
- As a developer, I can register my activity type with CalendarRegistry
- As a developer, I can define custom data structures for my activity
- As a developer, I can listen to events from other domains
- As a developer, I can provide custom views for my activity pages

## Non-Functional Requirements (NFRs)

### Performance
- Activity state computation should be efficient (cached or memoized within request)
- Dashboard widget query should be optimized (single query with date filtering)
- Activity listing page should be paginated (20 activities per page)
- Database indexes on all date fields for state filtering
- Activity-specific data queries should use proper indexes on `activity_id`

### Scalability
- Registry pattern allows unlimited activity types without modifying core Calendar code
- Activity-specific tables prevent data bloat in main activities table
- Archived activities remain accessible but don't impact active listing performance

### Maintainability
- Clear separation between Calendar core and activity-specific logic
- Activity types are self-contained with their own models, controllers, views
- Interface contract ensures consistent integration across activity types
- Domain-driven structure keeps Calendar logic isolated

### Usability
- Consistent state badges across all activity displays
- Clear timeline indicators showing activity progression
- Intuitive admin interface for activity management
- Activity-specific UIs can be customized while maintaining overall consistency

### Security
- Role-based access control enforced at query level
- 404 response for unauthorized access (don't leak activity existence)
- Activity-specific data isolated by `activity_id` foreign key
- Admin-only access to activity creation/management

### Extensibility
- New activity types can be added without database migrations to activities table
- Activity types can define their own routes, controllers, and views
- Event system allows loose coupling between domains
- Registry pattern supports runtime discovery of available activity types

### Testability
- State computation logic isolated in ActivityStateService (unit testable)
- Registry can be mocked for testing activity type integration
- Activity types can be tested independently
- Feature tests can verify role-based visibility

## Security Considerations

### Access Control
- Role restrictions validated on every activity access
- Draft activities return 404 for non-admin users
- Archived activities accessible via direct URL only
- Admin panel protected by admin role middleware

### Authorization
- Activity creation/editing restricted to admin role
- Activity type cannot be changed after creation (data integrity)
- Activity-specific authorization handled by activity type implementation

### Data Validation
- Date order validation (preview <= start <= end <= archive)
- Role restrictions must be valid role names from system
- Activity type must exist in CalendarRegistry
- HTML sanitization in rich text description (same purifier config as Story domain)

### Privacy
- Activity participation data managed by activity types
- No public participant lists without activity-specific implementation
- Activity results visibility controlled by activity type

## Performance Considerations

### Query Optimization
- Index all date fields for state computation
- Single query for dashboard widget with date range filtering
- Pagination on activity listing page
- Eager load activity type metadata for listing pages

### State Computation
- States derived on-the-fly from dates (no cron job overhead)
- Memoize state within request to avoid repeated computation
- Consider caching frequently accessed activities

### Activity-Specific Performance
- Each activity type responsible for its own query optimization
- Encourage activity types to use appropriate indexes
- Activity-specific data should not join to activities table unnecessarily

## Clarified Requirements Summary

### ✅ Resolved Decisions
- **Activity Registry**: Plugin-based system with ActivityTypeInterface
- **State Management**: Derived from dates, no cron jobs or status updates
- **Subscription Model**: Flag exists but subscription/participant management deferred
- **Role-Based Access**: Configurable per activity, enforced at query level
- **Admin Management**: Filament resource with registry-based type selection
- **Cross-Domain Integration**: Activity types register their own event listeners
- **Data Storage**: Activity-specific tables using `activity_id` foreign key
- **Visibility**: State-based visibility with role restrictions
- **Dashboard Widget**: Shows upcoming, ongoing, and recently finished activities
- **Rewards**: No central reward system, handled by activity types individually

### 🔄 Deferred Features
- **Subscription Management**: `requires_subscription` flag exists but enrollment UI/logic deferred
- **Participant Limits**: `max_participants` field exists but enforcement deferred
- **Participant Lists**: No public participant viewing until subscription system implemented
- **Activity Notifications**: No email/push notifications for activity state changes initially

## Dependencies

### Required
- User authentication and role system (Auth domain)
- Filament admin panel
- Laravel event system
- User roles: `user`, `user-confirmed`, `admin`

## Example Activity Type Implementations

### JardiNo Writing Challenge
**Description**: Month-long writing challenge where users set word count goals and earn virtual flowers for progress milestones.

**Features:**
- Users select one of their stories to track
- Set custom word count target
- System tracks progress automatically via Story events
- Earn flower for every 5% progress
- Place flowers on interactive garden map (pixel art)
- Reposition/replant flowers freely
- Final garden visualization at end of challenge

**Integration:**
- Listens to: `ChapterPublished`, `ChapterUpdated` (from Story domain)
- Emits: `JardiNoMilestoneReached`, `JardiNoCompleted`

---

**Document Status**: Initial Draft  
**Last Updated**: 2024-10-19  
**Requires Review**: Architecture team approval for registry pattern implementation
