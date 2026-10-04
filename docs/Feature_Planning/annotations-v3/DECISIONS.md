# Chapter annotations — v3 (in-chapter display) — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-04 | REFINE | Where do annotation avatars go? | Quote's 24px gutter at `md`+, same layout `md` and `lg`; no inline icon; filter menu at the top of the article | vision #15 |
| 2 | 2026-10-04 | REFINE | Author's heat map vs annotation overlay | Heat on hides annotation tint, avatars and filter menu; heat off restores them | — |
| 3 | 2026-10-04 | REFINE | Reader's quote tint vs annotation tint | Both shown, distinct styles; quote tint stays clickable; avatar opens the annotation | — |
| 4 | 2026-10-04 | REFINE | Moderators' in-chapter display | None — pop-up only | vision §3/§7 (moderator in-chapter view) |
| 5 | 2026-10-04 | REFINE | Moderator « Vider le contenu » on one annotation | Body → « <Annotation supprimée> »; anchor, tint, replies kept; commenter may delete, no longer edit | — |
| 6 | 2026-10-04 | REFINE | Commenter's own popover | Full: « Modifier » / « Supprimer » (draft or pending change), « Répondre » per v2 rule; pending-deleted annotations hidden from text | — |
| 7 | 2026-10-04 | REFINE | Replay of the whole flow + assumptions A1–A10 | Approved as written | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Annotation tint is a coloured underline (not a background) | REFINE | yes |
| A2 | « +N » = number of *other* root annotations in the group | REFINE | yes |
| A3 | `moved` anchors are tinted on their new passage with no extra marker | REFINE | yes |
| A4 | Filter menu is author-only and shown only when the chapter has a visible annotation | REFINE | yes |
| A5 | Processed annotations, when shown, are dimmed | REFINE | yes |
| A6 | Deleted (anonymised) commenter: default avatar + anonymised name in-chapter and in the filter | REFINE | yes |
| A7 | Tapping the tint below `md` does nothing | REFINE | yes |
| A8 | « Vider le contenu » on root annotations only; replies keep « Supprimer »; processed flag kept | REFINE | yes |
| A9 | No notification, event or audit entry for « Vider le contenu » | REFINE | yes |
| A10 | Avatars / arrows keyboard-focusable with labels, same bar as Quote's pills | REFINE | yes |
