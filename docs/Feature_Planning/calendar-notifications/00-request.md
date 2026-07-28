# Calendar — activity state-change notifications — request

Migrated from `Calendar.md` §Deferred Features. The Calendar core is live — see
[`../calendar/README.md`](../calendar/README.md).

## What I want

Tell users when an activity they can take part in changes state — most
obviously when it opens for participation, and when it is about to close.

## Why

Activities are time-bound and their state is derived from dates, so nothing ever
announces a transition. A user who does not visit the site on the right day
simply misses the activity. The original spec deferred this as "no email/push
notifications for activity state changes initially"; the Notification domain
has since been built, so it is now cheap.

## Constraints or ideas I already have

- State is **derived from dates, never stored, and there is no cron** — this is
  a deliberate architectural decision of the Calendar domain. A notification
  needs a transition *event*, which by definition does not exist yet. **How the
  transition is detected is the central design question of this task** and it
  must not force stored state on the domain if that can be avoided.
- Notifications go through the Notification domain, with Discord delivery
  available for users who opted in.
- Role restrictions decide who is eligible — an ineligible user must not be
  notified.

## Open questions for REFINE

- Which transitions are worth a notification: preview → active, active → ending
  soon, active → ended, or only some?
- Who is notified — every eligible user, or only those who showed interest
  (which may mean waiting for `calendar-subscription/`)?
- Does the user get a setting to turn these off?

## Explicitly out of scope

- Enrolment and participant limits — separate backlog task
  (`calendar-subscription/`).
