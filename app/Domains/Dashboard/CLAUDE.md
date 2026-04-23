- README: [app/Domains/Dashboard/README.md](README.md)

## Public API

This domain has no Public API class. It is a consumer-only domain; no other domain calls into it.

## Events emitted

This domain emits no events.

## Listens to

This domain registers no event listeners. It reads data synchronously through Public API calls at request time.

## Non-obvious invariants

- The `PromotionStatusComponent` performs its own API calls (`AuthPublicApi::canRequestPromotion`, `AuthPublicApi::getPromotionStatus`, `CommentPublicApi::countRootCommentsByUser`). Do not pass these values down from the controller — the component is intentionally self-contained.

- The promotion request route (`POST /dashboard/promotion/request`) is restricted to the `user` role only. Confirmed users (`user-confirmed`) must not be able to reach this endpoint; the role middleware enforces this.

- The `calendar.enabled` toggle is scoped to the `calendar` module: the call is `ConfigPublicApi::isToggleEnabled('enabled', 'calendar')`. Using a bare toggle name without the module scope will silently return the wrong value.

- `WelcomeComponent` fetches data in its constructor via `loadData()`. Errors are caught and surfaced through `$this->error` rather than thrown. The blade view must handle the case where `$error` is non-null and other properties are null. `PromotionStatusComponent` follows the same pattern.

- No FK constraints to `users` exist in this domain (it owns no tables). Cross-user data is accessed exclusively via Public APIs.
