# Moderation Feature Specification

## Document Status
**Status**: ✅ Planning Complete - Ready for Implementation  
**Last Updated**: 2025-10-14  

### Decisions Made ✅
- **Access**: Email-verified users only (roles `user` and `user-confirmed`)
- **Rate Limiting**: None (manual intervention for abuse)
- **Duplicate Reports**: Allowed (users can report same entity multiple times)
- **Reasons per Report**: Single reason only
- **Reason Management**: Admin-configured through Filament (sortable, can be deactivated)
- **Moderator Actions**: Approve/Reject only (for tracking); no automated content actions
- **Notifications**: Manual via messaging system (no automation)
- **Reporter Identity**: Visible to moderators, hidden from content owners
- **Review Comments**: Yes, moderators can add internal notes
- **Report History**: Users cannot see their reports; moderators see all
- **Content Actions**: Each domain handles separately (not centralized in Moderation)
- **Audit Trail**: Permanent record (no soft delete)

### Architecture Decisions ✅
- **Topic Registration**: Service provider boot() → ModerationRegistry (no database table)
- **Snapshot Formatter**: Single interface, domains implement capture + render + getReportedUserId
- **Reported User ID**: Via formatter's `getReportedUserId()` method
- **Callbacks/Events**: No callbacks initially; Laravel Events planned for future
- **Report Button**: Tertiary button, lazy-loaded form via Ajax
- **Submission**: Ajax/JSON endpoint with success message
- **Reason Caching**: Yes, cache active reasons per topic
- **Snapshot Storage**: JSON field, no size limit
- **Filament Resources**: Located in Admin domain (`Admin/Filament/Resources/Moderation/`)

## Overview
The Moderation feature enables the community to report inappropriate or harmful content across the platform, and provides administrators with tools to review and act upon these reports. The system is designed to be extensible, allowing different domains to register themselves as reportable topics with custom reporting reasons.

## Core Features

### Reporting System

#### Topics (Reportable Entity Types)
- Topics are registered dynamically by domains via service providers (no database table)
- Each domain registers with `ModerationRegistry::register()` in its service provider
- Initial topics: `profile`, `story`, `chapter`, `comment`
- Registration includes: topic key, display name, snapshot formatter class (optional)
- Admins configure reasons per topic through Filament

#### Reporting Reasons
- **Admin-configured**: Admins configure all reasons through Filament (no domain defaults)
- **"Other" Reason**: Configured by admin like any other reason (not a special case)
- **Sortable**: Reasons have a sort order controlled by admins
- **Deactivation**: Reasons can be marked inactive (hidden from users but preserved for historical reports)
- Examples per topic:
  - **Profile reasons**: "Shocking image", "AI generated image", "Incorrect bio", "Bad network URLs"
  - **Story reasons**: "Shocking cover image", "Harmful summary", "Misleading metadata"
  - **Chapter reasons**: "AI generated content", "Harmful content", "Plagiarism"
  - **Comment reasons**: "Harassment", "Spam", "Off-topic", "AI generated"

#### Report Submission
- **Access Control**: Email-verified users only (roles `user` and `user-confirmed`)
- **Rate Limiting**: None. Manual intervention if abuse is detected.
- **Duplicate Reports**: Users can report the same entity multiple times (no restrictions or warnings)
- **Report Component**: Moderation domain provides a reusable Blade component
  - Takes topic type and entity ID as parameters
  - Fetches active reasons for the topic
  - Displays reporting form (likely in a modal/popup)
- **Report Data**:
  - Reporter user ID (required - verified email only)
  - Topic type and entity ID
  - Selected reason (single reason per report)
  - Optional text description/comment
  - Snapshot of reported content (preserved even if original is modified)
    - **Format**: Domain-specific (each domain defines snapshot structure)
    - **Rendering**: Domains provide formatting classes during registration
  - URL to the problematic page
  - Timestamp

### Admin Moderation Interface

#### Report Queue
- **Filament Integration**: Admin panel resource for managing reports
- **List View**:
  - All reports with status (pending, confirmed, dismissed)
  - **Filterable by**: Topic, Topic ID, Reported user ID, Reporter user ID, Status, Date range
  - **Searchable by**: Topic, Topic ID, Reported user ID, Reporter user ID
  - Sort by date, status
  - Display key fields in table: Topic, Entity, Reporter, Reported User, Reason, Status, Date
- **Detail View**:
  - Full report details
  - Snapshot of reported content (rendered via domain-provided formatter)
  - Link to live content
  - Reporter identity (visible to moderators)
  - Reported user identity (content owner)
  - Moderator review comment
  - [QUESTION: Display list of other reports on same entity?]

#### Moderation Actions
- **Review Actions** (tracking purposes only):
  - **Approve**: Report is valid (status: confirmed)
  - **Reject**: Report is invalid/unfounded (status: dismissed)
  - Status remains permanently for audit trail
- **Moderator Review Comment**: Text field for internal notes/explanation
- **Content Actions**: 
  - Moderation domain does **not** take actions on content
  - Each domain handles its own moderation actions separately
  - Moderators use domain-specific admin tools to act on content
- **Notifications**: 
  - Manual via messaging system
  - Moderators notify reporter/content owner if needed
  - No automated notifications

### Domain Integration

#### Registration System

**ModerationRegistry** (public service) manages topic registration without database storage.

**How it works:**
1. Domains register topics in their service provider's `boot()` method
2. Registry stores configurations in memory (no database table needed)
3. Registry validates formatter classes implement `SnapshotFormatterInterface`
4. Formatters are optional (nullable) for topics that don't need snapshots

**Registration example:**
```php
// In ProfileServiceProvider::boot()
app(ModerationRegistry::class)->register(
    key: 'profile',
    displayName: 'profile::moderation.topic_name', // translatable
    formatterClass: ProfileSnapshotFormatter::class, // nullable
);
```

**Benefits:**
- No database queries to fetch topics
- Topics defined alongside domain code
- Easy to add new topics (just register in service provider)
- Type-safe with interface validation

#### Report Button Component
- Reusable component provided by Moderation domain
- Usage: `<x-moderation::report-button :topic="'story'" :entity-id="$story->id" />`
- Styled as tertiary button
- No indication if user already reported (users can report multiple times)

## Technical Specifications

### Database Schema

#### Reasons Table
```sql
moderation_reasons:
- id (primary key)
- topic_key (string, indexed) -- e.g., 'profile', 'story', 'chapter', 'comment'
- label (string) -- translatable key or plain text
- sort_order (integer)
- is_active (boolean, default true) -- when false, hidden from users but preserved for historical reports
- created_at (timestamp)
- updated_at (timestamp)

Indexes:
- (topic_key, is_active) -- fetch active reasons for a topic
- (topic_key, sort_order) -- sorted reasons display
```

#### Reports Table
```sql
moderation_reports:
- id (primary key)
- topic_key (string, indexed) -- e.g., 'profile', 'story'
- entity_id (unsigned bigint) -- ID of the reported entity
- reported_user_id (foreign key to users) -- content owner (from formatter->getReportedUserId())
- reported_by_user_id (foreign key to users, NOT NULL) -- reporter (verified email required)
- reason_id (foreign key to moderation_reasons)
- description (text, nullable) -- additional details from reporter
- content_snapshot (json, nullable) -- Domain-specific snapshot as JSON
- content_url (string) -- URL to the reported content
- status (enum: pending, confirmed, dismissed, default: pending)
- reviewed_by_user_id (foreign key to users, nullable)
- reviewed_at (timestamp, nullable)
- review_comment (text, nullable) -- moderator's internal notes
- created_at (timestamp)
- updated_at (timestamp)

Indexes:
- (topic_key, entity_id) -- find reports for specific entity
- (reported_user_id) -- filter by content owner
- (reported_by_user_id) -- filter by reporter
- (status, created_at) -- pending reports queue
```

### Domain Structure
```
app/Domains/Moderation/
├── Public/
│   ├── Controllers/
│   │   └── ReportController.php
│   ├── Requests/
│   │   └── SubmitReportRequest.php
│   ├── Services/
│   │   └── ModerationRegistry.php -- registers topics from service providers
│   └── Contracts/
│       └── SnapshotFormatterInterface.php
├── Private/
│   ├── Services/
│   │   └── ModerationService.php -- core business logic
│   └── Resources/
│       └── views/
│           └── components/
│               └── report-button.blade.php
├── Models/
│   ├── ModerationReason.php
│   └── ModerationReport.php
├── Providers/
│   └── ModerationServiceProvider.php
└── Database/
    └── migrations/

app/Domains/Admin/Filament/Resources/Moderation/
├── ModerationReasonResource.php
└── ModerationReportResource.php
```

### URL Structure
- `POST /moderation/report` - Submit a report (authenticated, CSRF protected, JSON response)
- `GET /moderation/report-form/{topic}/{entityId}` - Load report form via Ajax (returns reasons + form HTML)
- `/admin/moderation/reasons` - Manage reporting reasons (Filament)
- `/admin/moderation/reports` - Review reports (Filament)

## User Stories

### User Stories
- As a verified user, I can report inappropriate content across the platform
- As a user, I can select from admin-configured reasons for each content type
- As a user, I can provide additional description with my report
- As a user, I can report the same content multiple times if needed
- As a user, I receive a confirmation message after submitting a report
- As a user, I cannot see my report history or other users' reports

### Admin/Moderator Stories
- As an admin, I can configure reporting reasons for each topic (content type)
- As an admin, I can set the sort order of reasons
- As an admin, I can activate/deactivate reporting reasons without losing historical data
- As a moderator, I can view all reports (pending, confirmed, dismissed) in a centralized queue
- As a moderator, I can filter reports by topic, entity, reporter, reported user, and status
- As a moderator, I can search reports by various criteria
- As a moderator, I can review report details including domain-formatted content snapshot
- As a moderator, I can see the reporter's identity and the content owner's identity
- As a moderator, I can approve or reject reports with a review comment
- As a moderator, I take separate actions on content using domain-specific admin tools
- As a moderator, I can manually notify parties using the messaging system if needed

### Domain Developer Stories
- As a developer, I can seed my domain as a reportable topic
- As a developer, I can provide a snapshot formatter class for my content
- As a developer, I can easily integrate the report button into my views
- As a developer, I can [QUESTION: register callbacks for report lifecycle events?]

## Security Considerations
- **Authentication**: Email-verified users only (roles `user` and `user-confirmed`)
- **Rate Limiting**: None initially; manual intervention if abuse detected
- **Anti-Manipulation**: None initially; community trust model
- **XSS Protection**: Sanitize report descriptions and review comments
- **Authorization**: 
  - Only moderators/admins can view reports
  - Only moderators/admins can approve/reject reports
  - Reporter identity visible to moderators but hidden from content owners
- **CSRF Protection**: Required for report submission
- **Audit Trail**: All report status changes tracked with timestamps and reviewer ID
- **Content Owner Privacy**: Content owners do not see reports (handled manually by moderators)

## Performance Considerations
- **Database Indexes**: See Reports Table schema above (composite indexes on filtering columns)
- **Caching**: Active reasons cached per topic (invalidated on reason add/update/deactivate)
- **Pagination**: Filament handles report listing pagination
- **Archival**: Reports kept permanently for audit trail (soft delete not needed)
- **Snapshot Storage**: JSON field, no size limit (domains responsible for reasonable snapshot size)
- **Lazy Loading**: Report forms loaded on-demand via Ajax to minimize page weight

## Snapshot Formatter Interface

Domains implement `SnapshotFormatterInterface` to provide snapshot capture and rendering:

```php
interface SnapshotFormatterInterface {
    public function capture(int $entityId): array;
    public function render(array $snapshot): string;
    public function getReportedUserId(int $entityId): int;
}

// Example: Story domain implementation
class StorySnapshotFormatter implements SnapshotFormatterInterface {
    public function capture(int $entityId): array {
        $story = Story::findOrFail($entityId);
        return [
            'title' => $story->title,
            'description' => $story->description,
            'cover_url' => $story->cover_image_path,
        ];
    }
    
    public function render(array $snapshot): string {
        return view('story::moderation.snapshot', $snapshot)->render();
    }
    
    public function getReportedUserId(int $entityId): int {
        return Story::findOrFail($entityId)->created_by_user_id;
    }
}
```

## Component Design: Lazy-Loaded Report Button

**Challenge**: Pages with many reportable items need lightweight buttons but comprehensive test coverage.

**Solution**: Lazy-load forms via Ajax; test business logic via JSON endpoints.

### Component Usage
```blade
{{-- Report button component: lightweight, loads form on-demand --}}
<x-moderation::report-button 
    :topic="'comment'" 
    :entity-id="$comment->id" 
    :data-test-id="'report-comment-' . $comment->id" />
```

#### Component Structure
```
1. Initial Load (Minimal HTML)
   - Tertiary button with icon/text
   - data-topic and data-entity-id attributes
   - Alpine.js: x-data="reportButton" (shared Alpine component)
   - No form markup initially

2. On Click (Ajax)
   - Fetch: GET /moderation/report-form/{topic}/{entityId}
   - Returns: Cached HTML with reasons list + form fields
   - Inject into modal/popover
   - Show form

3. On Submit (Ajax)
   - POST /moderation/report (JSON)
   - Show success message
   - Close modal
```

#### Testing Strategy

**Test via JSON endpoints directly**
```php
// Feature test - bypasses UI complexity
$response = $this->actingAs($verifiedUser)
    ->postJson('/moderation/report', [
        'topic' => 'comment',
        'entity_id' => $comment->id,
        'reason_id' => $reason->id,
        'description' => 'Test report',
    ]);

$response->assertJson(['success' => true]);
$this->assertDatabaseHas('moderation_reports', [...]);
```

**Test form loading separately**
```php
// Test the form endpoint
$response = $this->actingAs($verifiedUser)
    ->get("/moderation/report-form/comment/{$comment->id}");

$response->assertOk()
    ->assertViewHas('reasons')
    ->assertViewHas('topic', 'comment');
```

#### Benefits
- ✅ **Lightweight**: Only button HTML on initial load (minimal per-comment cost)
- ✅ **Testable**: Can test endpoints independently without JavaScript
- ✅ **Cacheable**: Form HTML cached per topic (shared across entity instances)
- ✅ **Progressive**: Works without JS if needed (graceful degradation optional)
- ✅ **Fast**: Alpine.js handles interaction, no framework overhead

#### Implementation Notes
- **Shared Alpine Component**: `reportButton` component handles click → fetch → modal
- **Form Caching**: Cache form HTML per topic (reasons rarely change)
- **CSRF Token**: Included in fetched form or passed from page meta
- **Loading State**: Button shows spinner while fetching form
- **Error Handling**: Network failures show error message, retry option

## Future Enhancements
- Automated AI-based content detection (pre-moderation)
- Community voting on reports (crowdsourced moderation)
- Reputation system for reporters (reward accurate reports)
- Pattern detection for repeat offenders
- Batch moderation actions
- Moderation analytics and insights
- Public transparency reports
- Appeals system for moderation decisions
- Integration with user reputation/trust scores

## Dependencies
- User authentication and role system (email verification)
- Filament admin panel
- Messaging system (for manual moderator notifications)
- Cache system (for caching active reasons per topic)
- Alpine.js (for interactive report button component)
- Each reportable domain must:
  - Implement `SnapshotFormatterInterface`
  - Integrate the report button component in views
  - Create snapshot rendering Blade view

## Implementation Summary

### Key Architectural Principles
1. **Decoupled Design**: Moderation domain provides infrastructure; domains implement formatters
2. **Lazy Loading**: Report forms loaded on-demand to minimize page weight
3. **Testability First**: JSON endpoints allow testing without JavaScript complexity
4. **Progressive Enhancement**: Core functionality via Ajax, testable via API
5. **Audit Trail**: All actions tracked permanently for accountability
6. **Manual Control**: No automated actions initially; moderators drive all decisions

### Component Interactions
```
User clicks report button
  → Alpine.js fetches form (GET /moderation/report-form/{topic}/{id})
  → User fills form
  → Ajax submits (POST /moderation/report)
  → ModerationService calls SnapshotFormatter->capture()
  → Snapshot stored as JSON
  → Success message shown

Moderator reviews in Filament
  → Snapshot rendered via SnapshotFormatter->render()
  → Moderator approves/rejects with comment
  → [Future] Events fired for automation
  → Manual actions taken in domain-specific admin tools
```

### Testing Strategy
- **Unit Tests**: SnapshotFormatter classes
- **Feature Tests**: JSON endpoints (`/moderation/report`, `/moderation/report-form/{topic}/{id}`)
- **Integration Tests**: Filament resource CRUD operations
- **E2E Tests**: (Optional) Critical UX flows with Dusk

### Next Steps
1. ✅ **Planning Complete** - Document approved
2. Create migrations (reasons, reports tables)
3. Implement `ModerationRegistry` service (public) with `SnapshotFormatterInterface`
4. Register every topic that requires moderation in service providers, with nullable `SnapshotFormatterInterface` (no implementation yet)
5. Create Filament resources in Admin domain (reasons, reports)
6. Build `ModerationService` (report creation, approval, rejection)
7. Implement caching for reasons per topic, including killing the cache every time Admin change reasons
8. Build report-button component (Alpine.js + Ajax)
9. Create domain integration guide with example
10. Implement first topic (Profile) as reference implementation

---

## Summary

**Key Architecture:**
- **No Topics Table**: Topics registered via service providers → `ModerationRegistry` (in-memory)
- **Two Tables Only**: `moderation_reasons` (topic_key), `moderation_reports` (topic_key)  
- **Snapshot Formatter Interface**: Domains implement capture + render + getReportedUserId
- **Filament in Admin Domain**: `Admin/Filament/Resources/Moderation/`
- **Lazy-Loaded Forms**: Ajax endpoints for testability + performance
- **Manual Moderation**: No automated actions; moderators use domain-specific tools

**Integration Requirements:**
```php
// 1. Register topic in service provider boot()
app(ModerationRegistry::class)->register(
    key: 'profile',
    displayName: 'profile::moderation.topic_name',
    formatterClass: ProfileSnapshotFormatter::class,
);

// 2. Implement formatter
class ProfileSnapshotFormatter implements SnapshotFormatterInterface { ... }

// 3. Add button to view
<x-moderation::report-button :topic="'profile'" :entity-id="$profile->id" />
```
