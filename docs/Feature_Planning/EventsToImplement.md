# Events To Implement

Last updated: 2025-09-12

This document enumerates the domain events we plan to emit across the application, following the conventions defined in `app/Domains/Events/README.md` and the design notes in `docs/Feature_Planning/Events.md`.

Conventions:
- Names are `Domain.EventName` in PascalCase, past tense for facts (e.g., `Auth.UserRegistered`).
- Implement events under `app/Domains/<Domain>/Events/*`.
- Emit from services (not controllers), via `App\Domains\Events\PublicApi\EventBus`.
- Mark user-triggered events with `AuditableEvent` when we want full context captured.
- Use `EventBus::registerEvent()` in each domain's ServiceProvider to map names to DTO classes.
- Subscribe in the consuming domain’s ServiceProvider with `EventBus::subscribe()`.

Process:
1) Validate the event name and event class name, the data, the consumers.
2) Create class under the emitting domain `Events/` folder. (using translations for summary)
3) Register with `EventBus::registerEvent()` in the domain’s `ServiceProvider::boot()`.
4) Emit from the relevant domain service once the operation succeeds.
5) Write tests to ensure event is emitted properly in all cases.
6) Create consumers and subscribe in their `ServiceProvider`.
7) Write tests to ensure consumer is called properly and processing all cases.
8) Mark as [I] here once implemented.

Legend:
- [I] Implemented
- [A] AuditableEvent
- [C] Critical (emit/handle synchronously)

## Auth
- [I][A][C] `Auth.UserRegistered` — emitted on successful user registration.
  - Producers: `Auth` registration flow.
  - Consumers: `Profile` (create profile), future `Activity/Notifications`.
- [I][A] `Auth.EmailVerified` — on email verification.
  - Producers: `Auth` email verification controller/service.
  - Consumers: `Admin` audit, `Profile` (clears cache).
  - Summary: `UserId = <id> : Email vérifié`
- [I][A] `Auth.PasswordResetRequested` — when reset link is requested (audit sensitive).
  - Producers: `Auth` password reset request flow.
  - Consumers: `Admin` audit/monitoring.
- [I][A] `Auth.PasswordChanged` — after password update.
  - Producers: `Auth` password update flow.
  - Consumers: `Admin` audit
- [I][A] `Auth.UserLoggedIn` — successful login.
  - Producers: login flow.
  - Consumers: `Admin` audit, analytics.
- [I][A] `Auth.UserLoggedOut` — explicit logout.
  - Producers: logout flow.
  - Consumers: `Admin` audit.
- [A][C] `Auth.UserDeleted` — user deleted (hard delete).
  - Producers: `Auth` delete service.
  - Consumers: `Story` (to clean up the stories), `Comment` (to clean up the comments), `Profile` (to clean up the profile).

## Profile
- [I][A] `Profile.DisplayNameChanged` — display name updated.
  - Producers: `Profile` edit service.
  - Consumers: `Admin` audit, `Story` projections, `Comment` display cache.
- [I][A] `Profile.AvatarChanged` — avatar updated.
  - Producers: `Profile` edit service (avatar upload/crop).
  - Consumers: `Admin` audit, `Comment`/`Story` avatar caches.
- [I][A] `Profile.BioUpdated` — biography or networks updated.
  - Producers: `Profile` edit service.
  - Consumers: `Admin` audit.

## Story
- [I][A] `Story.Created` — story created (draft or published depending on workflow).
  - Producers: `Story` create service.
  - Consumers: `Admin` audit, `Activity/Notifications`, projections.
- [I][A] `Story.Updated` — metadata updated (title/summary/tags...).
  - Producers: `Story` update service.
  - Consumers: projections, caches, search indexing.
- [I][A] `Story.Deleted` — story deleted (soft delete).
  - Producers: `Story` delete service.
  - Consumers: projections cleanup, admin audit.
- [I][A] `Story.VisibilityChanged` — public/private/listed toggles.
  - Producers: `Story` settings service.
  - Consumers: search indexing, caches.

## Chapter
- [I][A] `Chapter.Created` — chapter created (typically draft).
  - Producers: `Chapter` create service.
  - Consumers: stats, notifications.
- [I][A] `Chapter.Updated` — chapter metadata/content updated.
  - Producers: `Chapter` update service.
  - Consumers: projections (word count), search indexing.
- [I][A] `Chapter.Published` — chapter published.
  - Producers: `Chapter` publish service.
  - Consumers: notifications to followers, stats, sitemap.
- [I][A] `Chapter.Unpublished` — chapter unpublished.
  - Producers: `Chapter` unpublish service.
  - Consumers: sitemap/update caches.
- [I][A] `Chapter.Deleted` — chapter deleted (soft delete).
  - Producers: `Chapter` delete service.
  - Consumers: projections cleanup.

## Comment
- [I][A] `Comment.Posted` — new comment posted.
  - Producers: `Comment` post service.
  - Consumers: notify story authors, moderation pipeline, counters.
- [I][A] `Comment.Edited` — comment edited.
  - Producers: `Comment` edit service.
  - Consumers: moderation pipeline, counters.

## News
- [A] `News.Published`
- [A] `News.Updated`
- [A] `News.Deleted`
  - Producers: `News` CRUD services.
  - Consumers: sitemap, notifications, audit.

## StaticPage
- [A] `StaticPage.Published`
- [A] `StaticPage.Updated`
- [A] `StaticPage.Deleted`
  - Producers: `StaticPage` CRUD services.
  - Consumers: sitemap, audit.

## StoryRef
- [A] `StoryRef.Added` — reference/link created (story-to-story or external).
- [A] `StoryRef.Removed` — reference removed.
  - Producers: `StoryRef` manage service.
  - Consumers: projections, link integrity checks.

## Admin
- [A] `Admin.RoleGranted` — role granted to a user.
- [A] `Admin.RoleRevoked` — role revoked.
- [A] `Admin.FeatureToggled` — feature flag toggled.
- [A] `Admin.UserBanned` — user banned.
- [A] `Admin.UserUnbanned` — user unbanned.
  - Producers: `Admin` actions.
  - Consumers: security audit, enforcement services, cache invalidation.

---

## Next steps
- For each event above:
  1) Create DTO class under the emitting domain `Events/` folder.
  2) Register with `EventBus::registerEvent()` in the domain’s `ServiceProvider::boot()`.
  3) Emit from the relevant domain service once the operation succeeds.
  4) Identify consumers and subscribe in their `ServiceProvider`.
  5) Mark as [I] here once implemented and add links to PRs/tests.

Open questions / to validate together:
- Which events should be considered Critical [C] beyond `Auth.UserRegistered`?
- Any events too noisy for auditing that we want to keep non-auditable?
- Align `Story` and `Chapter` published/unpublished states with our current workflow exactly (draft vs published transitions).
