# Role-gated notification preferences (moderator/admin) — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Moderators, admins and tech-admins have no in-app or Discord notification when
a new report is filed or a new promotion request is submitted — only navbar
badges. This feature creates those two staff notifications and lets only the
relevant roles see (and change) their preference rows, so they can opt in to
Discord and opt out of the website inbox.

The same “send to these roles, optionally excluding the actor” shape will be
reused later. This task ships only the two staff types; the recipient rule is
part of the product so a later type can declare it without inventing a second
mechanism.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| **Staff** | A user who currently has at least one of `moderator`, `admin`, `tech-admin`. Same trio as the navbar badges. |
| **Staff notification** | One of the two new types: nouveau signalement, nouvelle demande de promotion. |
| **Préférences de notification** | The existing Settings tab that lists types and per-channel toggles (site / Discord). |
| **Canal site** | In-app inbox. Default on for these types; staff can opt out. |
| **Canal Discord** | Discord DM. Default off; staff can opt in. Unlinked account: column warning, silent skip at send (unchanged). |
| **Acteur** | The user who filed the report or submitted the promotion request. Never a recipient of that send. |

## 3. Roles & visibility

| Role | Can see the two new preference rows | Receives the two new notifications |
|------|-------------------------------------|------------------------------------|
| Guest | No (Settings requires auth) | No |
| `user` (non-confirmed) | No | No |
| `user-confirmed` | No | No |
| Author / co-author | N/A — not an authorship feature | N/A |
| Moderator | Yes | Yes, if currently staff and not the actor, and the channel is enabled for them |
| Admin | Yes | Same |
| Tech-admin | Yes | Same |

Confirmed vs non-confirmed does not matter: only current staff roles do.

The existing requester-facing rows (« Ma demande de promotion a été
acceptée / refusée ») stay visible to every authenticated user. They are not
staff alerts.

## 4. Functional requirements

### 4.1 Preference rows (staff only)

1. A staff member opens Préférences → Notifications.
2. In the existing group « Promotions & modération » they see two extra rows:
   - Un nouveau signalement a été déposé
   - Une nouvelle demande de promotion a été déposée
3. Each row has a site toggle (default on, opt-out) and a Discord toggle
   (default off, opt-in). Discord unlinked: same warning as every other type.
4. Saving persists those choices for that user. A later visit shows them.
5. A non-staff authenticated user never sees those two rows. The rest of the
   tab is unchanged. Submitting preferences must not let a non-staff user
   create or change a stored choice for a staff type (hiding the row in the
   page is not enough).

### 4.2 New report

1. Anyone who can file a report does so (unchanged).
2. Every **currently** active staff member except the reporter receives one
   notification of type « nouveau signalement ».
3. Site channel: delivered unless that staff member opted out.
4. Discord channel: delivered only if they opted in (and the account is
   linked; otherwise skipped as today).
5. Body (French): a short line, the reporter’s display name, and a link to
   the same moderation-reports queue as the navbar badge
   (`moderation.admin.moderation-reports.index`). No report reason, topic, or
   private note.
6. If the reporter is the only staff member, nobody is notified. The badge
   still updates.
7. One notification per report. No digest.

### 4.3 New promotion request

Same flow as §4.2, for a new promotion request:

- Recipients: current active staff except the requester.
- Body: short line, requester display name, link to
  `auth.admin.promotion-requests.index`.
- One notification per request. No digest.

### 4.4 Reusable recipient rule

A notification type may declare a set of roles that are allowed to **see its
preference row** and to **be selected as recipients**. Dispatch to that type
targets users who currently have at least one of those roles, optionally
excluding a given actor, then applies per-user channel preferences as today.

This task uses that rule for the two staff types (`moderator`, `admin`,
`tech-admin`, exclude actor). Later types may declare a different role set.
It is **not** “every user who may see a given Calendar activity” — that is a
different audience (see §8 and the `calendar-notifications/` leftover).

### 4.5 Navbar badges

Unchanged. Badges and notifications are complementary.

## 5. Lifecycle

| Event | What happens |
|-------|----------------|
| Staff member demoted (loses last staff role) | Extra rows disappear. They stop receiving immediately (role is checked at send time). Stored Discord/site choices for the staff types are **kept**, so a later re-promotion restores them. |
| Staff member re-promoted | Rows reappear with the stored choices (or defaults if they never set any). |
| Staff account deactivated | Not a recipient (`activeOnly`). Existing inbox rows stay until normal cleanup. |
| Staff account reactivated | Receives again if they still have a staff role. |
| User deleted | Existing Notification cleanup: their reads and sourced notifications go away. Stored prefs for that user go away with the user. |
| Report or promotion request deleted / resolved | Already-sent inbox items stay (historical). No “resolved” staff ping. |
| Discord disconnected | Pending Discord deliveries for that user are dropped (existing behaviour). Prefs rows are not cleared. |

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Only current `moderator` / `admin` / `tech-admin` see the rows and can be recipients. `user` vs `user-confirmed` is N/A. |
| Visibility / privacy | Prefs are per-user and private. Inbox body has the reporter/requester display name only — no report reason or private note. |
| Settings | Two extra rows on the existing notification tab, role-gated. Not Settings parameters. |
| Notifications | This *is* the feature. Website default on / opt-out; Discord default off / opt-in. Self suppressed. |
| Domain events | Listen to the existing `Moderation.ReportSubmitted` and `Auth.PromotionRequested`. No new user-facing event. (How is DESIGN.) |
| Statistics | N/A — no new counter. |
| Moderation | N/A — does not add a reportable surface. |
| Lifecycle / cascade | See §5. No GC sweep. |
| Media | N/A — no images. |
| Search | N/A. |
| i18n | French only, lang files, no Blade literals. Proposed copy in §4.1 / §4.2; DESIGN/BUILD may tighten wording. |
| Mobile | Same Settings tab and inbox as today. |
| Accessibility | Existing toggles and inbox items; no new widget. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Scope | Create the two staff types **and** role-gate their preference rows. Types do not exist today (badges only). |
| 2 | Recipients | Same trio as the navbar badges: `moderator` + `admin` + `tech-admin`, for both types. |
| 3 | Website channel | Default on, staff can opt out. Discord stays default off / opt-in. |
| 4 | Actor | Suppress self — notify the other staff only. |
| 5 | Body | Short French line + reporter/requester display name + link to the matching admin queue. No reason/topic. |
| 6 | Demotion | Hide rows, stop sending immediately, **keep** stored prefs. |
| 7 | Extra types | Only these two in this task. No “report resolved”. Requester-facing promotion rows stay visible to everyone. |
| 8 | Reusable rule | Several future types will want “send to these roles, optionally exclude the current user”. This task defines that rule; it only *ships* the two staff types. |
| 9 | Calendar / Quote Contest | Not this audience. An activity’s eligible users are whoever passes that activity’s `role_restrictions`, not a type-level role list. Recorded on `calendar-notifications/`. |

## 8. Out of scope

- Any staff type other than « nouveau signalement » and « nouvelle demande de promotion ».
- Notifying staff when a report is approved, rejected, or deleted.
- Hiding or role-gating the existing requester-facing promotion accepted/rejected rows.
- Removing or changing navbar badges.
- Calendar / Quote Contest lifecycle notifications, and any “users authorized to this activity” audience.
- A digest or rate limit when many reports arrive in a short time.
- Changing Discord link-warning or silent-skip behaviour.
- Statistics, search, or a new Settings tab.

## 9. Open questions

None blocking.

Non-blocking: exact French inbox sentences may be tightened at BUILD (the
meaning is fixed: short line + display name + queue link).
