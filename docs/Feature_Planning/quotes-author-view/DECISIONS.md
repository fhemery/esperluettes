# Quotes — in-chapter author view — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-07-28 | REFINE | What does the author see on a quoted passage — count, reader list, or both? | Both: aggregate count **and** the list of readers who quoted it. Settles the question left open by Quotes v1 (`../Quotes.md` §11). | — |
| 2 | 2026-07-28 | REFINE | How are overlapping-but-different quoted ranges rendered? | Heat map — the tint deepens with the number of quotes covering that text. Not a flat tint, not exact-passage grouping. | — |
| 3 | 2026-07-28 | REFINE | Is the view always on when an author reads their own chapter? | No — a toggle on the chapter page, **off by default**, state remembered per user. Rationale: authors re-read and proofread their own chapters constantly. | — |
| 4 | 2026-07-28 | REFINE | Who besides the chapter's author(s) can see this view? | Nobody — no guests, no other readers, no moderators, no admins. Moderator access stays with backlog task #2 (`quotes-moderation/`). | — |
| 5 | 2026-07-28 | REFINE | What does the author view do with a quote whose owner deleted their account (`user_id` nulled in v1)? | Reverse the v1 rule: **delete the quotes** when the user is deleted, instead of nullifying and keeping the row. The orphan case then cannot occur. | Quotes v1 lifecycle rule (`NullifyUserOnUserDeleted`), documented in `app/Domains/Quote/AGENTS.md` |
| 6 | 2026-07-28 | REFINE | What about quote rows already orphaned by the v1 listener? | There are none currently — no data migration, no read-time filter, no `down()` concern. | — |
| 7 | 2026-07-28 | REFINE | Where is the chapter total shown, and what is the empty state? | On an author-only metric badge in the chapter header's existing metric row, beside reads and word count. At zero it renders « 0 citation » and is inert — no separate empty-state message. | — |
| 8 | 2026-07-28 | REFINE | Where does the per-passage marker go, and what about mobile? | Right-margin marker with the count on `md+` only; below `md`, tint only and no marker. Matches the reader-side rule in `../Quotes.md` §4.2. Clicking the tint opens the popover on every viewport. | — |
| 9 | 2026-07-28 | REFINE | Open question 1 — the badge counts stale quotes that cannot be tinted. How is that gap made legible? | Clicking the badge opens a **chapter summary** popup listing the quoted passages, where stale ones appear badged « Passage plus présent dans le chapitre ». No precedent existed in `../annotations/01-functional.md` — this is new surface. | resolves open question 1 |
| 10 | 2026-07-28 | REFINE | What is one row of that summary? | One row per **passage** with its count. Not one row per quote; no reader names (those stay in the in-text popover), to keep the popup short on a well-read chapter. | — |
| 11 | 2026-07-28 | REFINE | What does clicking a summary row do? | Closes the popup, turns the heat on if it was off, scrolls to the passage, opens its reader popover. Stale rows are inert — they have no location to scroll to. | — |
| 12 | 2026-07-28 | REFINE | Open question 3 — where does the heat toggle sit? | As an icon next to the citations badge, in the same metric row. | resolves open question 3 |
| 13 | 2026-07-28 | REFINE | Open question 2 — are readers told the author sees an aggregate? | No indicator. Fine as is, since the author is already notified of each individual quote. | resolves open question 2 |
| 14 | 2026-07-28 | REFINE | Is the heat toggle's state remembered between page loads? | **No — no persistence of any kind**, neither `localStorage` nor a user setting. It resets to off on every page load and every chapter. Rationale, in the user's words: authors must not have to keep *un*toggling; a clean page is the default worth having, and asking for the heat each time is cheaper than switching it off each time. | supersedes assumption A3, and the "state remembered per user" clause of decision #3 |

| 15 | 2026-07-28 | DESIGN | Where is the aggregate computed — server-side, or client-side from raw rows? | **Ship raw rows, aggregate client-side.** Staleness is only knowable after re-anchoring, which is client-side by v1 design; a server-computed aggregate cannot mark stale passages and its grouping would disagree with the tint on screen. Rejected: server-side grouping with counts and reader lists. | — |
| 16 | 2026-07-28 | DESIGN | What does an author's chapter page pay on load? | **Server-side COUNT for the badge, rows fetched on demand.** The badge must be correct at first paint; the rows, usually never opened, are not paid for. Rejected: everything lazy (badge pops in), everything server-rendered (every view pays). | — |

| 17 | 2026-07-28 | DESIGN | Must the endpoint stay reachable for an author demoted to non-confirmed `user`? | **No.** A demoted user does not need the view, and story creation already requires `user-confirmed`, so the case should never arise in practice. The endpoint joins the **existing** `role:user-confirmed` route group instead of getting one of its own. Low risk, less code. | supersedes assumption A2 |
| 18 | 2026-07-28 | DESIGN | How does the endpoint resolve the chapter's story for authorisation? | **Add `StoryPublicApi::getStoryIdByChapterId()`** and call it. Story owns the chapter→story relation, so it should answer the question directly rather than have Quote infer it. The endpoint takes `chapter_id` alone — no `story_id` parameter exists to forge. | supersedes design call D2 |

| 19 | 2026-07-28 | PLAN | `isAuthorOrCoAuthor()` returns true for **beta readers** (it plucks every collaborator role). Should a beta reader see the author view? | **No — authors only.** The policy uses `getAuthorIds()` (`role = 'author'`), which covers the author and any co-author. §3 of the spec lists nobody else, and the view exposes reader identities. Surfaced by the planner while verifying signatures. | — |

| 20 | 2026-07-28 | PLAN | `isAuthorOrCoAuthor()` is misnamed (it means "is any collaborator") and has exactly one caller, `QuotePolicy::canQuote()`. Remove it? | **Yes — remove it, as a prequel refactoring.** "Co-author" is meaningless in this design: once an author names another author, both hold identical rights and neither can remove the other, so there is only *author*. `canQuote()` switches to the author-only check, which means **beta readers become able to quote** a story they beta-read. That is intended: `../Quotes.md` decision #2 blocks "authors and co-authors" and never mentions beta readers, so blocking them was an accident of the misnamed method. The prequel is therefore a bug fix, not a rename. | — |
| 21 | 2026-07-28 | PLAN | Should chapters move to MultiEdit before this feature is built? | **Yes.** Chapters migrate to MultiEdit first; **this feature is BLOCKED for implementation** until that lands. REFINE, DESIGN and PLAN stand — only BUILD is held. Constraint to carry into the MultiEdit work: every quotable text block must render inside a single `[data-quote-article]` root, or both v1 reader quotes and this feature lose their anchoring. | — |

### Design calls made without asking (not user tradeoffs)

| # | Question | Chosen | Why |
|---|----------|--------|-----|
| D1 | Reuse `QuoteDto` for the author payload? | New `AggregateQuoteDto` with **no note field** | Makes the privacy guarantee structural rather than conditional — a field that does not exist cannot leak. |
| ~~D2~~ | ~~Derive the story from the quote rows' denormalised `story_id`.~~ **Overturned by the user — see decision #18.** Inferring the story from Quote's own rows was a workaround for a question Story should simply answer; it also broke down on a chapter with no quotes. | — |
| D3 | How to render overlapping heat? | Segment by depth, wrap each segment once | Nested `<mark>` per quote is not implementable — `Range.surroundContents()` throws on partially overlapping ranges. |
| D4 | Summary grouping key | Normalised exact `highlighted_text` | Decision #10 asks for one row per passage; partial overlap has no well-defined "same passage". Overlap stays a heat concern. |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Co-authors see the view exactly like the author (follows the existing notification, which goes to authors + co-authors). | REFINE | Yes — cheap. |
| ~~A2~~ | ~~Access is gated on authorship only; a demoted author keeps the view.~~ **Overturned by the user at DESIGN — see decision #17.** | REFINE | Resolved. |
| ~~A3~~ | ~~The toggle state persists client-side per browser.~~ **Overturned by the user before DESIGN — see decision #14.** No longer an assumption. | REFINE | Resolved. |
| A4 | The reader list is newest-first, showing avatar, display name and relative date, linking to the reader's **profile page** (not their quote book, whose visibility keeps following `canViewQuoteBook`). | REFINE | Yes — cheap. |
| A5 | The badge's number is a server-side count of stored quotes, stale ones included; the summary (decision #9) is what explains the gap with the tinted passages. | REFINE | Yes — cheap. |
| A6 | No new notification, no new domain event, no statistics counter, no change to the `hide-quotes-tab` setting. | REFINE | Yes — each would be additive. |
| A7 | At zero quotes the badge renders but is inert; no empty-state message. | REFINE | Yes — cheap. |
| A8 | Summary rows are ordered by count, highest first, with stale passages last. | REFINE | Yes — cheap. |
