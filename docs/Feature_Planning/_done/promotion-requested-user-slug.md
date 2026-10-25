# Add requester slug to auth.promotion.requested notification

**Status:** DONE — 2026-10-25 · **Domain(s):** `Auth`

## What it does

The staff-only `auth.promotion.requested` notification now also stores the
requester's profile slug as `user_slug` in `content_data`, next to `user_name`,
so the Discord bot can link to `/profile/<slug>`. The Discord pending endpoint
forwards `content_data` verbatim, so `data.user_slug` reaches the bot with no
Discord change. In-app text is unchanged. Old rows are not backfilled.

## Key behaviour

- Recipients unchanged: active moderator / admin / tech-admin, minus the requester.
- The slug is a copy taken at send time (like `user_name`); not updated if the slug changes later.
- `fromData()` defaults a missing `user_slug` to `''`, so legacy rows rebuild and render as before.
- **Consumers (the Discord bot) must tolerate a missing `user_slug` on rows created before this change** — the endpoint returns stored data as-is, so old rows have no key at all (the `''` default only applies when PHP rebuilds the content).
- **No-profile caveat:** the service passes `$profile?->slug ?? ''`, but `NotificationPublicApi` rejects a `sourceUserId` with no profile (`invalid_source_user`), so in practice a requester without a profile produces no notification at all (the exception is caught and `report()`ed; the request itself still succeeds). The empty-slug path is only reachable with a mocked `ProfilePublicApi`, which is what the test does. The spec (§4.1.4, "slug empty and the notification is still sent") does not match the code here.

## Where the code lives

| Concern | Path |
|---------|------|
| Notification content | `app/Domains/Auth/Public/Notifications/PromotionRequestedNotification.php` (`userSlug = ''` trailing ctor arg) |
| Sender | `app/Domains/Auth/Private/Services/PromotionRequestService.php` → `notifyStaffOfNewRequest()` |
| Tests | `app/Domains/Auth/Tests/Feature/PromotionRequestedStaffNotificationTest.php` (new payload, legacy rebuild, mocked no-profile) |
| Discord passthrough (untouched) | covered by `app/Domains/Discord/Tests/Feature/Api/NotificationsApiTest.php`, "data payload" block |
| Catalog | `docs/notification-types.md` regenerated at WRAP (`notifications:export-types-doc`) |

## Extension points used

None new; existing Notification content type, Shared `ProfilePublicApi`. No deptrac edge.

## Decisions worth remembering

- Expose the slug only (`user_slug`); the bot builds the URL. No URL field.
- Defaulted trailing constructor arg rather than required, to keep legacy `fromData` and any caller valid.
- No backfill (from the request).
- Assumptions made without asking (all reversible) were logged in the task's
  DECISIONS.md: slug-not-URL, in-app text unchanged, stay on branch
  `fix/misc-small-fixes`, defaulted arg, mocked no-profile test.

## Verification

VERIFY was intentionally skipped: backend-only change with no UI, covered by
the Pest tests above plus the existing Discord endpoint test that stored data
passes through unchanged. The plan's visual checklist (manual API call to
`/api/discord/notifications/pending`) was not run.

## Not done

- Non-goals: backfilling old notifications; linking the name in the in-app text.
- **Follow-up outside this repo:** the Discord bot must be updated to consume
  `data.user_slug` (and fall back when absent). Not tracked in this backlog.
- No e2e spec was written, so none to retire.
- WRAP moved the file-local `submitPromotionRequestFor` Pest helper into
  `app/Domains/Auth/Tests/helpers.php`.
