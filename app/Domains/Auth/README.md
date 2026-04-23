# Auth Domain

The Auth domain is the single source of truth for user identity, authentication, and access control on the platform. It owns all user lifecycle operations — from registration and email verification through role management, account compliance, and permanent deletion.

Built on Laravel Breeze; other domains never touch `users`, `roles`, or `sessions` tables directly — they call `AuthPublicApi` instead.

---

## Key Concepts

### User lifecycle

A new user goes through the following stages before gaining full access:

1. **Registration** — User submits email, password, and terms acceptance. An activation code is optional or mandatory depending on the `require_activation_code` config parameter.
2. **Email verification** — The `MustVerifyEmail` contract sends a verification link. Unverified users are blocked from compliant routes.
3. **Compliance gate** — The `EnsureUserCompliance` middleware checks that the user has accepted terms and, if under 15, has uploaded a signed parental-authorisation PDF.
4. **Role assignment** — After email verification and compliance, `RoleService::assignRolesBasedOnActivationCode()` determines whether the user receives `user` (probationary) or `user-confirmed`.

### Role system

Roles are slug-based. All role slugs are declared as constants in `Public/Api/Roles`:

| Slug | Meaning |
|------|---------|
| `user` | Newly registered, probationary. Community content (e.g. `community` stories) is not accessible. |
| `user-confirmed` | Confirmed user. Full community access. |
| `moderator` | Can review promotion requests and moderate content. |
| `admin` | Full admin access. Can manage users and promotion requests. |
| `tech-admin` | Same reach as admin; reserved for technical staff. |

Users can hold multiple roles simultaneously. Role assignment, revocation, and caching all go through `RoleService` (which wraps `User::assignRole` / `User::removeRole`) and emits `UserRoleGranted` / `UserRoleRevoked` events.

Role lookups are cached per user for 10 minutes by `RoleCacheService` (key `auth:user_roles:{userId}`). The cache is cleared whenever a role is mutated.

### Activation codes

Activation codes gate registration when `require_activation_code = true`. Codes use the format `XXXX-XXXXXXXX-XXXX` (uppercase alphanumerics). A code has:

- an optional sponsor user
- an optional expiry timestamp
- a single-use constraint (nullified once `used_at` is set)

`ActivationCodeService` generates, validates, and marks codes as used. During registration, if the user provides a valid code it is consumed. If codes are optional and no code was provided, the user receives the `user` role instead of `user-confirmed`.

### Promotion requests

Unsponsored users (role `user`) can request elevation to `user-confirmed` once they meet configurable criteria:

- Minimum comment count (`non_confirmed_comment_threshold`, default 5)
- Minimum time since registration or last rejection (`non_confirmed_timespan`, default 7 days)

The promotion workflow:
1. Dashboard collects the comment count via `CommentPublicApi` and calls `AuthPublicApi::canRequestPromotion()`.
2. If eligible, user submits via `AuthPublicApi::requestPromotion()` which creates a `PromotionRequest` row.
3. Admins/moderators review in `/admin/auth/promotion-requests`.
4. On accept: `RoleService::promoteToConfirmed()` swaps `user` → `user-confirmed`; `PromotionAccepted` event and in-app notification sent.
5. On reject: rejection reason stored; `PromotionRejected` event and in-app notification sent; user must wait before re-applying.

### Compliance (terms + parental authorisation)

`EnsureUserCompliance` middleware (applied on the `compliant` stack) intercepts every authenticated request and redirects to the compliance flow if either condition is unmet:

1. Terms not yet accepted → `/compliance/terms`
2. User is under 15 and parental authorisation not yet verified → `/compliance/parental-authorization`

A PDF upload is accepted via `ComplianceService`, stored on the `private` disk under `parental_authorizations/`. Once the file is stored, the user's `parental_authorization_verified_at` is set, and role assignment proceeds.

The compliance check result is cached in the session under `user_compliance_checked_{userId}` to avoid repeated DB queries on every request.

---

## Architecture Decisions

**No cross-domain foreign keys to `users`.** Migrations in other domains must not declare FK constraints referencing the `users` table. Cross-domain user data is accessed exclusively through `AuthPublicApi`.

**Roles are always eager-loaded.** The `User` model declares `protected $with = ['roles']`. This means every `User` query incurs a `role_user` join. Do not add `withoutEagerLoads()` for roles — the role-check methods depend on the in-memory collection.

**`RoleService` is the only place that mutates roles.** Using `User::assignRole` / `User::removeRole` directly bypasses cache invalidation and event emission. Always go through `RoleService`.

**`PromotionRequestService` receives the comment count from its caller.** Auth cannot call into the Comment domain. The Dashboard or any other consumer is responsible for supplying `$commentCount` when calling `canRequestPromotion()` or `requestPromotion()`.

**`AuthPublicApi::deleteUserById` requires admin/tech-admin role.** The method throws `AuthorizationException` if the caller is not an admin. This check is enforced in the API method itself, not only in middleware, to prevent misuse from non-HTTP code paths.

**`ConfigPublicApi` is resolved lazily in `RoleService` and `PromotionRequestService`.** There is a circular dependency: Auth → Config → FeatureToggle → Auth. To break the cycle, both services call `app(ConfigPublicApi::class)` inside methods instead of injecting it in the constructor.

---

## Cross-Domain Delegation

| Concern | Delegated to | Why |
|---------|-------------|-----|
| Display names | Profile (via `ProfilePublicApi`) | Profile owns user identity; Auth only stores credentials |
| Promotion eligibility comment count | Caller (Dashboard) via `AuthPublicApi` | Auth cannot depend on Comment |
| In-app notifications (promotion result) | Notification (via `NotificationPublicApi`) | Central delivery pipeline |
| Config parameters | Config (via `ConfigPublicApi`) | Centralised feature/parameter store |
| Admin navigation entry | Administration (via `AdminNavigationRegistry`) | Admin layout is owned by Administration |
| Event persistence & audit log | Events (via `EventBus`) | Cross-domain event infrastructure |

---

## Database Tables

| Table | Description |
|-------|-------------|
| `users` | Core user record: email, password hash, `is_active`, compliance timestamps |
| `roles` | Role definitions: `name`, `slug`, optional `description` |
| `role_user` | Many-to-many pivot between users and roles |
| `user_activation_codes` | Invitation codes with sponsor, expiry, and single-use tracking |
| `user_promotion_request` | Promotion requests with status, comment count at time of request, and decision metadata |
| `password_reset_tokens` | Laravel standard password-reset token table |
| `sessions` | Laravel database session driver table |

---

## Public API Surface

### `AuthPublicApi`

The primary interface for other domains.

| Method | Description |
|--------|-------------|
| `getRolesByUserIds(array $userIds)` | Returns `array<userId, RoleDto[]>`, uses role cache |
| `isAuthenticated()` | Whether a user is currently authenticated |
| `isVerified(?Authenticatable $user)` | Whether the user holds `user` or `user-confirmed` |
| `hasAnyRole(array $roles)` | Whether the current user has any of the given role slugs |
| `getUserIdsByRoles(array $roleSlugs, bool $activeOnly)` | User IDs filtered by role(s) |
| `getAllActiveUserIds()` | All active user IDs |
| `getAllRoles()` | Full list of roles as `RoleDto[]` |
| `deleteUserById(int $userId)` | Delete user; requires admin/tech-admin role |
| `deactivateUserById(int $userId)` | Deactivate + terminate sessions; requires admin/tech-admin |
| `activateUserById(int $userId)` | Reactivate user; requires admin/tech-admin |
| `getUsersById(array $userIds)` | Returns `array<userId, {email, isActive}>` |
| `canRequestPromotion(int $userId, int $commentCount)` | Returns `PromotionEligibilityDto` |
| `requestPromotion(int $userId, int $commentCount)` | Submit promotion request; returns `PromotionRequestResultDto` |
| `getPromotionStatus(int $userId)` | Returns `PromotionStatusDto` (pending / rejected / none) |
| `getPendingPromotionCount()` | Count of pending promotion requests (for admin badge) |

### `Roles` constants

`App\Domains\Auth\Public\Api\Roles` — use these instead of hardcoding strings anywhere in the codebase.

### Middleware (public, usable in other domains)

| Class | Alias | Effect |
|-------|-------|--------|
| `CheckRole` | `role` | Redirects to dashboard if user lacks required role(s) |
| `EnsureEmailIsVerified` | — | Redirects to `verification.notice` if email is unverified |
| `EnsureUserCompliance` | `compliant` | Redirects to compliance flow if terms or parental auth is missing |

### DTOs

- `RoleDto` — role id, name, slug, description
- `PromotionEligibilityDto` — eligible flag, criteria breakdown (days/comments required vs elapsed/posted)
- `PromotionRequestResultDto` — success flag + error code constant
- `PromotionStatusDto` — status (pending / rejected / none), rejection reason and date

### Events emitted

All events carry at minimum a `userId`. They are registered with the `EventBus` in `AuthServiceProvider`.

| Event | Trigger |
|-------|---------|
| `UserRegistered` | New user account created |
| `EmailVerified` | User verifies their email address |
| `PasswordChanged` | Password updated or reset |
| `PasswordResetRequested` | Forgot-password link requested |
| `UserLoggedIn` | Successful login |
| `UserLoggedOut` | Logout |
| `UserRoleGranted` | Role added to user |
| `UserRoleRevoked` | Role removed from user |
| `UserDeactivated` | Admin deactivates a user |
| `UserReactivated` | Admin reactivates a user |
| `UserDeleted` | User account permanently deleted |
| `PromotionRequested` | User submits a promotion request |
| `PromotionAccepted` | Admin/moderator accepts promotion |
| `PromotionRejected` | Admin/moderator rejects promotion |

### Config parameters (registered with `ConfigPublicApi`)

| Key (via `AuthConfigKeys`) | Type | Default | Description |
|---------------------------|------|---------|-------------|
| `require_activation_code` | bool | `true` | Whether an activation code is mandatory at registration |
| `non_confirmed_comment_threshold` | int | `5` | Comments needed before a promotion request can be submitted |
| `non_confirmed_timespan` | time (seconds) | `604800` (7 days) | Minimum time since registration (or last rejection) before requesting promotion |

---

## Routes

| Method | URI | Name | Access |
|--------|-----|------|--------|
| GET | `/register` | `register` | Guest |
| POST | `/register` | — | Guest |
| GET | `/login` | `login` | Guest |
| POST | `/login` | — | Guest |
| GET | `/forgot-password` | `password.request` | Guest |
| POST | `/forgot-password` | `password.email` | Guest |
| GET | `/reset-password/{token}` | `password.reset` | Guest |
| POST | `/reset-password` | `password.store` | Guest |
| GET | `/auth/login-intended` | `login.with_intended` | Guest |
| GET | `/compliance/terms` | `compliance.terms.show` | Auth |
| POST | `/compliance/terms` | `compliance.terms.accept` | Auth |
| GET | `/compliance/parental-authorization` | `compliance.parental.show` | Auth |
| POST | `/compliance/parental-authorization` | `compliance.parental.upload` | Auth |
| POST | `/logout` | `logout` | Auth + compliant |
| GET | `/account` | `account.edit` | Auth + compliant |
| PATCH | `/account` | `account.update` | Auth + compliant |
| DELETE | `/account` | `account.destroy` | Auth + compliant |
| GET | `/auth/roles/lookup` | `auth.roles.lookup` | Auth + compliant |
| GET | `/auth/roles/by-slugs` | `auth.roles.by_slugs` | Auth + compliant |
| GET | `/session/heartbeat` | `session.heartbeat` | Auth + compliant |
| GET | `/auth/csrf-token` | `session.csrf` | Auth + compliant |
| POST | `/auth/admin/users/{user}/deactivate` | `auth.admin.users.deactivate` | admin / moderator / tech-admin |
| POST | `/auth/admin/users/{user}/reactivate` | `auth.admin.users.reactivate` | admin / moderator / tech-admin |
| GET | `/admin/auth/users` | `auth.admin.users.index` | admin / tech-admin |
| GET | `/admin/auth/users/export` | `auth.admin.users.export` | admin / tech-admin |
| GET | `/admin/auth/users/{user}/edit` | `auth.admin.users.edit` | admin / tech-admin |
| PUT | `/admin/auth/users/{user}` | `auth.admin.users.update` | admin / tech-admin |
| POST | `/admin/auth/users/{user}/promote` | `auth.admin.users.promote` | admin / tech-admin |
| DELETE | `/admin/auth/users/{user}` | `auth.admin.users.destroy` | admin / tech-admin |
| GET | `/admin/auth/users/{user}/download-authorization` | `auth.admin.users.download-authorization` | admin / tech-admin |
| POST | `/admin/auth/users/{user}/clear-authorization` | `auth.admin.users.clear-authorization` | admin / tech-admin |
| GET | `/admin/auth/promotion-requests` | `auth.admin.promotion-requests.index` | admin / tech-admin / moderator |
| POST | `/admin/auth/promotion-requests/{req}/accept` | `auth.admin.promotion-requests.accept` | admin / tech-admin / moderator |
| POST | `/admin/auth/promotion-requests/{req}/reject` | `auth.admin.promotion-requests.reject` | admin / tech-admin / moderator |
