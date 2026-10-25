# Add requester slug to auth.promotion.requested notification — architecture

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Auth domain only (`PromotionRequestedNotification` content class and
`PromotionRequestService::notifyStaffOfNewRequest`).

## 2. Data model

No table. The notification's `content_data` JSON gains a `user_slug` key.

## 3. PHP architecture

- `PromotionRequestedNotification` gets a trailing `string $userSlug = ''`
  constructor argument. `toData()` adds `user_slug`; `fromData()` reads it with
  an empty-string default, so rows stored before the change still rebuild.
- `PromotionRequestService` passes `$profile?->slug ?? ''` from the
  `ProfileDto` it already fetches through the Shared `ProfilePublicApi`.
- The Discord endpoint forwards `toData()` verbatim, so `data.user_slug` is
  exposed with no Discord change.
- `display()` is unchanged.

## 4. Frontend architecture

N/A.

## 5. Deptrac

No new edge (`ProfilePublicApi` is already used by the service).

## 6. Testing strategy

Update the existing `PromotionRequestedStaffNotificationTest`: `toData()` holds
`user_slug`; content rebuilt from legacy data without the key yields an empty
slug and still renders; a request from a user without a profile still notifies.
Add the Discord endpoint payload assertion if a test for it exists nearby.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Constructor arg required vs defaulted | required; default `''` | default `''` last | keeps legacy `fromData` and any other caller valid |
