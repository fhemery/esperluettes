# Add requester slug to auth.promotion.requested notification — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Carry `user_slug` in the promotion-requested notification | S | — | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

No checkpoint row: the change is additive (a defaulted trailing constructor
argument and one extra payload key); existing consumers are covered by the
existing tests.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Carry `user_slug` in the promotion-requested notification

**Goal.** New `auth.promotion.requested` notifications store the requester's
profile slug as `user_slug`, legacy rows without it still rebuild and render.

Needs architecture §3 (PHP) and §6 (testing). Nothing earlier to build on.

**Deliverables.**
- `app/Domains/Auth/Public/Notifications/PromotionRequestedNotification.php`
  - constructor becomes
    `__construct(public readonly string $userName, public readonly string $userSlug = '')`;
  - `toData()` returns `['user_name' => …, 'user_slug' => $this->userSlug]`;
  - `fromData()` adds `userSlug: (string) ($data['user_slug'] ?? '')`;
  - `display()` unchanged.
- `app/Domains/Auth/Private/Services/PromotionRequestService.php`,
  `notifyStaffOfNewRequest()`: pass
  `userSlug: $profile?->slug ?? ''` alongside the existing
  `$profile?->display_name ?? ''` (`ProfileDto::$slug` exists in
  `app/Domains/Shared/Dto/ProfileDto.php`). Accepted/rejected notifications are
  not touched.
- No Discord change: the Discord pending endpoint returns `content_data`
  verbatim (its type-agnostic contract is already tested in
  `app/Domains/Discord/Tests/Feature/Api/NotificationsApiTest.php`, "data
  payload" describe block), so no new Discord test is added.

**Tests.** In
`app/Domains/Auth/Tests/Feature/PromotionRequestedStaffNotificationTest.php`:
- Update `it('notifies other active staff when a promotion request is submitted')`:
  the `toData()` expectation becomes
  `['user_name' => 'Alice', 'user_slug' => <alice's profile slug>]` — read the
  actual slug from the requester's profile (e.g. via `ProfilePublicApi::getPublicProfile`)
  rather than hard-coding it if unsure what the `alice()` helper produces.
- New `it('rebuilds legacy content without user_slug as an empty slug')`:
  `PromotionRequestedNotification::fromData(['user_name' => 'Alice'])` →
  `userSlug === ''`, `toData()` holds `'user_slug' => ''`, and `display()`
  still contains `Alice` and the admin promotion-requests URL.
- New `it('still notifies staff with an empty slug when the requester has no profile')`:
  delete (or never create) the requester's profile row before submitting, then
  the notification exists with `content_data['user_slug'] === ''` and
  `user_name === ''`. If removing the profile is not possible cheaply in a
  feature test, bind a `ProfilePublicApi` mock returning `null` for
  `getPublicProfile` instead.

**Acceptance.**
- ✅ A promotion request by a `user` creates an `auth.promotion.requested`
  notification whose `content_data` is `{user_name, user_slug}` with the
  requester's slug.
- ✅ `PromotionRequestedNotification::fromData(['user_name' => 'X'])` yields an
  empty slug and renders the same in-app text as before.
- ✅ A requester without a profile still produces the staff notification, slug `''`.
- ✅ Existing tests in `PromotionRequestedStaffNotificationTest`,
  `PromotionEventsTest` and `StaffNotificationRoleLifecycleTest` pass unchanged
  except for the updated `toData()` expectation.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Backend-only: no screen changes. Checks are done via the API and tests; the
in-app row is only a non-regression glance.

| Surface | Check | OK? |
|---------|-------|-----|
| `GET /api/discord/notifications/pending` (Discord bot token) after a fresh promotion request by a `user` | item for `auth.promotion.requested` has `data.user_name` and `data.user_slug`, slug matches `/profile/<slug>` of the requester | |
| Same endpoint, notification created before the change (legacy row, if any in the dev DB) | item returned without `user_slug` and no error (consumers tolerate absence) | |
| `content_data` of the new row in `notifications` (tinker / DB) | JSON is `{"user_name": …, "user_slug": …}` | |
| In-app notifications page, as a moderator/admin | promotion-request notification text unchanged (name + link to admin requests page), no extra text | |
| Tests | `PromotionRequestedStaffNotificationTest` green (covers new, legacy and no-profile cases) | |

## Open items

- Phase 1: what slug the `alice()` test helper gives the profile — read it from
  the profile rather than assuming `alice`.
- Phase 1: the cheapest way to get a requester with no profile in a feature
  test (delete the profile row vs mock `ProfilePublicApi`); either is acceptable.
