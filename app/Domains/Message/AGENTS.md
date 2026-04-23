# Message Domain — Agent Instructions

- README: [app/Domains/Message/README.md](README.md)

## Public API

This domain has no Public API class. It exposes no services to other domains. The `MessageIconComponent` is a Blade component registered under the `message::` namespace and can be embedded in any layout, but it is not a formal Public API.

## Events emitted

This domain emits no domain events.

## Listens to

This domain registers no event listeners.

## Non-obvious invariants

**Delivery, not message, is the unit of authorization.** Authorization checks compare `delivery->user_id` to `request->user()->id`. Never authorize against the `Message` model directly — two users may hold deliveries for the same message, each owning only their own copy.

**Unread count is always read from `UnreadCounterService`, never from a raw query in views.** The cache key is `message_unread_count_{userId}`. Invalidate via `UnreadCounterService::invalidateCache($userId)` whenever `is_read` changes or a new delivery is created. Missing an invalidation causes stale counts to persist for up to 5 minutes.

**Batch insert bypasses model events.** `MessageDispatchService::dispatch()` uses `MessageDelivery::insert([...])` for performance. This does not fire Eloquent model events (`created`, etc.). If you add observers or listeners on `MessageDelivery`, they will not fire for newly dispatched deliveries.

**Deduplication must happen before `insert()`.** `resolveRecipients()` calls `array_unique()`. If you add a new resolution path (e.g., group membership), apply deduplication before the final merge — the `UNIQUE(message_id, user_id)` constraint will throw on duplicates.

**Content is sanitized in the controller, not the model.** `Purifier::clean($content, 'strict')` is called in `MessageController::store()` before passing content to the service. If you add another dispatch path (e.g., a console command), you must sanitize content there too — the `MessageDispatchService` does not sanitize.

**Feature toggle gates the nav icon, not the routes.** The `messageactive` toggle (domain `message`, name `active`) controls whether `MessageIconComponent` renders. Routes remain accessible regardless of the toggle state. A user who knows the URL can still reach their inbox even when the toggle is off.

**`sent_by_id` and `user_id` have no FK to `users`.** Do not add FK constraints from these columns to `users`. Cross-domain FK to Auth is prohibited. Resolve sender display names via `AuthPublicApi` or `ProfilePublicApi` at query time.

**Compose is role-gated at the routing level.** The `role:admin,tech-admin,moderator` middleware on the compose/store routes is the authoritative gate. `ComposeMessageRequest::authorize()` returns `true` unconditionally and delegates to the route middleware.

## Feature toggles registered

| Toggle name | Domain scope | Default | Effect |
|-------------|-------------|---------|--------|
| `active` | `message` | (none set in code) | Controls whether `MessageIconComponent` renders in the navigation bar |
