# Quotes — in-chapter author view (vNext) — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

Request: [`00-request.md`](./00-request.md). Decisions log:
[`DECISIONS.md`](./DECISIONS.md). Supersedes the vNext placeholder in
[`../Quotes.md`](../Quotes.md) §4.5 / decision #6 / §11.

## 1. Overview

On the chapter reading page, the chapter's author(s) can turn on a view that
shows which passages their readers have saved as quotes, as a heat tint over the
text plus a per-passage count, and see who quoted each passage. It turns a signal
that v1 gave only to the reader who saved it — and to the author only as a
stream of disconnected notifications — into something readable in place, in the
text it is about. Reader notes stay strictly private and are never part of this
view.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| **Vue auteur** | The aggregate in-chapter view described here. Off by default; only authors/co-authors of the chapter can turn it on. |
| **Badge « n citations »** | An author-only metric badge in the chapter header's metric row, carrying the chapter's total number of quotes. Clicking it opens the chapter summary. |
| **Récapitulatif du chapitre** | The popup opened from the badge: one row per quoted passage with its count, stale passages included and badged. |
| **Bascule de surbrillance** | The icon next to the badge that turns the heat tint on and off. |
| **Heat / intensité** | The tint over quoted text, whose depth grows with the number of quotes covering that exact text. |
| **Marqueur de marge** | The right-margin marker showing the count for a quoted passage. `md+` viewports only. |
| **Popover de passage** | The panel opened by clicking a tint or a marker; lists the readers who quoted that passage. |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Nothing. No toggle, no tint, no marker. | Nothing (unchanged). |
| `user` (non-confirmed) | Nothing — including on their own chapters, in the theoretical case of a demoted author (decision #17). | Nothing (unchanged — still cannot quote). |
| `user-confirmed` (reader) | Only their own quotes, exactly as in v1: yellow tint + their own private note. | Quote, edit their own note, delete their own quote (unchanged). |
| Author / co-author of the chapter | The toggle, the heat tint, the margin markers, and the reader list per passage. Never any note. | Turn the view on/off. Nothing else — the view is read-only. |
| Beta reader on the story | Nothing beyond what they see as an ordinary reader — they are collaborators, but not authors (decision #19). | Nothing. |
| Moderator | Nothing beyond what they see as an ordinary reader. | Nothing. Moderation of quotes is backlog task #2. |
| Admin | Same as moderator — no override. | Nothing. |

Access requires **authorship of the chapter's story _and_ the `user-confirmed`
role** (decision #17). Story creation already requires `user-confirmed`, so a
demoted author is a case that should not arise in practice; treating it as a
loss of access keeps the feature on the app's existing role gate instead of
carving out an exception for it. Authors already cannot quote their own story
(v1 rule), so an author never sees the reader tint and the author heat at the
same time on the same chapter.

## 4. Functional requirements

### 4.1 The badge and the toggle

1. An author opens a chapter of their own story. In the chapter header's
   existing metric row — beside the reads and word-count badges — an
   author-only badge shows **« n citations »**, where `n` is the number of
   quotes readers have saved on this chapter.
2. Next to the badge sits the **heat toggle**, an icon. It is **off on page
   load**. With it off, the chapter body looks and behaves exactly as it does
   today — no tint, no margin marker, no extra element in the prose.
3. Turning it on tints the quoted passages and renders the margin markers;
   turning it off removes them.
4. The toggle is **not persisted**. It resets to off on every page load, on
   every chapter — nothing is remembered client-side or server-side. The
   default that matters is "clean page"; an author who wants the heat asks for
   it each time, and never has to switch it back off after the fact.
5. At `n = 0` the badge still renders, reading « 0 citation », and neither the
   badge nor the toggle does anything — there is nothing to show. This is the
   empty state; there is no separate empty-state message (assumption A7).
6. Neither the badge nor the toggle is rendered for anyone who is not an
   author/co-author of the chapter.

### 4.2 The chapter summary

1. Clicking the badge opens the **récapitulatif**, a popup listing the chapter's
   quoted passages. It works whether the heat toggle is on or off — the author
   can consult the summary without ever tinting the text.
2. **One row per passage**, showing the quoted text and how many readers kept
   it. Two readers who quoted the same passage produce one row with a count of
   two, not two rows.
3. Rows are ordered by count, highest first (assumption A8). Reader names are
   *not* listed here — they belong to the in-text popover (§4.4), which keeps
   the summary short on a well-read chapter.
4. A **stale passage** — one the author has since edited away — appears in the
   list with the existing badge wording « Passage plus présent dans le
   chapitre », below the live rows. This is where the discrepancy of §4.3.6
   becomes legible: a quote that cannot be tinted is still counted, and the
   summary says why.
5. Clicking a live row closes the popup, **turns the heat toggle on if it was
   off**, scrolls the page to that passage, and opens its reader popover.
6. A stale row is **inert** — it has no location in the current text to scroll
   to. It is not presented as clickable.

### 4.3 Reading the heat

1. Every passage quoted by at least one reader is tinted.
2. Where several readers' quoted ranges overlap, the tint **deepens with the
   number of quotes covering that text**. A word kept by three readers is
   visibly darker than a word kept by one.
3. Ranges do not need to be identical to combine: overlapping-but-different
   quotes contribute to the heat of the text they share, and each keeps its own
   lighter tint over the text it alone covers.
4. On `md+` viewports, a small marker showing the count sits in the **right
   margin** at the line of a quoted passage.
5. Below `md`, **no margin marker is rendered** — only the tint. This matches
   the reader-side rule already specified in `../Quotes.md` §4.2.
6. A **stale quote** — one whose anchor can no longer be found because the
   author edited the passage away — is not tinted and has no marker, exactly as
   a reader's own stale quote stops being tinted in v1. It is still counted by
   the badge, and it stays visible and explained in the summary (§4.2.4).

### 4.4 Seeing who quoted a passage

1. Clicking (or activating with the keyboard) a tint or a margin marker opens
   the passage popover. This works on every viewport, including mobile, where
   the tint is the only target. A row of the chapter summary opens the same
   popover (§4.2.5).
2. The popover shows the number of quotes on that passage and the list of
   readers who quoted it: avatar, display name, relative date.
3. The list is ordered **newest first** (assumption A4).
4. Each reader's name links to their **profile page**. Whether their "Citations"
   tab is visible there is unchanged and still governed by the existing
   `canViewQuoteBook` rule — this feature grants the author no access to any
   reader's quote book.
5. A reader whose account was deleted does not appear, because their quotes no
   longer exist (see §5).
6. **The popover never contains a note**, in any form, for any passage, for any
   author. This is a server-side guarantee, not a template condition.
7. When the clicked point is covered by several overlapping quotes, the popover
   lists every quote covering that point.

### 4.5 What does not change

1. Readers see exactly what they saw in v1: their own quotes, their own tint,
   their own note. Nothing about the author view is visible to them, and there
   is no indicator telling a reader that the author can see the aggregate (see
   open question 2).
2. Quoting rules, the mini-form, the reader's popover, the profile "Citations"
   tab and the `hide-quotes-tab` setting are untouched.
3. The existing `ChapterQuotedNotification` continues to fire per quote,
   unchanged.

## 5. Lifecycle

| Event | Effect on the author view |
|-------|---------------------------|
| Reader **deactivates** their account | Their quotes are soft-deleted (v1 behaviour, unchanged) → they drop out of the heat, the counts and the lists. |
| Reader is **reactivated** | Their quotes are restored (v1 behaviour, unchanged) → they reappear in the heat and the counts. |
| Reader **deletes** their account | **Changed from v1**: their quotes are now **deleted outright**, instead of being kept with a null owner. They disappear from the heat, the counts and the lists permanently. No orphan row is ever rendered. |
| Existing orphaned rows (`user_id IS NULL`) | None exist — confirmed by the user. No data migration, no read-time filter. |
| Reader **deletes one quote** | It leaves the heat and the counts immediately for anyone loading the chapter afterwards. |
| Reader **edits their note** | No effect — the note is not part of this view. |
| Author **edits the chapter** so a quoted passage disappears | The quote becomes stale: no tint, no marker. The row survives, and the reader still sees it in their quote book with the existing "Passage plus présent dans le chapitre" badge. |
| **Chapter or story deleted** | The chapter page no longer exists; quotes are handled by the existing v1 rule (no cross-domain FK, references resolved at render time, missing ones shown as unavailable). Nothing specific to this feature. |
| Chapter **unpublished** | The author can still open their own chapter, so the view still works. Readers' access is governed by existing rules. |

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Gated on authorship of the chapter's story **and** `user-confirmed` (decision #17). Co-authors included (A1). No moderator/admin override — decision #4. |
| Visibility / privacy | The note is never exposed — reaffirmed as a server-side invariant (§4.4.6), and the summary (§4.2) carries no note either. Reader identity is disclosed to the author, which the existing `ChapterQuotedNotification` already does per quote, so this introduces no new disclosure. No reader-facing change (§4.5). |
| Settings | N/A — the toggle is ephemeral view state, not a user preference and not persisted at all (decision #14). No new entry in the Settings page, no `localStorage` key; `hide-quotes-tab` is untouched. |
| Notifications | N/A — nothing new is sent. The existing per-quote author notification is unchanged. Readers are not told the author looked. |
| Domain events | N/A — the view is read-only and emits nothing. `ChapterPassageQuoted` is unchanged. |
| Statistics | N/A for this version — no "quotes received" counter. Listed as out of scope (§8). |
| Moderation | N/A — moderator access to quotes is backlog task #2 (`quotes-moderation/`), deliberately not pulled forward (decision #4). |
| Lifecycle / cascade | §5. One behaviour change: hard-delete on account deletion (decision #5). |
| Media | N/A — the only images are reader avatars, already served through the existing profile rendering. |
| Search | N/A — nothing indexable is created. |
| i18n | French only. New strings: the badge label and tooltip with its count (plural agreement on « n citation·s »), the summary heading and its stale badge (reusing the existing « Passage plus présent dans le chapitre » wording), the passage popover heading (« Cité par n lecteur·s »), and aria-labels for the tint, the margin marker and the heat toggle. All in the Quote domain's lang files, no literals in Blade. |
| Mobile | Below `md`: tint only, no margin marker (§4.3.5); tapping the tint opens the popover, anchored below the passage as the reader popover already is. Badge, toggle and summary render on all viewports. |
| Accessibility | Tint and marker are keyboard-activatable and carry an aria-label naming the count; badge, toggle and summary rows are labelled controls. Heat depth is a redundant cue — the count is always available as text in the marker (md+), in the passage popover and in the summary, so the information is never carried by colour alone. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | What does the author see on a quoted passage? | Aggregate count **and** the list of readers who quoted it (name, avatar, date). Settles the open question carried over from Quotes v1 §11. |
| 2 | How are overlapping quotes rendered? | Heat map: tint deepens with the number of quotes covering the text. Not exact-passage grouping, not a flat tint. |
| 3 | Is the view always on for authors? | No — a toggle in the chapter page, **off by default**, because authors constantly re-read and proofread their own chapters. |
| 2b | Does a demoted (non-confirmed) author keep the view? | No. The view requires authorship **and** `user-confirmed`, so the endpoint stays inside the app's existing role gate. Story creation already requires `user-confirmed`, making this case theoretical. |
| 3b | Is the toggle state remembered? | No. It resets to off on every page load; nothing is stored. Persisting it would leave authors re-disabling the heat, which is the wrong default to be sticky. |
| 4 | Who else sees it? | Nobody. Not guests, not other readers, not moderators, not admins. |
| 5 | What happens to a deleted reader's quotes? | Their quotes are **deleted**, superseding the v1 nullify-and-keep rule. |
| 5b | Existing orphaned rows? | None exist — no migration, no filter. |
| 6 | Where is the chapter total shown? | On an author-only metric badge in the chapter header's existing metric row, beside reads and word count. At zero it reads « 0 citation » and is inert — no separate empty-state message. |
| 7 | Where does the per-passage marker go? | Right margin, `md+` only; nothing below `md`, matching the reader-side rule. |
| 8 | How does the author see stale quotes, which cannot be tinted? | Clicking the badge opens a chapter summary listing the quoted passages; stale ones appear there, badged « Passage plus présent dans le chapitre ». This is what makes the badge's count fully explainable and closes the discrepancy. |
| 9 | What is one row of that summary? | One row per **passage**, with its count. Not one row per quote, and no reader names — those stay in the in-text popover. |
| 10 | What does clicking a summary row do? | Closes the popup, turns the heat on if it was off, scrolls to the passage and opens its reader popover. Stale rows are inert. |
| 11 | Where does the heat toggle sit? | As an icon next to the badge, in the same metric row. |
| 12 | Are readers told the author sees an aggregate? | No indicator. The author is already notified of each individual quote. |

## 8. Out of scope

- **Anything that exposes a reader's private note** to the author, in any
  aggregated, anonymised or summarised form.
- **Moderation of quotes and notes** — backlog task #2.
- **Moderator/admin access** to this view.
- **Statistics**: no "quotes received" metric, global or per-user; no
  most-quoted-passage ranking.
- **Notifying the author** differently (digest, grouping by passage) — the
  per-quote notification stays as is.
- **Telling readers** that the author can see the aggregate.
- **A story-level view** of quoted passages, or any list living outside the
  chapter reading page. The summary of §4.2 is in scope; a "top passages across
  the story" screen is not.
- **Export, sorting or filtering** of the reader list or the summary.
- **Marking a quote as seen/read** by the author.
- **Reader-visible aggregates** — readers do not learn that others quoted the
  same passage.

## 9. Open questions

*The three questions raised at REFINE were all resolved by the user on
2026-07-28 — decisions #8 to #12. None remain.*

- ~~The badge count can exceed what is tinted~~ — resolved by the chapter
  summary (decision #8): stale quotes stay visible and explained there, so the
  number is always accountable.
- ~~Transparency toward readers~~ — resolved: no indicator (decision #12).
- ~~Where the toggle sits~~ — resolved: next to the badge in the chapter
  header's metric row (decision #11).

Remaining unknowns are technical and belong to DESIGN: how the aggregate is
computed and shipped to the client, and how the summary reconciles server-side
rows with client-side re-anchoring in order to know which passages are stale.

## Assumptions (not asked, open to veto)

| # | Assumption |
|---|------------|
| A1 | Co-authors see the view exactly like the author. |
| ~~A2~~ | ~~Access is gated on authorship only; a demoted author keeps the view.~~ **Overturned by the user** — see decision #17: authorship **and** `user-confirmed`. |
| ~~A3~~ | ~~The toggle state persists client-side per browser.~~ **Overturned by the user** — see decision #14: no persistence at all. |
| A4 | The reader list is ordered newest first and shows avatar, display name and relative date, linking to the reader's profile page. |
| A5 | The badge number is a server-side count of stored quotes, stale ones included — the summary is what explains the gap between it and the tinted passages. |
| A6 | No new notification, no new domain event, no statistics counter, no change to `hide-quotes-tab`. |
| A7 | At zero quotes the badge renders but is inert; no empty-state message. |
| A8 | Summary rows are ordered by count, highest first, with stale passages last. |
