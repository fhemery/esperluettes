# Quotes — private stories — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Fix quoting on **private** (and correctly gate **community**) stories so that
readers who can access the story can save quotes, see them in their own book,
and trigger the existing author notification — while readers who cannot access
the story never see those quote entries on someone else's Citations tab. This
closes a hole where private stories effectively cannot be quoted at all, and
pins the non-leak rules for community visibility.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Citation / quote | Passage saved by a reader from a chapter (existing Quote concept). |
| Cahier de citations | The reader's quote book on the profile « Citations » tab. |
| Histoire privée | Story with visibility `private` — only collaborators (authors + beta readers, etc.) may read it. |
| Histoire communauté | Story with visibility `community` — only confirmed users (plus collaborators) may read it. |
| Note | Strictly private text on a quote; unchanged — never shown to anyone but the owner. |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | No Citations tab; no quote UI. | Nothing. |
| `user` (non-confirmed) | No Citations tab (own or others'). Never quote entries from community or private stories via any Quote surface. | Cannot create quotes. |
| `user-confirmed` (no access to the story) | Other people's visible Citations tabs, but **entries from inaccessible stories are omitted entirely** (no placeholder). | Cannot create a quote on a chapter they cannot read. |
| `user-confirmed` with story access (incl. **beta reader** on a private story) | Own quotes everywhere. On another's Citations tab: entries for stories they can access, including private/community. | Create / edit note / delete **own** quotes on chapters they can read, if they are not an author of that story. |
| Author / co-author of the story | Own Citations tab as usual. Notification when someone quotes their chapter. **Not** special access to readers' notes or books. | Cannot quote their own story (unchanged). |
| Moderator / Admin | Same as confirmed for quote books in this task — **no** moderator override to reveal inaccessible private-story quotes. | Same create rules as a confirmed reader. |

## 4. Functional requirements

### 4.1 Create a quote on a private (or community) chapter

1. A confirmed reader who **can read** the chapter opens it, selects a passage, and uses « Citer » as today.
2. Save succeeds for private and community stories the same way as for public ones.
3. Authors/co-authors of that story still get **403** / no toolbar affordance.
4. A confirmed user **without** access to the story cannot create a quote on it (server must refuse even if the client is forged).
5. Beta readers **can** quote (they are readers with access, not authors). Today they cannot — that is the main functional break for private stories.

### 4.2 Author notification

1. On successful create, authors/co-authors of the story receive the existing `quote.chapter_quoted` notification (quoter excluded), including when the story is private or community.
2. The notification must not include the reader's private note (unchanged). Passage text handling stays as today's notification type already does.
3. No notification on note edit or delete (unchanged).

### 4.3 Own Citations tab (owner)

1. The owner always sees **all** of their quotes, including those from private and community stories.
2. Private note remains visible only to them.

### 4.4 Another user's Citations tab (non-owner)

1. Tab still requires: viewer confirmed + owner has not hidden the tab (`hide-quotes-tab`). Guests and non-confirmed see no tab / empty book.
2. For each quote entry, the viewer must have **current** access to that story (same rule Story uses for reading). Otherwise the entry is **omitted** — not shown as « Chapitre non disponible ».
3. Consequently:
   - Quotes from **private** stories appear only for viewers who are collaborators on that story (e.g. other beta readers / authors of that story browsing the quoter's book).
   - Quotes from **community** stories appear only for **confirmed** viewers (who already pass the tab gate) — never for non-confirmed users.
   - Quotes from **public** stories appear for any confirmed viewer of a visible book.
4. The private note is never included for non-owners (unchanged).

### 4.5 In-chapter view

Unchanged: a reader only ever loads **their own** quotes for the chapter. No cross-reader quote leakage on the chapter page.

## 5. Lifecycle

| Event | Behaviour |
|-------|-----------|
| Story becomes private / community / public | Non-owner Citations lists re-evaluate access on each request; entries appear or disappear accordingly. No backfill job. |
| Access revoked (removed as beta reader, etc.) | Entry disappears from that viewer's reading of the owner's book on next load. Owner still sees it. |
| Chapter unpublished / deleted / story gone | Owner: existing « unavailable » treatment. Non-owner: entry omitted (no access / not published). |
| User deactivated / reactivated / deleted | Existing Quote cascades (soft-delete / restore / nullify `user_id`) — unchanged. |

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Confirmed required to quote and to see others' books; non-confirmed never see community/private quote entries. |
| Visibility / privacy | Per-entry gate = Story access. No leak of private-story passages via Citations. |
| Settings | Existing `hide-quotes-tab` only — no new setting. |
| Notifications | Existing author notification must fire for private/community creates. |
| Domain events | Existing `ChapterPassageQuoted` — no new event. |
| Statistics | N/A — no new counters. |
| Moderation | N/A this task — quotes moderation remains backlog (`quotes-moderation/`). No mod override to peek private-story quotes. |
| Lifecycle / cascade | Unchanged Quote listeners; visibility is computed at read time. |
| Media | N/A. |
| Search | N/A — quotes stay out of global search. |
| i18n | No new user-facing strings expected; reuse existing quote UI / unavailable copy. |
| Mobile | Same flows; no special layout. |
| Accessibility | Unchanged surfaces. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Mode | `auto` — privacy bugfix; intent already in `00-request.md`. |
| 2 | Who may quote private stories? | Confirmed non-authors who can **read** the story (incl. beta readers). |
| 3 | Who sees private-story quotes on another's book? | Only viewers who currently have Story access (e.g. other beta readers) — as requested « if possible ». |
| 4 | Community quotes vs non-confirmed | Never visible to non-confirmed (tab gate + per-entry access). |
| 5 | Author notification on private quote | Yes — same notification as public. |
| 6 | Moderator override | No — out of scope for this fix. |

## 8. Out of scope

- In-chapter author view of who quoted what (`quotes-author-view/`).
- Moderation of quotes/notes (`quotes-moderation/`).
- Changing Story visibility rules or collaborator roles.
- Removing / renaming `StoryPublicApi::isAuthorOrCoAuthor` as a Story API cleanup — that is owned by `story-author-check/` (still required for beta readers to quote; see §9).
- New settings, search indexing, or statistics.
- Changing note privacy or notification copy beyond making private/community creates notify correctly.

## 9. Open questions

| # | Question | Status |
|---|----------|--------|
| 1 | `story-author-check/` is `WIP:DESIGN` and is the intended fix for « beta readers blocked by `isAuthorOrCoAuthor` ». This task's create-on-private acceptance depends on that behaviour. Coordinate in DESIGN: land that refactor first, or absorb the `canQuote` behaviour change here and leave the API rename to that task. | **non-blocking** for REFINE — DESIGN must pick the sequencing. |

No blocking open questions.
