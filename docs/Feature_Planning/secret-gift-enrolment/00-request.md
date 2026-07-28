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
- Does this wait for `calendar-subscription/`, which introduces generic
  enrolment for **any** activity with `requires_subscription`? Secret Gift may
  be its first real consumer rather than needing its own mechanism —
  **settle this before designing anything**.

## Constraints

- Role restrictions on the activity must gate enrolment.
- The shuffle is a manual Artisan command run by an admin after registration
  closes; enrolment must be closed by then, or the assignment set is unstable.
