# Auth Domain — Agent Instructions

- README: [app/Domains/Auth/README.md](README.md)

## Public API

- [AuthPublicApi](Public/Api/AuthPublicApi.php) — role lookups (with cache), user queries, activate/deactivate/delete user, promotion request lifecycle
- [Roles](Public/Api/Roles.php) — role slug constants (`USER`, `USER_CONFIRMED`, `ADMIN`, `TECH_ADMIN`, `MODERATOR`)
- [AuthConfigKeys](Public/Support/AuthConfigKeys.php) — config parameter key constants for this domain

## Middleware (usable by other domains)

| Class | Registered alias | Effect |
|-------|-----------------|--------|
| `CheckRole` | `role` | Redirect to dashboard if current user lacks specified role(s) |
| `EnsureEmailIsVerified` | — | Redirect to `verification.notice` if email unverified |
| `EnsureUserCompliance` | `compliant` | Redirect to compliance flow (terms / parental auth) when user is not fully compliant |

## Events emitted

| Event | When |
|-------|------|
| `UserRegistered` | New account created |
| `EmailVerified` | User clicks verification link |
| `PasswordChanged` | Password updated or reset |
| `PasswordResetRequested` | Forgot-password form submitted |
| `UserLoggedIn` | Successful login |
| `UserLoggedOut` | Logout |
| `UserRoleGranted` | Role added to user |
| `UserRoleRevoked` | Role removed from user |
| `UserDeactivated` | Admin deactivates account |
| `UserReactivated` | Admin reactivates account |
| `UserDeleted` | Account permanently deleted |
| `PromotionRequested` | User submits promotion request |
| `PromotionAccepted` | Admin/moderator accepts promotion |
| `PromotionRejected` | Admin/moderator rejects promotion |

## Listens to

This domain registers no event listeners. It is a pure emitter; cross-domain reactions to its events are handled inside the consuming domains.

## Non-obvious invariants

**Always use `RoleService` to mutate roles, never call `User::assignRole` / `User::removeRole` directly.** `RoleService` invalidates the role cache (`auth:user_roles:{userId}`, TTL 10 min) and emits `UserRoleGranted` / `UserRoleRevoked`. Bypassing it silently leaves stale cache and no audit trail.

**Roles are always eager-loaded on `User`.** `protected $with = ['roles']` is set on the model. Do not call `withoutEagerLoads()` for roles — `hasRole()`, `isConfirmed()`, and `isOnProbation()` all operate on the in-memory collection.

**`PromotionRequestService` and `RoleService` resolve `ConfigPublicApi` lazily via `app()`.** There is a circular dependency (Auth → Config → FeatureToggle → Auth). Injecting `ConfigPublicApi` in the constructor will cause an infinite boot loop. Keep the lazy resolution in place.

**`AuthPublicApi::deleteUserById` / `deactivateUserById` / `activateUserById` perform their own role checks.** These methods throw `AuthorizationException` if the caller is not admin/tech-admin. Do not skip this check by calling `UserService` directly.

**`PromotionRequestService::checkEligibility` / `requestPromotion` expect the comment count from the caller.** Auth has no dependency on the Comment domain. Dashboard (or any other consumer) must supply `$commentCount` by calling `CommentPublicApi` itself before calling these methods.

**`EnsureUserCompliance` skips certain routes.** The bypass list is hardcoded in `EnsureUserCompliance::$except`. When adding new routes that must be reachable before compliance is complete (e.g. a new logout variant), add the route name there.

**Activation code determines initial role, not the middleware.** After email verification (and parental auth if applicable), `RoleService::assignRolesBasedOnActivationCode()` is called from `VerifyEmailController`. If `require_activation_code = true`, the user always gets `user-confirmed`. If `false`, users without a code get `user`.

**`user_promotion_request` table name is singular (not `promotion_requests`).** The `PromotionRequest` model declares `protected $table = 'user_promotion_request'`. Do not assume the default table name.

**Session termination on deactivation.** `UserService::deactivateUser` deletes all rows in `sessions` for that user immediately. This is the mechanism by which a deactivated user is kicked out in real time.

**Parental-authorisation PDFs are stored on the `private` disk** at `parental_authorizations/authorization-{userId}.pdf`. Use `ComplianceService` to store, check, download, or clear these files — never access the disk directly.

## Registry integrations

- **ConfigPublicApi** (`Config` domain) — registers three parameters: `require_activation_code` (bool), `non_confirmed_comment_threshold` (int, default 5), `non_confirmed_timespan` (time in seconds, default 604800).
- **NotificationFactory** (`Notification` domain) — registers `PromotionAcceptedNotification` and `PromotionRejectedNotification` content types.
- **AdminNavigationRegistry** (`Administration` domain) — registers the promotion-requests admin page under the `auth` group with a badge icon (visible to admin, tech-admin, moderator).
- **EventBus** (`Events` domain) — all 14 public events are registered during `AuthServiceProvider::boot`.
- **Router alias** — `role` middleware alias is registered globally pointing to `CheckRole`.
