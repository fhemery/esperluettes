# Calendar — activity subscription and participant limits — request

Migrated from `Calendar.md` §Deferred Features. The Calendar core is live — see
[`../calendar/README.md`](../calendar/README.md).

## What I want

Let an activity require explicit enrollment, cap the number of participants, and
show who has enrolled.

## Why

`requires_subscription` and `max_participants` are stored on `activities`, are
editable in the admin form, and **enforce nothing**. An admin can set both today
and get no behaviour at all — the flag is a promise the app does not keep.

Three deferred items from the original spec are really one feature and are
grouped here:

- **Subscription management** — the flag exists, the enrolment UI and logic do
  not.
- **Participant limits** — `max_participants` exists, nothing enforces it.
- **Participant lists** — no public participant view, deferred until enrolment
  exists.

## Constraints or ideas I already have

- Activity state is derived from dates, never stored — enrolment must respect
  the same model. Enrolling presumably makes sense in `preview` and `active`
  states, not in `draft`, `ended` or `archived`.
- Role restrictions already gate participation (`['user-confirmed']` and
  similar); enrolment must not bypass them.
- Activity types are plugins (`CalendarRegistry`). Enrolment is a base-activity
  concern, so it should work without each type implementing anything — but the
  types may need to read the participant list.
- Existing activities (Jardino, SecretGift) use implicit participation and must
  keep working untouched.

## Open questions for REFINE

- Can a user un-enrol, and until when?
- What happens at the cap — hard refusal, or a waiting list?
- Who can see the participant list: everyone, enrolled users, admins only?
- What happens to enrolments when an activity is archived or deleted, and when a
  user is deactivated or deleted?

## Explicitly out of scope

- Notifications on activity state changes — separate backlog task
  (`calendar-notifications/`).
- Any central reward system. The original spec is explicit: rewards are handled
  per activity type.
