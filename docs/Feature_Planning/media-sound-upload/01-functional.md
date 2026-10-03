# Gift sound on Media — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

The Secret Gift sound (an mp3 the giver attaches to their gift) moves to the
same storage the gift image already uses: owned by Media, private, reclaimed by
Media's garbage collection. This closes the orphan leak (sounds left on disk
forever after a re-shuffle or an activity deletion) and retires
`<x-shared::sound-upload>`, the last upload widget in Shared. For users,
nothing visibly changes.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Son du cadeau | The optional mp3 a giver attaches to their Secret Gift (existing term, unchanged) |
| Orphan sound | A sound file no assignment row points at any more (replaced, removed, re-shuffled, activity deleted) |

## 3. Roles & visibility

Unchanged from today. Restated because the storage move must preserve it.

| Role | Can see | Can do |
|------|---------|--------|
| Guest | nothing | nothing |
| `user` / `user-confirmed` participant, as **giver** | their own gift's sound, at any time | upload, replace, remove while the activity is ACTIVE |
| Participant, as **recipient** | the sound of the gift they receive, once the activity is ENDED or ARCHIVED | listen, seek, download |
| Any other participant / user | nothing | nothing |
| Moderator / Admin | no special access (as today) | nothing |

The sound never has a public URL: every byte goes through SecretGift's own
authorization check (`canViewSound`), as for the image.

## 4. Functional requirements

### 4.1 Upload / replace / remove (giver)

1. On the gift preparation form, the giver sees the sound field with the same
   behaviour as today: drag & drop or file picker, a preview player for the
   chosen file, the current sound playable, a remove control.
2. Accepted: mp3 only, max 10 MB (unchanged). Invalid file → the same
   validation error as today, form not saved.
3. Saving with a new file stores it privately and attaches it to the gift.
4. Replacing or removing detaches the old sound from the gift immediately (it
   is no longer playable or downloadable by anyone). The old file itself is
   reclaimed later by Media's cleanup, after its grace period (7 days), as for
   the gift image — no longer deleted on the spot.
5. Only possible while the activity is ACTIVE (unchanged).

### 4.2 Listen / seek / download

1. The giver (always) and the recipient (once ENDED/ARCHIVED) can play the
   sound in the reveal view.
2. **Seeking in the player keeps working** (the browser can jump anywhere in
   the track).
3. The download button keeps working, with a downloaded file name equivalent
   to today's.
4. Anyone else gets the same refusal as today.

### 4.3 Existing sounds

1. Sounds already uploaded in production are moved to the new storage as part
   of the deploy (database migration, like the images), with no manual step.
2. Every existing gift keeps its sound, playable and downloadable as before.
3. A database row whose file is missing on disk is reported, not fatal; the
   deploy goes through.

### 4.4 Cleanup

1. Sounds orphaned by a replace, a remove, a re-shuffle or an activity
   deletion are reclaimed by Media's periodic cleanup after the grace period.
2. A sound still attached to any gift is never reclaimed.

## 5. Lifecycle

| Event | Effect on the sound |
|-------|---------------------|
| Giver replaces / removes | detached at once; file reclaimed after grace period |
| Re-shuffle (assignments recreated) | old sounds detached with their rows; files reclaimed after grace period (**fixes today's leak**) |
| Activity deleted | sounds reclaimed after grace period (**fixes today's leak**) |
| User deactivated / deleted | unchanged from today (no sound-specific behaviour) |

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Unchanged — see §3 |
| Visibility / privacy | Sound stays private; served only after `canViewSound` |
| Settings | N/A — no preference involved |
| Notifications | N/A — no new notification |
| Domain events | N/A — no new event; no new listener |
| Statistics | N/A |
| Moderation | N/A — gifts are not reportable today; unchanged |
| Lifecycle / cascade | See §5; cleanup via Media GC |
| Media | Media becomes owner of the sound bytes (private, non-image); Media authorizes nothing |
| Search | N/A |
| i18n | Existing French labels of the sound field kept (wording may move with the widget) |
| Mobile | Unchanged behaviour |
| Accessibility | Unchanged behaviour (native `<audio>` player with controls) |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Where do gift sounds live? | Media owns them: a private non-image file store with seeking support, cleaned by Media GC, served by SecretGift after its own check; existing files migrated |
| 2 | Removal/replace timing | Detached at once, file reclaimed after the GC grace period (image precedent), not deleted on the spot |
| 3 | Overall flow (replay) | Confirmed as written in §4–§5 |

## 8. Out of scope

- Changing gift visibility or timing rules, or giving admins access.
- Touching the gift image half.
- Other audio formats, a size change, or a duration limit.
- A reuse/library picker for sounds.
- Deactivated/deleted-user handling of gifts.

## 9. Open questions

None blocking. For DESIGN (technical, parked):

- Shape of the Media private non-image store (generic file vs sound-specific),
  and where Range support lives (Media `stream()` vs consumer).
- GC currently only recognises image extensions under the private root —
  must learn mp3.
- Name/home of the replacement widget (Media component, sound-specific or
  generic file field).
