# Role-gated notification preferences (moderator/admin)

**Status:** DONE — 2026-09-26 · **Domain(s):** `Notification` (core), `Moderation`, `Auth` · **Spec:**
[functional](./01-functional.md) · [architecture](./02-architecture.md) ·
[plan](./03-plan.md) · [decisions](./DECISIONS.md)

## What it does

A notification type can now declare `visibleToRoles` at `NotificationFactory::register`.
That one list gates who sees the type's preference row, who may write a preference for it,
and who receives it through the new `NotificationPublicApi::createNotificationForTypeAudience`.
Two staff-only types ship on it: `moderation.report.submitted` (new report) and
`auth.promotion.requested` (new promotion request), both sent to active moderator /
admin / tech-admin except the actor, both in the existing « Promotions & modération » group.
No schema change, no new route, no JS.

## Key behaviour

- **Visibility:** null/empty `visibleToRoles` = every authenticated user (unchanged); otherwise the user must currently hold one listed role (`NotificationTypeDefinition::isVisibleTo`).
- **Server-side gate, not cosmetic:** roles come from `AuthPublicApi::getRolesByUserIds`, never the request. `set` throws `NotificationTypeNotVisibleException` → controller 404 (same as `hideInSettings`); `setAll`, `setGroup` and the full-form `save` silently skip invisible types; the settings Blade drops invisible rows and empty group headers.
- **Recipients:** `getUserIdsByRoles(visibleToRoles, activeOnly: true)` minus `$excludeUserId`; then the normal `createNotification` channel filtering (website default on / opt-out, Discord default off / opt-in).
- **Empty audience = silent no-op** (no row). Unknown type or no `visibleToRoles` = `ValidationException` — the method is not a general-purpose sender. Contrast: `createNotification` throws on an empty id list.
- **`$sourceUserId` is not auto-excluded;** callers pass `excludeUserId` explicitly.
- **Demotion:** stored preference rows are kept; rows vanish from the tab and sends stop at once (audience = current roles). Re-promotion restores prior choices. Deactivated staff are never recipients.
- **Send never undoes the write:** both owning services call it after the insert + event emit, inside `try/catch` + `report()`.
- **Payload = actor `user_name` only**, captured at send via `ProfilePublicApi` (empty string if no profile); `display()` HTML-escapes it and links to the admin *index* (reports queue / promotion-requests queue), never a show page, never the report reason/topic.
- Already-sent inbox rows stay when the report/request is later resolved or deleted.
- The export command (`docs/notification-types.md`) gains a **Visible to roles** column.

## Where the code lives

| Concern | Path |
|---------|------|
| Type definition + `isVisibleTo` | `app/Domains/Notification/Public/Contracts/NotificationTypeDefinition.php` |
| Registration arg | `app/Domains/Notification/Public/Services/NotificationFactory.php` (`register(…, ?array $visibleToRoles = null)`) |
| Audience dispatch | `app/Domains/Notification/Public/Api/NotificationPublicApi.php` (`createNotificationForTypeAudience`) |
| Preference gate | `app/Domains/Notification/Private/Services/NotificationPreferencesService.php`, `Private/Exceptions/NotificationTypeNotVisibleException.php` |
| Controller 404 / save filter | `app/Domains/Notification/Private/Controllers/NotificationPreferencesController.php` |
| Settings view | `app/Domains/Notification/Private/Resources/views/settings/settings.blade.php` |
| Catalog export | `app/Domains/Notification/Private/Console/ExportNotificationTypesDocumentationCommand.php` |
| Report type + send | `app/Domains/Moderation/Public/Notifications/ReportSubmittedNotification.php`, `Private/Services/ModerationService.php` (`notifyStaffOfNewReport`), registered in `ModerationServiceProvider` |
| Promotion type + send | `app/Domains/Auth/Public/Notifications/PromotionRequestedNotification.php`, `Private/Services/PromotionRequestService.php` (`notifyStaffOfNewRequest`), registered in `AuthServiceProvider` |
| French copy | `Moderation/Private/Resources/lang/fr/notifications.php`, `Auth/Private/Resources/lang/fr/notification.php`, `Notification/.../lang/fr/validation.php` |
| Tests | `Notification/Tests/Feature/{RoleGatedNotificationPreferences,CreateNotificationForTypeAudience,StaffNotificationRoleLifecycle}Test.php`, `Notification/Tests/Fixtures/StaffOnlyTestNotificationContent.php`, `Moderation/Tests/Feature/ReportSubmittedStaffNotificationTest.php`, `Auth/Tests/Feature/PromotionRequestedStaffNotificationTest.php` |
| E2E world | `promotable@e2e.test` in `E2eAccountsSeeder` (+ `PROMOTABLE` in `e2e/support/fixtures.ts`) and its 5 root comments in `E2eCommentsSeeder`, kept for future specs |
| Deptrac | `ModerationPublic` → `NotificationPublic` (type registration), `ModerationPrivate` → `NotificationPublic` (send). Auth needed no new edge. |

## Extension points used

- **NotificationFactory** — two new types, group `moderation`, `visibleToRoles: [MODERATOR, ADMIN, TECH_ADMIN]`.
- **NotificationPublicApi** — new audience method, reusable by any later "send to roles, minus actor" type.

## Decisions worth remembering

- Audience lives **on the type** (#10, §7.1 of architecture): one list for seeing the row and for being selected, so visibility and send cannot drift.
- Each domain owns its type (#11): Moderation owns the report type, Auth the request type; Notification knows nothing about reports.
- Send is in the owning service after the write, not a listener on `ReportSubmitted` / `PromotionRequested` (#12) — Auth stays a pure emitter, no queue wait.
- Per-activity eligibility (Calendar / Quote Contest `role_restrictions`) is **not** this rule (#9) — that audience is "users authorized to this activity", handled by `calendar-notifications/`.

**Assumptions made without asking (DECISIONS.md A1–A15) — all reversible except A7, A8:**
A1 same « Promotions & modération » group, no staff-only group · A2 one notification per event, no digest ·
A3 deactivated accounts excluded · A4 sent rows stay after resolve/delete · A5 preference labels « Un nouveau
signalement a été déposé » / « Une nouvelle demande de promotion a été déposée » · A6 links to admin index, not
show page · **A7 writes refused for non-staff, not only hidden (not reversible — else the gate is cosmetic)** ·
**A8 keys `moderation.report.submitted` / `auth.promotion.requested`, payload `user_name` only (permanent once
stored)** · A9 empty audience = no-op · A10 null/empty `visibleToRoles` = everyone · A11 non-staff write → 404
not 403 · A12 send wrapped so it cannot roll back the write · A13 `sourceUserId` not auto-excluded · A14/A15
inbox copy « :user_name a déposé un nouveau signalement / une nouvelle demande de promotion. Voir les … »
(link), name HTML-escaped.

## Code vs plan

The code matches `03-plan.md` phase for phase. Things the plan did not spell out:

- The controller checks visibility itself in `update` *before* calling `set`, and `set` checks again. The duplication is intentional.
- The settings Blade resolves roles itself (`@inject` `AuthPublicApi`) in addition to `getPreferencesForUser` filtering. Two filters, one rule.
- `ModerationService` reads the reporter from `Auth::id()` (as `createReport` already did); Auth passes `$userId` through.
- Phase 4 added no production code; the demotion checklist rows were moved from browser to PHP (`StaffNotificationRoleLifecycleTest`).

## Not done

- **Non-goals (spec §8):** other staff types; notifying staff on report approve/reject/delete; gating the requester-facing accepted/rejected rows; changing navbar badges; digest/rate limit; Calendar/Quote Contest audiences; Discord warning changes.
- **Cut mid-build:** nothing.
- **Known risk kept:** the staff trio `moderator/admin/tech-admin` is now hard-coded in badges, admin nav and both registrations. A fourth staff role must update all of them. No `Roles::staff()` helper was added on purpose.
- **Backlog:** no new row. The existing `calendar-notifications/` row already carries the per-activity-audience leftover (decision #9).
- **E2E:** `e2e/tests/features/role-gated-notification-settings.spec.ts` **deleted** at WRAP. It was VERIFY-only, and PHP tests cover the gates, sends and lifecycle. Its four page objects and the `E2eFeatureTogglesSeeder` (Discord column on) were removed with it (user decision); the `promotable` account was kept.
