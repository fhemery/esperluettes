# Secret Gift — participants cannot enrol — request

Found on 2026-07-28 while documenting the activity from its code. Not a
regression: it appears never to have existed.

## The problem

**There is no way for a user to join a Secret Gift activity.** Nothing in the
application creates a `calendar_secret_gift_participants` row:

- no route in `Private/Activities/SecretGift/Http/routes.php` — the five routes
  are all about saving and serving a gift;
- no create call anywhere in the activity's controllers or services;
- only the test helpers insert participants.

Consequences:

- The `preferences` column can never be filled from the UI.
- `ShuffleSecretGiftCommand` has nothing to shuffle unless rows are inserted by
  hand in the database.
- The activity is, in practice, unusable as shipped.

## Why it matters now

The Calendar README claimed "participants enroll by visiting the activity page
while it is Active". That was false, and it is the kind of claim that hides a
missing feature for a long time — it has been corrected, and this row exists so
the gap itself is not forgotten.

## Open questions for REFINE

- Is enrolment implicit (visiting the page while Active enrols you, as the old
  README claimed) or explicit (a button, a confirmation)?
- What are `preferences` for, and when does the participant fill them in?
- Which activity states allow enrolling and un-enrolling?

## Constraints

- Role restrictions on the activity must gate enrolment.
- The shuffle is a manual Artisan command run by an admin after registration
  closes; enrolment must be closed by then, or the assignment set is unstable.

## Merged from `calendar-subscription/` (2026-08-07)

That task proposed a generic base-Calendar enrolment mechanism (driven by the
unused `requires_subscription` / `max_participants` columns on
`calendar_activities`). REFINE on it established that Jardino and Quote
Contest are both open participation and never consult those columns, and
collaborative-stories (future) won't need subscription in v1 either — so
Secret Gift is the *only* real consumer. Building a generic mechanism for one
consumer is speculative; decided to fold that task's scope in here instead and
build enrolment as a Secret-Gift-owned concern on its existing
`calendar_secret_gift_participants` table. `calendar-subscription/` is closed
as absorbed. Its remaining open questions, now in scope here:

- Cap enforcement: hard refusal at `max_participants`, or a waiting list?
- Who can see the participant list: everyone, enrolled users only, or admins
  only?
- What happens to a participant row when the user is deactivated or deleted?
  (Calendar has no cleanup listeners for either event today — Jardino goals
  aren't cleaned up either, so there's no existing convention to follow inside
  Calendar; Quote and ReadList soft-delete/hard-delete on those events
  respectively, if a precedent is wanted.)

The `requires_subscription` / `max_participants` base columns are dead code
once this ships (SecretGift's own logic won't read them) — DESIGN should
decide whether to drop them.
