# New fanart / artwork module — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption (see `DECISIONS.md` → "Assumptions made without asking").
> Wireframes live next to this file (`*.png`) and are authoritative for layout,
> not for the rules settled below.

## 1. Overview

Confirmed members can contribute **images** (drawings) and **audio** (chapter
readings) to other authors' stories; authors validate each contribution before
it becomes visible. Authors can also attach their own images/audio
("artworks") to their stories, at the cost of a chapter credit. Contributions
appear in a new « Fanarts » tab on the story page, on the chapter page (image
block, audio widget) and on the contributor's profile, are visible to confirmed
members only, can be reported, and can optionally be relayed to Discord.

The epic ships as one release at the end (no feature toggle); PLAN splits it
into phases/sub-tasks.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| **Fanart** | Generic word for any contribution (image or audio). Used in the UI for contributions by non-authors. |
| **Artwork** | A fanart posted by an author of the story. Published immediately, costs one chapter credit. |
| **Fanartiste** | A per-story collaborator role granted by an author. Cumulative with other roles (a bêta-lecteur can also be fanartiste). |
| **Fanartiste désigné** | Shorthand for "a user holding the Fanartiste role on this story". |
| **Mode d'accueil** | Per-author setting, one for images and one for audio: `oui` / `non` / `fanartistes désignés`. |
| **En attente** | A submitted fanart not yet accepted or declined by an author. Visible only to the authors, the submitter and staff. |
| **Toute l'œuvre** | The group of image fanarts attached to no chapter. |
| **Demande de rôle fanartiste** | A request by a confirmed user to be granted the Fanartiste role on a story. |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Nothing of the feature (no tab, no play icon, no audio widget, no chapter block, no profile tab). | — |
| `user` (non-confirmed) | Nothing of the feature, same as guest. | — |
| `user-confirmed` | Published fanarts of every public/community story they can read; a fanartist's profile tab when that fanartist opened it to the community; their own pending items. | Submit fanarts where the mode allows; request the Fanartiste role; delete their own fanarts (pending or published); report a fanart. |
| Fanartiste (on a story) | As `user-confirmed`. | Submit on that story when the mode is `fanartistes désignés`. |
| Author / co-author of the story | All fanarts of the story, including the pending queue. | Accept / decline pending fanarts (any one author decides); delete any fanart; post / edit / delete own artworks (1 credit per new artwork); grant / revoke Fanartiste; accept / decline role requests; report a pending or published fanart. |
| Moderator | Everything (pending queue, profile tabs, private stories), as for other content. | Act on reports; delete any fanart directly. |
| Admin | As moderator. | As moderator; manage the fanart report reasons. |

Private stories accept no new contribution. Fanarts already on a story that
becomes private stay, visible to whoever can see the story (collaborators,
staff).

## 4. Functional requirements

### 4.1 Mode d'accueil (author settings)

1. In Paramètres › Histoires, each author has two settings: **Contributions
   images** and **Contributions audio**, each `oui` / `non` / `fanartistes
   désignés`.
2. Defaults: images `oui`, audio `fanartistes désignés`. Users who never touch
   the settings get the defaults.
3. The settings apply to every story the user authors.
4. On a co-authored story, the effective mode is the **most restrictive** of
   the co-authors' choices (`non` > `fanartistes désignés` > `oui`).
5. The author-facing Fanarts tab explains the effective mode and, on a
   co-authored story, that the most restrictive co-author setting applies.
6. Changing a setting never touches existing fanarts, published or pending; it
   only governs new submissions.

### 4.2 Fanartiste role

1. On the existing collaborators page, an author can grant the **Fanartiste**
   role to a **confirmed** member, alongside author and bêta-lecteur.
2. The role is cumulative: a user can be bêta-lecteur and fanartiste on the
   same story. Granting it notifies the user (existing "role given"
   notification).
3. The role only matters when the effective mode is `fanartistes désignés`.
4. Revoking the role does not remove the user's existing fanarts, published or
   pending.

### 4.3 Requesting the Fanartiste role

1. When the effective mode (images or audio) is `fanartistes désignés` and a
   confirmed reader does not hold the role, the story page offers « Demander le
   rôle de fanartiste ».
2. Submitting the request notifies every author.
3. The collaborators page lists pending requests. Any one author can accept
   (grants the role, notification "role given") or decline (requester
   notified). No comment required.
4. After a decline, the requester can request again only after **30 days**.
5. Only one pending request per user per story; the requester can withdraw it.
6. A request becomes moot (removed) if the user is granted the role directly,
   or if the story is deleted.

### 4.4 Submitting a fanart (non-author)

1. A confirmed user sees « **Proposer un fanart** » on a public or community
   story, next to the chapter list (wireframe `fanarts-tab-story-page.png`),
   when the effective mode for at least one media type allows them (`oui`, or
   `fanartistes désignés` and they hold the role). Never on a private story.
2. The button opens a dedicated page « Créer un nouveau fanart »
   (`create-fanart.png`) with an **Image** / **Audio** switch. A media type the
   user is not allowed to submit is not offered.
3. **Image**: file jpg / png / webp, max 2 Mo; title (required); description
   (optional, rich text, max 1000 characters, no minimum); chapter (optional —
   none means « Toute l'œuvre »).
4. **Audio**: file mp3, max 15 Mo; title (required); description (optional,
   same rules); chapter (**required**).
5. Required attestation checkbox: « J'atteste être l'auteur·ice de ce fanart,
   qu'il n'a pas été généré par une IA, que j'en détiens tous les droits et
   qu'il respecte le règlement du site ».
6. Optional checkbox « Partager sur Discord » (images only, see 4.12).
7. On save the fanart is **en attente**; every author is notified. It costs
   nothing. No cap on the number of pending submissions.
8. Rejected uploads (size, format, missing field, lost right between page load
   and save) show a validation error and keep the form filled.

### 4.5 Posting an artwork (author)

1. An author sees « **Ajouter un artwork** » instead of « Proposer un fanart »,
   on public and community stories (not private).
2. Same form as 4.4, plus a warning that a new artwork costs **one chapter
   credit** from the uploading author.
3. If the uploading author has no credit, the upload is refused with an
   explanation.
4. An artwork is published immediately; no approval, no notification to
   co-authors.
5. The author can edit their own artwork (file, title, description, chapter) at
   no cost. Deleting it does not refund the credit.
6. An audio artwork on a chapter that already has an audio replaces it (see
   4.7.5).

### 4.6 The Fanarts tab on the story page

1. The story page gains two tabs: **Chapitres** (the current list) and
   **Fanarts** (`fanarts-tab-story-page.png`). The tab is shown only to
   confirmed users and staff.
2. **Authors (and staff)** see on top « En attente de confirmation » with the
   pending thumbnails (images and audio), then the published content.
3. Published content is grouped by chapter in story order, plus a « Toute
   l'œuvre » group for unassigned images. Each group is collapsible
   (« Montrer tout » / « Cacher tout »).
4. For readers, groups of chapters they have **not read** are collapsed by
   default with a spoiler warning; read chapters and « Toute l'œuvre » are
   expanded. Authors see everything expanded. Content loads lazily.
5. Each chapter group shows its image thumbnails and its audio (with player).
6. Clicking a thumbnail opens a viewer (`view-image.png`): image fit to full
   width or height, title, artist name (link to profile), description,
   « Signaler » flag, and « Supprimer » for authors, the fanart's owner and
   staff.
7. Unpublished chapters' fanarts are hidden from readers (authors still see
   them). Empty tab: an empty state message.

### 4.7 Reviewing a pending fanart (author)

1. Clicking a pending thumbnail opens the review viewer (`accept-image.png`):
   image (or audio player), title, artist, description, target chapter,
   a **required** comment field, « Accepter », « Décliner » and a « Signaler »
   flag.
2. **Décliner**: the fanart is deleted. The fanartist is notified with the
   author's comment.
3. **Accepter**: the fanart is published in its chapter (or « Toute
   l'œuvre »). The fanartist is notified with the author's comment.
4. Any one author decides; the item disappears from the other authors' queues.
5. Accepting an **audio** on a chapter that already has one deletes the
   previous audio and notifies its creator (fanartist) that it was removed.
6. **Signaler** on a pending item: opens the report flow (4.11); the item
   leaves the queue (deleted, the copy lives in the report) and the fanartist
   receives the usual decline notification, without comment.
7. The author's comment appears only in the notification, never publicly.
8. A pending fanart survives the loss of the right to submit (mode switched to
   `non`, role revoked, story made private): authors can still accept or
   decline it.

### 4.8 Deleting fanarts

1. Authors can delete any published fanart of their story at any time; the
   fanartist is notified (no comment required).
2. A fanartist can delete their own fanart, pending or published, from the
   viewer or their profile tab. Nobody is notified.
3. Staff can delete any fanart (4.11.4).

### 4.9 Chapter page

1. If the chapter has a published audio, a player widget sits **below the
   chapter title**; it streams the audio. Report flag next to it.
2. If the chapter has published images, the same image block as the chapter
   group of the Fanarts tab appears **at the bottom of the chapter**, view-only
   (thumbnails open the viewer; no delete/accept there).
3. Neither is shown to guests or non-confirmed users.

### 4.10 Audio in the chapter list

1. In the Chapitres tab, chapters with a published audio show a speaker /
   play icon (`chapter-sound.png`). Shown to confirmed users and staff only.
2. Clicking it starts streaming that chapter's audio in a player on the story
   page.
3. When an audio ends, the chapter is **marked as read** for the listener and
   the **next chapter that has an audio** starts automatically (chapters
   without audio are skipped). Playback stops after the last one.

### 4.11 Reporting & moderation

1. Fanart is a new report topic with its own reasons (admin-managed, as for
   other topics).
2. « Signaler » sits next to each fanart (viewer), next to the audio player
   (story page and chapter page), and on pending items in the author's queue.
   Available to anyone who can see the fanart.
3. A **copy of the reported file** (image or audio) plus its title,
   description, artist and story/chapter is kept with the report so
   moderators can review it even if the fanart is later deleted.
4. Moderators have a direct « Supprimer » action on any fanart.
5. Approving a report does **not** delete the fanart, as for every other
   topic: the report links to the fanart, where moderators have a moderation
   menu with « Supprimer » (as for comments). On moderator deletion, the
   authors and the fanartist are notified. *(decision 31, supersedes the
   REFINE wording)*

### 4.12 Discord relay

1. A Discord bot can fetch the latest approved image fanarts to post them in a
   dedicated topic (bot polls, as for Discord notifications).
2. Eligible: **images only**, on **public** stories only (never community or
   private), and only when the fanartist ticked « Partager sur Discord » at
   submission. Fanarts become eligible when accepted.
3. Author artworks follow the same rule (the author ticks the box).
   *(assumption A9)*
4. Each item carries the image, title, artist name, story and chapter (no
   spoiler gating beyond naming the chapter).
5. A fanart deleted before the bot fetches it is not offered.

### 4.13 Fanartist profile tab

1. A new profile tab with two sections, **Images** and **Sons**, listing the
   user's published fanarts grouped by story, then chapter. Collapsible,
   lazy-loaded, spoiler-collapsed for chapters the **viewer** has not read
   (same rules as 4.6.4).
2. Images behave like the Fanarts tab thumbnails; sounds show a player.
3. The owner additionally sees their **pending** items with an « En attente de
   validation » badge, and can delete any of their items (pending or
   published). No editing — delete and resubmit.
4. The tab is **private by default** (owner + staff only). A setting in
   Paramètres › Profil opens it to confirmed members.
5. Viewers only see fanarts on stories they are allowed to read.
6. The tab is not shown at all to viewers who may not see it.

## 5. Lifecycle

| Event | Effect | Notification |
|-------|--------|--------------|
| Effective mode → `non`, Fanartiste role revoked | Blocks new submissions only. Published and pending stay. | — |
| Story → private | No new submission. Published fanarts stay (visible to those who can see the story); pending can still be decided. | — |
| Story private → public/community | Submissions allowed again. | — |
| Chapter unpublished | Its fanarts hidden from readers, visible to authors. Restored on republish. | — |
| Chapter deleted | Its fanarts deleted. | Each affected fanartist |
| Story deleted | All its fanarts deleted, pending role requests removed. | Each affected fanartist |
| Author account deleted, sole author | Story disappears → as story deleted. | Each affected fanartist |
| Author account deleted, co-authored story survives | Story's fanarts kept; only that author's own artworks deleted. | — |
| Fanartist account deleted | All their fanarts hard-deleted. | — |
| Fanartist deactivated | Their fanarts hidden (soft-deleted); restored on reactivation. | — |
| Author deactivated | Story's fanarts hidden; on a co-authored story only once **every** author is deactivated. Restored on reactivation. | — |
| New audio replaces an existing one | Previous audio deleted. | Previous audio's fanartist |
| Moderator deletion (from the fanart's moderation menu) | Fanart deleted (report copy kept). | Authors + fanartist |

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Confirmed users only; authors decide; staff override. See §3. |
| Visibility / privacy | Fanarts visible to confirmed users + staff only; no new uploads on private stories; profile tab private by default; author's review comment only in notification. |
| Settings | Two per-user settings in Paramètres › Histoires (images `oui`, audio `fanartistes désignés`), most-restrictive across co-authors; one Profile privacy setting for the profile tab (private by default). |
| Notifications | Pending fanart → all authors; accepted/declined (with comment) → fanartist; deleted by author/moderator → fanartist (+ authors for moderation); audio replaced → previous fanartist; story/chapter deleted → fanartist; role request → all authors; request accepted (role given) / declined → requester. Actor never notified of own action. No batching. |
| Domain events | Listen: user deactivated / reactivated / deleted, story deleted, chapter deleted, chapter unpublished/published, story visibility changed, report submitted (decision 31). Emission of own events: DESIGN decides. |
| Statistics | N/A — none for now (decision 22). |
| Moderation | New topic with own reasons; file copy kept with report; direct moderator delete; report approval deletes the fanart. |
| Lifecycle / cascade | See §5. |
| Media | Images jpg/png/webp ≤ 2 Mo; audio mp3 ≤ 15 Mo, streamed with seeking. Visible to confirmed only, so not public URLs (DESIGN). Moderation copies must survive fanart deletion. |
| Search | N/A — fanarts are not indexed in global search. |
| i18n | French only; labels per wireframes (« Proposer un fanart », « Ajouter un artwork », « En attente de confirmation », « Montrer tout » / « Cacher tout »). |
| Mobile | Tabs, thumbnails and viewer must work on small screens; the viewer stacks image above text. |
| Accessibility | Thumbnails need alt text (title); player has native controls; tabs and collapsibles keyboard-operable. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | REFINE scope | Whole epic in one spec; split at PLAN. |
| 2 | Where the mode d'accueil lives | Per user in Paramètres › Histoires; on co-authored stories the most restrictive choice wins, explained on the author view. |
| 3 | Fanartiste cumulative with bêta-lecteur? | Yes. |
| 4 | Who validates on a co-authored story | Any one author. |
| 5 | Whose credit an artwork costs | The uploading author's. |
| 6 | Refund on artwork deletion | No. |
| 7 | Audio replacement | Previous audio deleted, its fanartist notified. |
| 8 | Fanartist sees own pending items | Yes, on profile tab, with badge, can withdraw. |
| 9 | Pending when right to submit disappears | Stays; author decides. |
| 10 | Deactivation | Soft-delete, restore on reactivation; account deletion hard-deletes the fanartist's work; co-authored story hidden only when all authors deactivated. |
| 11 | Chapter unpublished | Fanarts hidden, back on republish. |
| 12 | Co-author deletes account, story survives | Fanarts kept; only their own artworks removed. |
| 13 | Author can report a pending fanart | Yes. |
| 14 | Direct moderator delete | Yes, authors + fanartist notified. |
| 15 | Audio auto-advance | In: mark read + play next chapter with audio. |
| 16 | Flood cap | None for now. |
| 17 | Feature toggle | None; release at end of epic. |
| 18 | Formats | Images jpg/png/webp, audio mp3. |
| 19 | Guests / non-confirmed | See nothing of the feature. |
| 20 | Fanartist edits accepted fanart | No — delete & resubmit. |
| 21 | Discord relay scope | Images only, public stories only, fanartist opt-in at submission. |
| 22 | Statistics | None for now. |
| 23 | Fanartiste role request | Any author accepts/declines, requester notified, 30-day cooldown after decline. |
| 24 | Description | Optional, rich text, max 1000, no minimum. |
| 25 | GIF | No. |
| 26 | Attestation wording | Includes the AI clause (4.4.5). |
| 27 | Button label | « Proposer un fanart » (authors: « Ajouter un artwork »). |

## 8. Out of scope

- Statistics / counters of any kind.
- A cap or rate limit on submissions or notifications.
- Fanartists editing their published fanarts.
- Notifying followers/readers when a fanart is published.
- Fanarts in global search.
- Animated GIFs and audio formats other than mp3.
- Audio on Discord.
- A persistent player that keeps playing across page navigation (playback
  lives on the story page; the chapter page has its own widget).
- Comments, reactions or likes on fanarts.
- Teaser / upsell for non-confirmed users.

## 9. Open questions

| # | Question | Blocking? |
|---|----------|-----------|
| 1 | How long the moderation copy of a file is kept after the report is closed (forever with the report, or purged after N days)? | Non-blocking — DESIGN may propose; default: as long as the report exists. |
| 2 | Exact title / comment length limits (assumed ~100 / ~500). | Non-blocking — tune in BUILD. |

### Notes parked for DESIGN

- The user favours a **separate `Fanart` domain** and accepts compromises to
  isolate it from Story.
- Collaborator roles are single-valued today (`story_collaborators.role`);
  decision 3 requires multi-role.
- Chapter credits have no public API; artwork needs check / spend.
- Story page has no tab system today.
- Media private scopes are hard-coded; images visible to confirmed only are
  not public-URL material.
- No moderation precedent for keeping a copy of an image/audio.
- Story's Histoires settings tab has an early-return guard: new parameters
  registered from another domain need care.
- Discord bot API pattern: `docs/Discord_Api_Usage.md`.
