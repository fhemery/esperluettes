# New fanart / artwork module — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-17 | REFINE | REFINE scope for the epic | Whole epic refined into one 01-functional.md; split into sub-tasks/phases at PLAN | — |
| 2 | 2026-10-17 | REFINE | Where the mode d'accueil (images / audio: oui / non / fanartistes désignés) lives | Per user, Paramètres › Histoires ("per story would be too difficult"). On multi-author stories the most restrictive level chosen by the authors applies, with an explanation on the author view | — |
| 3 | 2026-10-17 | REFINE | Can a bêta-lecteur also be Fanartiste on the same story? | Yes, cumulative | — |
| 4 | 2026-10-17 | REFINE | Who validates a pending fanart on a co-authored story | Any one author | — |
| 5 | 2026-10-17 | REFINE | Whose chapter credit an artwork costs | The uploading author's | — |
| 6 | 2026-10-17 | REFINE | Credit refund when an author deletes their artwork | No refund | — |
| 7 | 2026-10-17 | REFINE | One audio per chapter: what happens to the previous one on replacement | Deleted, its fanartist notified | — |
| 8 | 2026-10-17 | REFINE | Can a fanartist see / withdraw their pending submissions? | Yes, on their profile tab with a pending badge | — |
| 9 | 2026-10-17 | REFINE | Pending fanart when the right to submit disappears (mode non, role revoked, story private) | Stays, author decides | — |
| 10 | 2026-10-17 | REFINE | Account deactivation (fanartist or author) | Soft-delete, restore on reactivation; co-authored story hidden only when all authors deactivated; account deletion hard-deletes fanartist work | — |
| 11 | 2026-10-17 | REFINE | Chapter unpublished | Its fanarts hidden from readers, back on republish | — |
| 12 | 2026-10-17 | REFINE | Co-author deletes account, story survives | Fanarts kept; only that author's own artworks removed | — |
| 13 | 2026-10-17 | REFINE | Can an author report a pending fanart? | Yes, « Signaler » on pending too | — |
| 14 | 2026-10-17 | REFINE | Direct moderator « Supprimer » on any fanart | Yes, authors + fanartist notified | — |
| 15 | 2026-10-17 | REFINE | Audio end in story page player | In scope: mark chapter read + play next chapter with audio | — |
| 16 | 2026-10-17 | REFINE | Cap on pending submissions / notifications | No cap for now | — |
| 17 | 2026-10-17 | REFINE | Feature toggle | None — "we'll publish only at the end, so you can leave pending work" | — |
| 18 | 2026-10-17 | REFINE | Accepted formats | Images jpg/png/webp (2 Mo), audio mp3 (15 Mo) | — |
| 19 | 2026-10-17 | REFINE | What guests / non-confirmed users see | Nothing at all | — |
| 20 | 2026-10-17 | REFINE | Can a fanartist edit their accepted fanart? | No, delete & resubmit | — |
| 21 | 2026-10-17 | REFINE | Discord relay (added by user): which fanarts | Images only, public stories only, fanartist opt-in at submission | — |
| 22 | 2026-10-17 | REFINE | Statistics | None for now | — |
| 23 | 2026-10-17 | REFINE | Fanartiste role request (added by user) | Any one author accepts/declines, no comment, requester notified either way, 30-day cooldown after decline | — |
| 24 | 2026-10-17 | REFINE | Description field (wireframe showed required 100–1000) | Optional, rich text, max 1000, no minimum | — |
| 25 | 2026-10-17 | REFINE | GIF images (wireframe hint) | No — jpg/png/webp | — |
| 26 | 2026-10-17 | REFINE | Attestation wording | Wireframe wording + AI clause | — |
| 27 | 2026-10-17 | REFINE | Button label | « Proposer un fanart » (authors: « Ajouter un artwork ») | — |
| 28 | 2026-10-17 | DESIGN | Where the Fanartiste role lives | In the Fanart domain (`fanart_fanartists`), shown as a section on Story's collaborators page; not a `story_collaborators` role (no private-story access) | — |
| 29 | 2026-10-17 | DESIGN | Thumbnails for confirmed-only images | Private image variants in Media (gated variant streaming, GC-aware) | — |
| 30 | 2026-10-17 | DESIGN | Keeping the reported file | Generic Moderation capability: snapshot `_media` paths copied via Media, kept while the report exists | — |
| 31 | 2026-10-17 | DESIGN | Does approving a report delete the fanart | No — "the report should send to the fanart, and there should be a moderation menu enabling to delete it like we have for comments". Spec §4.11.5 amended | REFINE §4.11.5 wording |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

Assumptions A1–A8 were read back to the user at REFINE and not vetoed.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Author reporting a pending fanart removes it from the queue (deleted; copy kept in report); fanartist gets the decline notification without comment | REFINE | Yes |
| A2 | Deleting a fanart (author/moderator) needs no comment; fanartist deleting own fanart notifies nobody | REFINE | Yes |
| A3 | Author's accept/decline comment only appears in the notification, never publicly | REFINE | Yes |
| A4 | Every fanart shows its artist's name linked to their profile | REFINE | Yes |
| A5 | Title ~100 chars, review comment ~500 chars, plain text (description rich, decision 24) | REFINE | Yes |
| A6 | Spoiler collapsing never applies to authors; applies to profile-tab viewers as on the story page | REFINE | Yes |
| A7 | Moderators/admins see the profile tab and pending items | REFINE | Yes |
| A8 | Publishing a fanart notifies no followers/readers | REFINE | Yes |
| A9 | Author artworks are Discord-eligible under the same rule (public story, author ticks « Partager sur Discord ») | REFINE | Yes |
| A10 | One pending role request per user per story, withdrawable; removed if role granted directly or story deleted | REFINE | Yes |
| A11 | Story pages get `<x-fanart::…>` component tags, no story-page registry | DESIGN | Yes |
| A12 | Discord relay = Fanart's own pending/mark-sent endpoints behind `discord.api`, signed expiring image URLs | DESIGN | Yes |
| A13 | Unpublished chapters / deactivated authors / visibility handled by read-time filtering, no listeners | DESIGN | Yes |
| A14 | Moderation copy retained as long as the report exists (spec open Q1) | DESIGN | Yes |
| A15 | Only `Fanart.DeletedByModeration` emitted as a domain event | DESIGN | Yes |
| A16 | Author reporting a pending fanart handled by a `Moderation.ReportSubmitted` listener in Fanart | DESIGN | Yes |
