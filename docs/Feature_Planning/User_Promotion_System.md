# User Promotion System

## Context & Problem Statement

Currently, users must have an activation code to register on the platform. Upon email verification, they receive the `USER_CONFIRMED` role, granting full access (read, write stories, comment).

We want to support **unsponsored registration** (without activation code), where users start with limited privileges (`USER` role) and must request promotion to `USER_CONFIRMED` after meeting certain criteria.

---

## Functional Requirements

### Registration Flow

1. **Sponsored users** (with activation code): Upon email verification, receive `USER_CONFIRMED` role directly.
2. **Unsponsored users** (without activation code, when allowed): Upon email verification, receive `USER` role only.
   - Can read stories and chapters
   - Can post comments
   - **Cannot** write their own stories

### Promotion Criteria

To request promotion, a `USER` must meet **both** criteria:
- **Time criterion**: Be registered for at least `X` days (configurable, default 7 days)
- **Comment criterion**: Have posted at least `Y` root comments on chapters (configurable, default 5)

### Promotion Request Flow

1. User sees promotion status on Dashboard (replaces `KeepWritingComponent` for non-confirmed users)
2. Progress bars show advancement toward both criteria
3. When both criteria are met, user can click "Request Promotion"
4. Request is stored with `pending` status
5. Admins/Moderators see pending requests count in navbar (similar to moderation icon)
6. On admin screen, they review requests and either **Accept** or **Reject** (with reason)
7. User is notified via system notification of the outcome
8. On acceptance: `USER` → `USER_CONFIRMED`
9. On rejection: Reason displayed on dashboard, countdown restarts from rejection date

### After Rejection

- The **time criterion** restarts from rejection date (not original registration)
- The **comment criterion** does NOT reset (total count must stay above threshold)
- User can request again once time criterion is met again

---

## Technical Architecture

### Domain Boundaries

| Domain | Responsibilities |
|--------|------------------|
| **Auth** | Promotion request table, admin screen, navbar icon, role management, notifications |
| **Dashboard** | Display promotion status component, compute comment count, trigger promotion request via AuthPublicApi |
| **Config** | Store `require_activation_code`, `non_confirmed_comment_threshold`, `non_confirmed_timespan` parameters |
| **Comment** | Provide root comment count via existing `CommentPublicApi::countRootCommentsByUser()` |
| **Notification** | Deliver system notifications for approval/rejection |

### Data Model

#### `user_promotion_request` table (Auth domain)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | bigint | User requesting promotion (no FK, cross-domain) |
| `status` | enum | `pending`, `accepted`, `rejected` |
| `comment_count` | int | Comment count at time of request |
| `requested_at` | timestamp | When request was submitted |
| `decided_at` | timestamp | When decision was made (nullable) |
| `decided_by` | bigint | Admin/moderator user ID (nullable, no FK) |
| `rejection_reason` | text | Reason if rejected (nullable) |
| `created_at` | timestamp | Laravel timestamp |
| `updated_at` | timestamp | Laravel timestamp |

**Indexes:**
- `user_id` (for filtering by user)
- `status` (for filtering pending)
- `requested_at` (for ordering)

### Config Parameters (Auth domain)

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `require_activation_code` | bool | `false` | If true, activation code is mandatory for registration |
| `non_confirmed_comment_threshold` | int | 5 | Minimum root comments required |
| `non_confirmed_timespan` | time | 604800 (7 days) | Minimum time since registration/last rejection |

### AuthPublicApi Extensions

```php
// Check if user can request promotion (both criteria met, no pending request)
public function canRequestPromotion(int $userId, int $commentCount): PromotionEligibility;

// Submit promotion request
public function requestPromotion(int $userId, int $commentCount): PromotionRequestResult;

// Get current promotion status for dashboard display
public function getPromotionStatus(int $userId): ?PromotionStatusDto;
```

### DTOs

```php
// Eligibility check result
class PromotionEligibility {
    public bool $eligible;
    public bool $hasPendingRequest;
    public int $daysRequired;
    public int $daysElapsed;
    public int $commentsRequired;
    public int $commentsPosted;
    public ?string $lastRejectionReason;
    public ?\DateTime $lastRejectionDate;
}

// Promotion request result
class PromotionRequestResult {
    public bool $success;
    public ?string $errorKey; // 'already_pending', 'criteria_not_met', etc.
}

// Current status for dashboard
class PromotionStatusDto {
    public string $status; // 'none', 'pending', 'rejected'
    public ?string $rejectionReason;
    public ?\DateTime $rejectionDate;
}
```

---

## User Stories

### Phase 1: Config Migration & Core Model

| ID | Story | Priority | Status |
|----|-------|----------|--------|
| AU-001 | Migrate `require_activation_code` from `config/app.php` to Config domain parameter | High | [DONE] |
| AU-002 | Update `RoleService::assignRolesBasedOnActivationCode()` to use new config parameter | High | [DONE] |
| AU-003 | Create `user_promotion_request` migration and model in Auth domain | High | [DONE] |
| AU-004 | Create `PromotionRequestService` in Auth domain (CRUD operations) | High | [DONE] |

### Phase 2: AuthPublicApi & Eligibility Logic

| ID | Story | Priority | Status |
|----|-------|----------|--------|
| AU-005 | Create `PromotionEligibility` and related DTOs | High | [DONE] |
| AU-006 | Implement `AuthPublicApi::canRequestPromotion()` | High | [DONE] |
| AU-007 | Implement `AuthPublicApi::requestPromotion()` | High | [DONE] |
| AU-008 | Implement `AuthPublicApi::getPromotionStatus()` | High | [DONE] |

### Phase 3: Dashboard Component

| ID | Story | Priority |
|----|-------|----------|
| AU-009 | Create `PromotionStatusComponent` in Dashboard domain | High |
| AU-010 | Display progress bars (days elapsed / required, comments / required) | High |
| AU-011 | Display "Request Promotion" button (enabled when eligible) | High |
| AU-012 | Handle pending state (show "Awaiting review" message) | High |
| AU-013 | Handle rejection state (show reason + new countdown) | High |
| AU-014 | Replace `KeepWritingComponent` with `PromotionStatusComponent` for non-confirmed users | Medium |

### Phase 4: Admin Screen

| ID | Story | Priority |
|----|-------|----------|
| AU-015 | Create `PromotionRequestController` in Auth domain (Admin namespace) | High |
| AU-016 | Create admin list view with filters (pending/all, by user) | High |
| AU-017 | Display columns: user profile link, request date, waiting duration, comment count | High |
| AU-018 | Implement "Accept" action (grant `USER_CONFIRMED`, revoke `USER`) | High |
| AU-019 | Implement "Reject" action with reason modal | High |
| AU-020 | Register admin page in `AdminNavigationRegistry` | Medium |

### Phase 5: Navbar Icon

| ID | Story | Priority |
|----|-------|----------|
| AU-021 | Create `PromotionIconComponent` in Auth domain (similar to `ModerationIconComponent`) | High |
| AU-022 | Display pending count badge for Admin/Tech-Admin/Moderator | High |
| AU-023 | Link to admin promotion screen | High |
| AU-024 | Add component to navbar layout | Medium |

### Phase 6: Notifications

| ID | Story | Priority |
|----|-------|----------|
| AU-025 | Create `PromotionAcceptedNotification` content class | Medium |
| AU-026 | Create `PromotionRejectedNotification` content class | Medium |
| AU-027 | Send notification on acceptance via `NotificationPublicApi` | Medium |
| AU-028 | Send notification on rejection via `NotificationPublicApi` | Medium |

### Phase 7: Testing

| ID | Story | Priority |
|----|-------|----------|
| AU-029 | Unit tests for `PromotionRequestService` | High |
| AU-030 | Feature tests for promotion eligibility logic | High |
| AU-031 | Feature tests for promotion request submission | High |
| AU-032 | Feature tests for admin accept/reject actions | High |
| AU-033 | Feature tests for role assignment on acceptance | High |

### Phase 8: Cleanup

| ID | Story | Priority |
|----|-------|----------|
| AU-034 | Remove `require_activation_code` from `config/app.php` | Low |
| AU-035 | Update any legacy code referencing old config key | Low |

---

## UI Mockups

### Dashboard: Promotion Status Component

```
┌─────────────────────────────────────────┐
│            Bonjour !                    │
│                                         │
│  Tu es actuellement une graine          │
│  d'Esperluette.                         │
│                                         │
│           🌱                            │
│                                         │
│  Pour devenir Esperluette               │
│  tu as besoin :                         │
│                                         │
│  • D'être inscrit.e depuis 14 jours,    │
│  • D'avoir posté 10 commentaires.       │
│                                         │
│  ████░░░░░░░░░░░░░░░░  3/14 jours       │
│  ██████████████░░░░░░  7/10 commentaires│
│                                         │
│  ┌─────────────────────────────┐        │
│  │   Demander à passer &      │        │
│  └─────────────────────────────┘        │
└─────────────────────────────────────────┘
```

### Dashboard: Pending State

```
┌─────────────────────────────────────────┐
│  Ta demande de promotion est en         │
│  cours d'examen par l'équipe.           │
│                                         │
│           ⏳                            │
│                                         │
│  Nous reviendrons vers toi bientôt !    │
└─────────────────────────────────────────┘
```

### Dashboard: Rejection State

```
┌─────────────────────────────────────────┐
│  Ta demande de promotion a été refusée. │
│                                         │
│  Raison : [rejection reason here]       │
│                                         │
│  Tu pourras refaire une demande dans    │
│  X jours.                               │
│                                         │
│  ████░░░░░░░░░░░░░░░░  3/14 jours       │
└─────────────────────────────────────────┘
```

### Admin: Promotion Requests List

```
┌─────────────────────────────────────────────────────────────────────┐
│  Demandes de promotion                              [Filter: Pending ▼] │
├─────────────────────────────────────────────────────────────────────┤
│  Utilisateur  │ Date demande │ Attente  │ Commentaires │ Actions   │
├───────────────┼──────────────┼──────────┼──────────────┼───────────┤
│  @username1   │ 2025-12-01   │ 15 jours │ 12           │ ✓ ✗ 👁    │
│  @username2   │ 2025-12-02   │ 8 jours  │ 7            │ ✓ ✗ 👁    │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Open Questions

1. **Ban threshold**: After how many rejections should we consider banning? (Future feature, not in scope)
2. **Notification wording**: Need French translations for notification messages
3. **Admin screen pagination**: Standard pagination or infinite scroll?

---

## Dependencies

- Custom Admin System (Phase 1 must be complete for admin screen)
- Notification domain (already available)
- Config domain (already available)

---

## Estimated Effort

| Phase | Effort |
|-------|--------|
| Phase 1: Config & Model | 2-3 hours |
| Phase 2: AuthPublicApi | 3-4 hours |
| Phase 3: Dashboard Component | 4-5 hours |
| Phase 4: Admin Screen | 6-8 hours |
| Phase 5: Navbar Icon | 1-2 hours |
| Phase 6: Notifications | 2-3 hours |
| Phase 7: Testing | 4-6 hours |
| Phase 8: Cleanup | 1 hour |
| **Total** | **23-32 hours** |

---

## Implementation Order

1. AU-001 → AU-004 (Foundation)
2. AU-005 → AU-008 (API layer)
3. AU-009 → AU-014 (Dashboard - visible to users)
4. AU-021 → AU-024 (Navbar icon - visible to admins)
5. AU-015 → AU-020 (Admin screen)
6. AU-025 → AU-028 (Notifications)
7. AU-029 → AU-033 (Tests in parallel with each phase)
8. AU-034 → AU-035 (Cleanup last)
