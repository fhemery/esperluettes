# Moderation — report refusals: 403 handling and moderator-only accounts — request

*Leftover from `annotations-v4` VERIFY (findings 2 and 3).*

## What I want

Two gaps in how the report flow treats a refused report.

1. **403 in the report modal sends the user to `/login`.**
   `reportForm.submitReport` in
   `app/Domains/Moderation/Private/Resources/js/moderation.js` redirects to
   `/login` on any 401 **or 403** (both in the form fetch and the submit).
   Since `annotations-v4`, a topic's `ReportGuardInterface` can refuse a report
   with 403 (`{success: false, message}`) for a logged-in user — e.g. the root
   comment was trashed while the annotation pop-up was open, so the
   `can_report` hint and the server guard disagree. The user is bounced to the
   login page instead of seeing the error in the modal. 401 should keep
   redirecting; 403 should show `message`.
2. **A moderator-only account sees « Signaler » but cannot submit.** The
   `moderation/report*` routes (`app/Domains/Moderation/Private/routes.php`)
   are gated to `user` / `user-confirmed`. The annotation `can_report` hint
   is true for moderators (spec: shown "as on comments"), and the server guard
   accepts them, but an account holding only `moderator` (no `user*` role) is
   refused by the route gate. Seeded moderators hold both roles, so e2e passes.
   Decide: add moderator/admin to the route gate, or hide the button for them
   everywhere (check what the comment-list report button does today).

## Why

Both make « Signaler » look broken to a legitimate user.

## Constraints or ideas I already have

- Not reproduced in a browser: (1) needs a hint/guard disagreement, (2) needs
  a moderator account without `user*` roles.
- Change is in Moderation, so it applies to every topic, not just `annotation`.

## Explicitly out of scope

- Changing who may report what on the `annotation` topic.
