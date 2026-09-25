# Role-gated notification preferences (moderator/admin) — implementation plan

> PLAN output. BUILD reads **one phase at a time** and nothing else of this file.
> Each phase names the architecture sections it depends on and states what earlier
> phases left behind.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Role-gated notification infrastructure | M | — | DONE |
| 2 | Moderation — report submitted staff notification | S | 1 | DONE |
| 3 | Auth — promotion requested staff notification | S | 1 | DONE |
| 4 | Staff demotion and re-promotion lifecycle | S | 1, 3 | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days.

## Working agreement

- One phase = one commit. Each phase ships independently, keeps `pnpm run gate`
  green, and is revertable on its own.
- Failing integration test first, then the implementation.
- Do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Role-gated notification infrastructure

**Goal.** A notification type may declare `visibleToRoles`; that list gates preference
listing, preference writes (404 for non-audience), bulk saves, settings UI, and a new
`createNotificationForTypeAudience` dispatch path — all before any owning-domain staff
type ships.

**Architecture sections.** §1.1 Notification, §2 (no schema change), §3.1–§3.3, §3.5,
§4 (settings Blade filter), §6 (Notification-domain rows).

**Deliverables.**

- `app/Domains/Notification/Public/Contracts/NotificationTypeDefinition.php` —
  add `?array $visibleToRoles` constructor param; add `isVisibleTo(array $roleSlugs): bool`
  (true when list is null/empty, or viewer holds at least one listed role).
- `app/Domains/Notification/Public/Services/NotificationFactory.php` — extend
  `register(…, ?array $visibleToRoles = null)` per architecture §3.1 signature.
- `app/Domains/Notification/Public/Api/NotificationPublicApi.php` — add
  `createNotificationForTypeAudience(NotificationContent $content, ?int $sourceUserId = null, ?int $excludeUserId = null): void`:
  resolve recipients via `AuthPublicApi::getUserIdsByRoles($definition->visibleToRoles, activeOnly: true)`,
  drop `$excludeUserId` when set, **no-op** (no notification row) when the set is empty,
  throw `ValidationException` when the type is unknown or has no `visibleToRoles`;
  then reuse the same channel-preference filtering as `createNotification`.
- `app/Domains/Notification/Private/Services/NotificationPreferencesService.php` —
  inject `AuthPublicApi`; in `getPreferencesForUser`, `set`, `setAll`, and `setGroup`,
  skip types the user cannot see (roles from `getRolesByUserIds`, not the HTTP form);
  `set` throws a dedicated exception (e.g. `NotificationTypeNotVisibleException`) when
  the type exists but is invisible to that user.
- `app/Domains/Notification/Private/Controllers/NotificationPreferencesController.php` —
  `update`: treat invisible role-gated types like `hideInSettings` → **404**;
  catch the service exception from `set` → **404**;
  `save`: iterate only types visible to the current user (same role lookup);
  `bulkUpdate`: unchanged call path — service already skips invisible types.
- `app/Domains/Notification/Private/Resources/views/settings/settings.blade.php` —
  resolve current user's role slugs once; skip types where `!$typeDef->isVisibleTo($roles)`;
  skip a group header when no visible types remain in that group.
- `app/Domains/Notification/Private/Console/ExportNotificationTypesDocumentationCommand.php` —
  add a **Visible to roles** column to the types table (`all authenticated users` when
  null/empty; otherwise comma-separated slugs).
- `app/Domains/Notification/Tests/Fixtures/StaffOnlyTestNotificationContent.php` —
  test-only content class, type key `test.staff.notification`, registered in tests with
  `visibleToRoles: [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN]`.

**Tests.**

- `app/Domains/Notification/Tests/Feature/RoleGatedNotificationPreferencesTest.php`
  - `it hides a visibleToRoles type from the settings tab for a confirmed non-staff user`
  - `it shows a visibleToRoles type on the settings tab for moderator, admin, and tech-admin`
  - `it returns 404 on PUT of a staff-only type by a non-staff user and writes no preference row`
  - `it persists a staff-only type preference when a moderator PUTs a channel toggle`
  - `it skips staff-only types on bulk enable-all for a non-staff user`
  - `it includes staff-only types on bulk enable-all for a staff user`
- `app/Domains/Notification/Tests/Feature/CreateNotificationForTypeAudienceTest.php`
  - `it throws when the type has no visibleToRoles`
  - `it is a silent no-op when the audience is empty after excluding the actor`
  - `it delivers to role holders respecting website opt-out and Discord opt-in`
- Extend `app/Domains/Notification/Tests/Feature/ExportNotificationTypesDocumentationCommandTest.php`
  to assert the exported markdown includes the visible-to-roles column for a registered
  staff-only fixture type.

**Acceptance.**

- ✅ Types with null/empty `visibleToRoles` behave exactly as today (world-visible).
- ✅ Non-staff users never see staff-only rows; crafted single-type PUT returns 404;
  bulk save cannot create staff-type preference rows.
- ✅ `createNotificationForTypeAudience` throws without `visibleToRoles`; empty audience
  after exclude creates no `notifications` row.
- ✅ Settings Blade hides empty groups after role filtering.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Moderation — report submitted staff notification

**Goal.** After a report is created, every active staff member except the reporter
receives a `moderation.report.submitted` notification; Moderation owns the type and
the send.

**Architecture sections.** §1.2 Moderation, §5 (deptrac), §6 (Moderation row), §8
(`ReportSubmittedNotification`).

**Earlier phases.** Phase 1 left `visibleToRoles`, preference gates, and
`createNotificationForTypeAudience` in place. This phase registers the first real
staff type and wires the send.

**Deliverables.**

- `app/Domains/Moderation/Public/Notifications/ReportSubmittedNotification.php` —
  `NotificationContent` implementing type `moderation.report.submitted`; payload
  `user_name` only; `display()` returns French HTML with reporter display name and
  `route('moderation.admin.moderation-reports.index')`.
- `app/Domains/Moderation/Public/Providers/ModerationServiceProvider.php` —
  register the type on `NotificationFactory` with `groupId: 'moderation'`,
  `nameKey: 'moderation::notifications.settings.type_report_submitted'`,
  `visibleToRoles: [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN]`.
- `app/Domains/Moderation/Private/Services/ModerationService.php` — after successful
  report insert and `ReportSubmitted` emit (same block as today), capture reporter
  display name via `ProfilePublicApi`, call
  `NotificationPublicApi::createNotificationForTypeAudience(…, sourceUserId: reporterId, excludeUserId: reporterId)`
  inside `try/catch` + `report()` so a notification failure cannot roll back the report.
- `app/Domains/Moderation/Private/Resources/lang/fr/notifications.php` (or extend
  existing lang file) — preference label and inbox copy per functional spec §4.2.
- `deptrac.yaml` — add `NotificationPublic` to `ModerationPublic` and
  `ModerationPrivate` collectors (architecture §5).

**Tests.**

- `app/Domains/Moderation/Tests/Feature/ReportSubmittedStaffNotificationTest.php`
  - `it notifies other active staff when a report is submitted`
  - `it excludes the reporter from staff notification recipients`
  - `it does not notify non-staff users`
  - `it does not notify deactivated staff accounts`
  - `it creates no notification when the reporter is the only staff member`
  - `it honours website opt-out and Discord opt-in for staff recipients`
  - Drive sends through `POST /moderation/report`; assert via
    `app/Domains/Notification/Tests/helpers.php` (`getLatestNotificationByKey`,
    `getNotificationTargetUserIds`, `notificationReadRow`) — never query
    `notifications` ad hoc.

**Acceptance.**

- ✅ Report creation still succeeds when notification dispatch throws (mock or forced failure).
- ✅ Inbox body contains reporter display name and moderation-reports queue link only
  (no reason/topic).
- ✅ Existing `SubmitModerationReportTest` behaviour unchanged aside from new notification side effects.
- ✅ Deptrac passes with the new Moderation → Notification edges.
- ✅ `pnpm run gate` green.

---

## Phase 3 — Auth — promotion requested staff notification

**Goal.** After a promotion request is created, every active staff member except the
requester receives an `auth.promotion.requested` notification.

**Architecture sections.** §1.3 Auth, §6 (Auth row), §8 (`PromotionRequestedNotification`).

**Earlier phases.** Phase 1 infrastructure; phase 2 is not required for this phase.

**Deliverables.**

- `app/Domains/Auth/Public/Notifications/PromotionRequestedNotification.php` —
  type `auth.promotion.requested`; payload `user_name`; `display()` with requester
  name and `route('auth.admin.promotion-requests.index')`.
- `app/Domains/Auth/Public/Providers/AuthServiceProvider.php` — register alongside
  accepted/rejected types: `groupId: 'moderation'`,
  `nameKey: 'auth::notification.settings.type_promotion_requested'`,
  `visibleToRoles: [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN]`,
  `forcedOnWebsite: false`.
- `app/Domains/Auth/Private/Services/PromotionRequestService.php` — after successful
  `PromotionRequest::create` and `PromotionRequested` emit, capture requester display
  name via `ProfilePublicApi`, call `createNotificationForTypeAudience(…, sourceUserId: $userId, excludeUserId: $userId)` with the same `try/catch` + `report()` pattern as `sendAcceptedNotification`.
- `app/Domains/Auth/Private/Resources/lang/fr/notification.php` — preference label
  and inbox copy per functional spec §4.3.

**Tests.**

- `app/Domains/Auth/Tests/Feature/PromotionRequestedStaffNotificationTest.php`
  - `it notifies other active staff when a promotion request is submitted`
  - `it excludes the requester from staff notification recipients`
  - `it does not notify non-staff users`
  - `it does not notify deactivated staff accounts`
  - `it honours website opt-out and Discord opt-in for staff recipients`
  - Drive through `AuthPublicApi::requestPromotion` (same eligibility setup as
    `AuthPublicApiPromotionTest`); assert via Notification test helpers.

**Acceptance.**

- ✅ Requester-facing accepted/rejected types and rows stay unchanged for all users.
- ✅ Request creation still succeeds when notification dispatch throws.
- ✅ No new deptrac edges (AuthPrivate → NotificationPublic already allowed).
- ✅ `pnpm run gate` green.

---

## Phase 4 — Staff demotion and re-promotion lifecycle

**Goal.** Demotion hides staff notification preference rows and stops sends immediately
while keeping stored choices; re-promotion restores the rows with those choices.

**Architecture sections.** §2.3 Lifecycle, §5 (send-time role check), §6 (demotion row).

**Earlier phases.** Phase 1 gates; phase 3 registers `auth.promotion.requested` (used
as the real staff type under test).

**Deliverables.**

- No new production classes — behaviour falls out of phase 1 gates plus phase 3
  registration. Only tests (and lang tweaks only if VERIFY finds copy gaps).

**Tests.**

- `app/Domains/Notification/Tests/Feature/StaffNotificationRoleLifecycleTest.php`
  - `it hides staff notification preference rows after the user loses their last staff role`
  - `it stops delivering staff notifications after demotion`
  - `it keeps stored staff-type preferences across demotion`
  - `it restores preference rows with stored choices after re-promotion`
  - Use `RoleService::revoke` / `RoleService::grant` on a moderator; seed a non-default
    Discord opt-in on `auth.promotion.requested`; trigger send before/after demotion via
    `AuthPublicApi::requestPromotion`; assert settings tab HTML and Notification helpers.

**Acceptance.**

- ✅ Demoted user no longer receives `auth.promotion.requested` sends even with stale
  preference rows in `notification_preferences`.
- ✅ Re-promoted user sees the row again with prior opt-in/out intact.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled during PLAN; VERIFY executes against a running app (`verify-visually` skill).

| Surface | Role / state | Check | OK? |
|---------|--------------|-------|-----|
| Settings → Notifications tab | Confirmed non-staff (`user-confirmed`) | « Promotions & modération » shows only the two requester-facing promotion rows; **no** « nouveau signalement » or « nouvelle demande de promotion » rows | |
| Settings → Notifications tab | Moderator (or admin / tech-admin) | Same group shows **four** rows: two requester-facing + two new staff rows | |
| Settings → Notifications tab | Staff | Staff row website toggles default **on**; Discord toggles default **off** | |
| Settings → Notifications tab | Staff, Discord unlinked | Discord column still shows the existing link warning; website column unchanged | |
| Settings → Notifications tab | Mobile viewport (~375px) | Table scrolls/aligns like today; staff rows readable | |
| Inbox | Staff recipient after another user files a report | French line, reporter display name, link opens moderation-reports admin queue | |
| Inbox | Staff recipient after a user submits a promotion request | French line, requester display name, link opens promotion-requests admin queue | |
| Settings → Notifications tab | Staff demoted to `user-confirmed` only | Two staff rows disappear; requester-facing rows remain | |
| Settings → Notifications tab | Same user re-promoted to moderator | Staff rows reappear with prior toggle choices restored | |

## Open items

None. Verified before PLAN:

- `NotificationFactory::register` currently has six parameters ending at
  `hideInSettings` — the seventh `visibleToRoles` slot is additive (`02-architecture.md` §3.1).
- `AuthPublicApi::getUserIdsByRoles` and `getRolesByUserIds` exist and support
  `activeOnly` (Auth domain tests).
- `PromotionRequestService` already resolves `NotificationPublicApi` and
  `ProfilePublicApi` lazily; accepted/rejected sends use `try/catch` + `report()`.
- `ModerationService` emits `ReportSubmitted` synchronously after insert; no
  Notification dependency yet — phase 2 adds the first Moderation → Notification deptrac edges.
- `deptrac.yaml` `ModerationPublic` / `ModerationPrivate` do not yet list
  `NotificationPublic`; `AuthPrivate` already does.
- Calendar / Quote Contest activity eligibility remains out of scope (`calendar-notifications/` backlog entry).
