# Secret Gift — enrolment — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Secret Gift ships with no way to join it — nothing in the app creates a
`calendar_secret_gift_participants` row outside of tests. This feature adds a
real enrolment flow (join, fill in gift preferences, un-enrol), a mandatory
registration deadline, and a moderator/admin screen to run the gift shuffle —
turning Secret Gift from unusable-as-shipped into a working activity. It
absorbs the separate `calendar-subscription/` backlog task: that task's
generic base-Calendar enrolment mechanism is dropped as speculative (Secret
Gift is its only real consumer), and its participant-limit idea is dropped
entirely (see §8, §10).

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Participant | A user who has joined (`calendar_secret_gift_participants` row) a Secret Gift activity. |
| Préférences (preferences) | Free rich-text the participant writes about what they like/dislike, shown only to whoever draws them as recipient. |
| Date de fin d'inscription (registration deadline / `registration_ends_at`) | New mandatory date on the activity; enrolling and un-enrolling are only possible before it (and before the shuffle, if that happens first). |
| Tirage (shuffle) | The admin action that builds the giver→recipient assignments. Existing mechanic (`ShuffleService`), currently Artisan-only. |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Whatever `role_restrictions` already allows today (unchanged) | Nothing — enrolment requires being logged in |
| `user` (non-confirmed) | Gated by the activity's existing `role_restrictions`, unchanged by this feature | Same as above — this feature adds no new role check beyond the generic gate that already applies to the activity page |
| `user-confirmed` | Activity page, own participant status, participant list once enrolled | Join / edit own preferences / un-enrol, all only before registration closes |
| Moderator / Admin / Tech Admin | New shuffle screen: participant list, count, shuffle action | Trigger shuffle (any time there are ≥2 participants, before the activity goes active); everything a regular participant can do if they also join |

Enrolment itself introduces no new role gate — whoever can already see the
activity page (per its existing `role_restrictions`) can enrol. The shuffle
screen is restricted to moderator/admin/tech-admin.

## 4. Functional requirements

### 4.1 Joining

1. A user who can see the activity page, while it is `preview` and before
   `registration_ends_at`, and before the admin has shuffled, sees a "Join"
   action (replacing today's static "you are not a participant" message).
2. Clicking it opens a rich-text editor for their preferences, pre-filled with
   the existing starter template (likes / dislikes / fanart allowed /
   favourite genres / other info).
3. Submitting creates their participant row (preferences may be left blank —
   the column is nullable today and stays optional).
4. Once enrolled, the user sees the participant list (other enrolled users'
   display names — not their preferences, and not any pairing information).

### 4.2 Editing preferences / un-enrolling

1. While registration is still open (same window as joining: `preview`, before
   `registration_ends_at`, before shuffle), an enrolled user can re-open the
   rich-text editor and change their preferences at any time.
2. They can also un-enrol, which deletes their participant row entirely
   (preferences included).
3. Once registration has closed (deadline passed, or the admin has shuffled —
   whichever came first), both editing preferences and un-enrolling become
   unavailable, even if the activity is technically still `preview`.

### 4.3 Registration deadline

1. `registration_ends_at` is a new, **mandatory** field on the Secret Gift
   activity, set by the admin alongside the existing `preview_starts_at` /
   `active_starts_at` / `active_ends_at` dates.
2. Validation: `preview_starts_at < registration_ends_at < active_starts_at`.
3. Once `registration_ends_at` has passed, enrolling, editing preferences and
   un-enrolling all close automatically — no admin action required.

### 4.4 Shuffle screen (moderator / admin / tech admin)

1. A new admin screen for a Secret Gift activity shows the current
   participant list and count, and a "Shuffle" action.
2. The action is enabled once there are at least 2 participants (existing
   `ShuffleService` rule) — it does **not** wait for `registration_ends_at`;
   the admin may close registration early by shuffling.
3. Shuffling can be repeated as many times as needed (each run replaces the
   previous assignments — existing behaviour, unchanged) as long as the
   activity has not yet reached `active` state.
4. Once the activity is `active`, the shuffle action is disabled entirely,
   whether or not a shuffle already happened.
5. From `active` onward, the existing gift-preparation flow
   (`SecretGiftController::saveGift`, preferences shown to the assigned giver)
   is unchanged.

## 5. Lifecycle

- **Activity reaches `active` before ever being shuffled** (e.g. admin forgot,
  or fewer than 2 participants ever joined): out of scope to auto-shuffle or
  block activation — see §8. The existing "no assignment yet" message on the
  activity page already covers this state for the user.
- **Participant deactivated/deleted, before shuffle**: their participant row
  is removed — same effect as un-enrolling, applied automatically.
- **Participant deactivated/deleted, after shuffle**: no special handling.
  Their assignment (as giver and as recipient) is left as-is; the person who
  would have received a gift from them simply doesn't get one. This matches
  the existing, unaddressed gap for Jardino goals on the same events — not a
  regression, a deliberate non-goal for this task (see §8).
- **Activity deleted**: existing cascade already removes
  `calendar_secret_gift_participants` and `calendar_secret_gift_assignments`
  rows (`cascadeOnDelete` on `activity_id`) — unchanged by this feature.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | No new role gate for enrolment beyond the activity's existing `role_restrictions`. Shuffle screen restricted to moderator/admin/tech-admin. |
| Visibility / privacy | Participant list visible to enrolled participants only, not to outsiders or non-enrolled viewers of the activity page. Preferences visible only to the participant themself and to whoever draws them as recipient — never to the participant list. |
| Settings | N/A — no user-configurable preference introduced. |
| Notifications | Out of scope — matches the existing separate `calendar-notifications/` backlog task and the SecretGift README's known gap (no notification on reveal either). |
| Domain events | N/A — no other domain plausibly needs to react to a Secret Gift enrolment. |
| Statistics | N/A — not requested. |
| Moderation | N/A — preferences text is low-risk, addressed-to-one-person content; no report/hide flow requested. |
| Lifecycle / cascade | See §5. Existing FK cascade on activity deletion is sufficient; no new deactivation/deletion listener beyond the pre-shuffle cleanup in §4.2/§5. |
| Media | N/A — preferences are text only, no image/sound attached at enrolment. |
| Search | N/A — participants and preferences are not searchable content. |
| i18n | All new UI strings (join button, preferences editor, participant list, shuffle screen) go in `Private/Activities/SecretGift/Resources/lang/fr/secret-gift.php`, French only, matching the app convention. |
| Mobile | No new constraint beyond the existing activity page and admin panel being responsive already. |
| Accessibility | Join/un-enrol/shuffle are standard form actions; no new a11y pattern beyond what the rich-text editor component already provides. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Does this wait for / merge with `calendar-subscription/`? | Merge. Secret Gift is the only real consumer of "enrolment"; generic base mechanism dropped as speculative. |
| 2 | Enrolment/un-enrolment window? | Open during `preview`, closes at `registration_ends_at` **or** when the admin shuffles, whichever comes first. |
| 3 | Is `registration_ends_at` mandatory, with what ordering? | Mandatory: `preview_starts_at < registration_ends_at < active_starts_at`. |
| 4 | Does the deadline auto-close registration? | Yes — passing the date closes enrolling/un-enrolling automatically, independent of any admin action. |
| 5 | Can the admin shuffle before the deadline? | Yes, any time there are ≥2 participants — the date is a backstop, not a lock on the action. |
| 6 | Can the admin re-shuffle, and until when? | Freely, as many times as needed, until the activity reaches `active` state — then shuffling is blocked entirely. |
| 7 | What happens at `max_participants`? | The field is removed entirely — no cap, no waiting list. |
| 8 | Who sees the participant list? | Enrolled participants only. |
| 9 | When are preferences filled in, and can they be edited? | At enrolment (rich-text editor, pre-filled template), editable until registration closes (§2). |
| 10 | Deactivation/deletion of a participant? | Before shuffle: row removed. After shuffle: assignment left as-is, no special handling. |

Mirror of `DECISIONS.md`, restricted to functional decisions.

## 8. Out of scope

- A generic, base-Calendar enrolment mechanism usable by any activity type
  (the dropped `calendar-subscription/` proposal).
- Participant caps / `max_participants` in any form, including a waiting list.
  The field and its admin-form input are removed as dead code.
- The `requires_subscription` toggle on `calendar_activities` — also removed
  as dead code; Secret Gift's need for enrolment is inherent to the type, not
  a per-instance toggle.
- Any notification (on enrol, un-enrol, shuffle, or gift reveal).
- Reassigning or auto-reshuffling to fix a broken pairing after a
  post-shuffle deactivation/deletion.
- Auto-shuffling, or blocking activation, when an activity reaches `active`
  with fewer than 2 participants or without ever having been shuffled.
- Reopening registration after it has closed (deadline passed or shuffled).
- Moderation/reporting of preferences text.

## 9. Open questions

None blocking. All questions raised in `00-request.md` (both the original and
the merged-in `calendar-subscription/` questions) are resolved above.
