# Add requester slug to auth.promotion.requested notification — functional specification

## 1. Overview

The `auth.promotion.requested` staff notification also carries the slug of the
user who made the request, so the Discord bot can link to that user's profile.
Only new notifications carry it.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Demande de promotion | a `user` asking to become `user-confirmed` |
| Slug du demandeur | the requester's profile slug, as in `/profile/<slug>` |

## 3. Roles & visibility

Unchanged: recipients are the staff roles already targeted. The slug is public
profile data; nothing new is exposed to non-staff.

## 4. Functional requirements

### 4.1 New request
1. A user submits a promotion request.
2. Staff are notified as today; the stored notification content now also holds the requester's slug.
3. The Discord endpoint exposes it in the notification's `data` as `user_slug`, next to `user_name`.
4. If the requester's profile cannot be found, the slug is empty and the notification is still sent.

### 4.2 Existing notifications
Not backfilled. Old rows have no slug and keep rendering as before; consumers must tolerate a missing `user_slug`.

## 5. Lifecycle

N/A — read-only copy of a slug at notification time (like `user_name`; not updated if the slug later changes).

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | unchanged |
| Visibility / privacy | slug is public profile data |
| Settings | N/A |
| Notifications | this is the change; in-app text unchanged |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A |
| i18n | no new text |
| Mobile / Accessibility | N/A |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Backfill existing notifications? | No (request) |

## 8. Out of scope

- Backfilling old notifications.
- Changing the Discord bot itself.
- Changing the in-app notification wording or linking the name to the profile in the in-app text.

## 9. Open questions

None blocking.
