# Role-gated notification preferences (moderator/admin) — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Notification** owns the reusable rule: a type may declare the roles that may
see its preference row and that form its recipient pool. It does not own the
two staff facts (a report was filed; a promotion was requested).

**Moderation** owns « Un nouveau signalement a été déposé » — content class,
registration, and the send from `ModerationService` after a report is created.

**Auth** owns « Une nouvelle demande de promotion a été déposée » — same shape
as the existing promotion accepted/rejected types, sent from
`PromotionRequestService` after the request row is created.

No new domain.

### 1.1 Notification

Extend `NotificationFactory::register` / `NotificationTypeDefinition` with an
optional role list. Add one public dispatch method that reads that list,
resolves active users via the existing `AuthPublicApi::getUserIdsByRoles`,
optionally drops one actor, then reuses today’s channel-preference filtering.

Preference listing, single-type save, and bulk save all honour the same list:
a user who currently holds none of the roles must not see the row and must not
persist a choice for that type. Stored rows already written are left in place
(demotion keeps prefs — spec §5).

`createNotification` and `createBroadcastNotification` stay unchanged.
Broadcast remains “all `user` + `user-confirmed`”, which is a different
audience (Quote Contest / News publish). Calendar activity eligibility is out
of scope (`calendar-notifications/`).

### 1.2 Moderation

Registers the report type with the staff role trio. After a successful report
insert (same place `ReportSubmitted` is already emitted), captures the
reporter’s display name via `ProfilePublicApi` (already used in this domain)
and calls the new Notification public method, excluding the reporter.

New deptrac edges: Moderation → Notification (§5). No new extension point.

### 1.3 Auth

Registers the promotion-request type with the same staff role trio. After a
successful `PromotionRequest` insert (same place `PromotionRequested` is
already emitted), captures the requester’s display name and calls the new
method, excluding the requester.

`AuthPrivate → NotificationPublic` already exists. Auth stays a pure emitter:
no new listeners.

## 2. Data model

### 2.1 Tables

No new table. No new columns.

`notification_preferences` stays sparse: a row exists only when the value
differs from the channel default. Website default remains ON; Discord default
remains OFF (`NotificationChannelDefinition::$defaultEnabled`). Staff types
use those defaults (`forcedOnWebsite: false`) so staff can opt out of the
inbox and opt in to Discord.

### 2.2 Model

No new Eloquent model. The audience lives on the in-memory type definition,
not in the database — same as `forcedOnWebsite` and `hideInSettings`.

### 2.3 Lifecycle rules

| Event | Persistence |
|-------|-------------|
| Demotion (loses last staff role) | Preference rows **kept**. Send-time lookup uses current roles, so they stop receiving. Listing uses current roles, so rows disappear. |
| Re-promotion | Rows reappear; sparse storage restores the last non-default choices, else channel defaults. |
| User deleted | Existing `CleanNotificationsOnUserDeleted` — prefs and sourced notifications go with the user. |
| Report / request resolved or deleted | Already-sent inbox rows stay. No “resolved” type. |
| Discord disconnect | Existing Discord listener; prefs not cleared. |

## 3. PHP architecture

### 3.1 Public API

`NotificationFactory::register` gains one optional argument. Existing callers
omit it and stay world-visible:

```php
public function register(
    string $type,
    string $class,
    string $groupId,
    string $nameKey,
    bool $forcedOnWebsite = false,
    bool $hideInSettings = false,
    ?array $visibleToRoles = null, // list<string> role slugs; null/[] = every authenticated user
): void
```

`NotificationTypeDefinition` carries the same `?array $visibleToRoles` and:

```php
/** True when the type has no audience, or the viewer holds at least one of those roles. */
public function isVisibleTo(array $roleSlugs): bool
```

`getTypesForGroup` keeps today’s `hideInSettings` filter only. Role visibility
is applied by the preferences service and the settings view, using
`isVisibleTo`. The types catalog export continues to list every registered
type (including staff ones) and should show the audience so the catalog stays
the source of truth.

`NotificationPublicApi` gains:

```php
/**
 * Dispatch to the type's visibleToRoles (active users only), then apply
 * per-user channel preferences as createNotification already does.
 *
 * @throws ValidationException if the type is unknown or has no visibleToRoles
 *   (this method is not a substitute for createNotification / createBroadcastNotification)
 */
public function createNotificationForTypeAudience(
    NotificationContent $content,
    ?int $sourceUserId = null,
    ?int $excludeUserId = null,
): void
```

Behaviour that differs from `createNotification`:

- Recipients come from `AuthPublicApi::getUserIdsByRoles($definition->visibleToRoles, activeOnly: true)`.
- `$excludeUserId`, when set, is removed from that set. `$sourceUserId` is
  **not** auto-excluded (it remains display/audit, as today).
- After exclude, an empty set is a **no-op**: no notification row, no
  exception. (`createNotification` throws on empty `$userIds`; the spec’s
  “reporter is the only staff member” case must not throw.)
- Channel filtering, Discord callbacks, and `forcedOnWebsite` then run exactly
  as in `createNotification`.

### 3.2 Services

**Notification — preferences.** `NotificationPreferencesService` is the
enforcement point for spec §4.1 / assumption A7:

- `getPreferencesForUser($userId)` omits types the user cannot currently see.
- `set($userId, $type, …)` refuses a type the user cannot see (so a crafted
  `PUT …/preferences/{type}` cannot write a staff row). Controller maps that
  refusal to **404**, same as `hideInSettings`.
- `setAll` / `setGroup` skip invisible types rather than failing the bulk
  (same pattern as skipping `forcedOnWebsite` on the website channel).

Role slugs for `$userId` come from `AuthPublicApi::getRolesByUserIds` — not
from the HTTP form.

**Moderation / Auth — send.** Each owning service, after the write succeeds
and the existing event is emitted, builds the content DTO (display name
captured now; a notification must not read the database at display time) and
calls `createNotificationForTypeAudience`. A notification failure must not
roll back the report or the promotion request — wrap the send like
`PromotionRequestService::sendAcceptedNotification` already does (`try/catch`
+ `report`).

### 3.3 Policy / authorization

No new policy class. Three stacked gates, all server-side:

| Surface | Gate |
|---------|------|
| Settings tab listing | `isVisibleTo(current user’s roles)` |
| Prefs write (`set` / `setAll` / `setGroup`) | same, on the `$userId` being written |
| Dispatch | current holders of `visibleToRoles`, `activeOnly`, minus `$excludeUserId` |

Hiding the row without the write gate is not enough (A7). Guests never reach
the tab (existing `auth` + `role:user,user-confirmed` middleware).

### 3.4 Events and listeners

No new domain event. No new listener.

`Moderation.ReportSubmitted` and `Auth.PromotionRequested` stay as they are
(`emitSync` vs async `emit` unchanged). Send is in the owning service, not on
those events, so a staff ping does not wait on the queue.

### 3.5 Routes, controllers, form requests

No new routes. No `PATCH`. Existing:

- `POST /notifications/preferences/`
- `PUT /notifications/preferences/`
- `PUT /notifications/preferences/{type}`

`NotificationPreferencesController::update` treats a role-gated type the
current user cannot see as **404** (unknown/hidden). `save` iterates only
visible types so a non-staff form post cannot invent staff keys.

## 4. Frontend architecture

Existing notification settings Blade table. No new Alpine store, no new JS
module, no new CSS.

The type loop must skip types for which `isVisibleTo` is false for the
current user, and must skip a group that then has no remaining types. Toggles,
`forcedOnWebsite` disabled website switch, and Discord unlinked warning stay
as they are.

Inbox items use the existing notification-item component. Content classes
render French HTML via `__()`; the queue URL is built in `display()` with
`route('moderation.admin.moderation-reports.index')` /
`route('auth.admin.promotion-requests.index')` — routes are allowed at
display time; display names are not looked up then.

## 5. Deptrac

| Edge | Why |
|------|-----|
| `ModerationPublic` → `NotificationPublic` | `ModerationServiceProvider` registers the report type on `NotificationFactory`. |
| `ModerationPrivate` → `NotificationPublic` | `ModerationService` calls `NotificationPublicApi` after report creation. |

No new Auth edge: `AuthPrivate` already depends on `NotificationPublic`.

No Notification → Moderation edge: Notification never learns what a report is.

`ProfilePublicApi` is already reachable from Moderation (Shared contract) and
Auth (existing promotion notifications).

## 6. Testing strategy

Integration tests are the default. Drive sends through the real report /
promotion-request actions, not by calling the new public method in isolation
for the owning-domain cases. Use `app/Domains/Notification/Tests/helpers.php`
— never assert against the `notifications` table directly.

| Coverage | Where |
|----------|--------|
| Type with `visibleToRoles` is omitted from the settings tab for a confirmed non-staff user; present for moderator / admin / tech-admin | Notification feature test |
| `PUT` of a staff type by a non-staff user is 404 and writes nothing; staff `PUT` persists | Notification feature test |
| Bulk enable-all / enable-group does not create staff-type prefs for a non-staff user; does for staff | Notification feature test |
| `createNotificationForTypeAudience` on a type without `visibleToRoles` throws; empty audience after exclude is a silent no-op | Notification feature test |
| New report: other active staff notified; reporter excluded; non-staff and deactivated staff not notified; site opt-out / Discord opt-in honoured | Moderation feature test |
| New promotion request: same recipient rules, requester excluded | Auth feature test |
| Demotion: rows gone from the tab, no further sends, stored prefs still in `notification_preferences` and restored on re-promotion | Notification + Auth feature test |
| Unit / vitest | None — no isolated algorithm, no new JS |
| Visual only (VERIFY) | Staff vs confirmed-user settings tab; inbox item copy and queue link; Discord column warning unchanged |

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where the reusable audience lives | **A.** `visibleToRoles` on the type; listing, save, and dispatch read it. **B.** Dispatch helper takes roles; prefs gate is a parallel map. **C.** Callers resolve IDs themselves; settings special-cases two keys. | A | Decision #8: a type *declares* the roles for both seeing the row and being selected. One source of truth; later types only pass a different list. B/C let visibility and send drift. |
| 2 | Who owns the two types | **A.** Moderation owns the report type; Auth owns the promotion-request type. **B.** Notification owns both and listens to the two events. | A | Types already live with the domain that has the fact (promotion accepted/rejected, Quote Contest). B would teach Notification about reports. Costs a Moderation → Notification edge, which is the honest dependency. |
| 3 | Where send is triggered | **A.** Owning service, after the write. **B.** Listeners on `ReportSubmitted` / `PromotionRequested`. | A | Same as promotion accepted/rejected; Auth stays a pure emitter; the promotion-request ping does not wait on an already-async event. |

Decided without asking (reversible except where noted):

- New method name `createNotificationForTypeAudience`; `sourceUserId` is not auto-excluded.
- Empty audience after exclude: no-op, no row.
- Non-staff write of a gated type: 404, not 403.
- Website opt-out uses the existing channel default (no per-type channel defaults).
- Send wrapped so a notification exception cannot undo the report/request.

## 8. File layout

New classes only. Existing files that must change are implied by §1–§4; PLAN
lists the edits.

```
app/Domains/Moderation/Public/Notifications/ReportSubmittedNotification.php
app/Domains/Auth/Public/Notifications/PromotionRequestedNotification.php
```

Both implement `NotificationContent`. Type keys (permanent once stored):

| Key | Class | `groupId` | `visibleToRoles` |
|-----|-------|-----------|------------------|
| `moderation.report.submitted` | `ReportSubmittedNotification` | `moderation` | `moderator`, `admin`, `tech-admin` |
| `auth.promotion.requested` | `PromotionRequestedNotification` | `moderation` | same |

Payload for both: actor display name only (`user_name`, captured at send).
No report id, reason, topic, or request id — the body links to the admin
index, not a show page (assumption A6).

French preference labels and inbox copy live in the owning domain’s lang
files (`moderation::…`, `auth::…`). Exact sentences may be tightened at BUILD;
meaning is fixed in the spec.

## 9. Risks acknowledged

- **Staff trio duplicated.** Navbar badges, admin nav, and these two types
  each list `moderator` / `admin` / `tech-admin`. A fourth staff role would
  need all three updated. Revisit if a `Roles` helper for “staff” appears;
  do not add one in this task.
- **`createNotification` empty-set throw vs new method no-op.** Easy to call
  the wrong method from a later type. The new method throws when the type has
  no `visibleToRoles`, which makes the mix-up fail closed.
- **Prefs service currently does not enforce `forcedOnWebsite`** (controller
  does). Role visibility *does* go in the service so `set` / `setAll` /
  `setGroup` cannot drift. Two enforcement styles on one class — acceptable;
  do not relocate `forcedOnWebsite` here.
- **Moderation → Notification is a new edge.** If Moderation later needed to
  stay notification-free, the alternative is Notification listening to
  `ReportSubmitted` (rejected in §7.2). Revisit only if that edge starts
  pulling Moderation toward more Notification internals.
- **No digest.** A burst of reports creates one inbox row each. Spec §8;
  revisit if staff mail becomes noisy.
