# Chapter annotations — v2 — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-03 | REFINE | v2 scope (8 themes too big) | Writer side: A reactions + B post-publish add/edit/delete + C replies. D/E/G → `annotations-v3`, F/H → `annotations-v4` | — |
| 2 | 2026-10-03 | REFINE | Post-publish commit mode | Pending changes in browser + sticky save banner, one atomic save (keeps vision #18) | — |
| 3 | 2026-10-03 | REFINE | Notify on annotation replies? | No; hint nudging the author to reply on the root comment (keeps vision #8) | — |
| 4 | 2026-10-03 | REFINE | Commenter edits a processed annotation | Processed reset to unprocessed | — |
| 5 | 2026-10-03 | REFINE | Who may reply | Author/co-author (not beta) on any root annotation; commenter only once an author replied; moderators never | — |
| 6 | 2026-10-03 | REFINE | Annotate after a moderator emptied the root comment? | Yes (recommended no; user chose yes) | — |
| 7 | 2026-10-03 | REFINE | Commenter deletes an annotation with replies | Replies go with it; confirmation asked | — |
| 8 | 2026-10-03 | REFINE | Toolbar content | « Annoter » + ❤️ 🔥 👍 (unicode), no « + » | — |
| 9 | 2026-10-03 | REFINE | Reply edit/delete | Writer may delete, immediately; no edit | — |
| 10 | 2026-10-03 | REFINE | Replies of a deactivated user | Hidden while deactivated, back on reactivation, no row touched | — |
| 11 | 2026-10-03 | DESIGN | Where pending post-publish changes live | New `annotationChanges` slot in the comment-draft store (not a separate module/key) | — |
| 12 | 2026-10-03 | DESIGN | How "an author has replied" is decided | Derived: a visible reply whose writer ≠ the root's writer; no `is_author_reply` column | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Replies show writer name + date; visible to commenter, authors, moderators only | REFINE (replayed, accepted) | yes |
| A2 | Commenter's pop-up shows saved rows with pending markers + pending adds; per-row undo | REFINE (replayed, accepted) | yes |
| A3 | A stale pending item fails the whole save; error names it; user can discard it | REFINE (replayed, accepted) | yes |
| A4 | No events, no statistics, no new moderation topic | REFINE (replayed, accepted) | yes |
| A5 | v1 leftovers folded in (highlight cap from policy, counts respect gate, beta-reader test, e2e helpers); emoji-list de-dup → v3 | REFINE (replayed, accepted) | yes |
| A6 | v3 = D+E+G (+ emoji de-dup); v4 = F+H | REFINE (replayed, accepted) | yes |
| A7 | `PUT …/annotations` is a JSON body, so Laravel's TrimStrings trims edge spaces of `prefix`/`suffix` on adds (the v1 create path, a JSON string in one input, keeps them). Left as is: Quote already lives with it and re-anchoring compares normalized words | BUILD phase 4 | yes |
| A8 | `annotatedChapterCommentPayload` / `annotationItem` moved from `PostCommentWithAnnotationsTest` to `Comment/Tests/helpers.php` — file-local Pest helpers broke under parallel runs once a second file used them | BUILD phase 4 | yes |
| A9 | Deactivating or deleting a *current* author soft-deletes/removes the story (Story listeners), so a hidden-reply case under a live chapter only arises for a **former** co-author. The deactivated/deleted-writer tests seed the reply from a non-collaborator standing in for one. Reply DTOs carry `prefix`/`suffix` null (no anchor) | BUILD phase 5 | yes |
| A10 | `postReply` / `deleteReply` throw on any non-2xx (like v1's `setProcessed`), including a 422 on reply length; phase 11 may switch `postReply` to resolve 422 errors if the pop-up needs the message. `setPendingEdit` does not refuse an id already pending deletion (the UI is expected not to offer it) | BUILD phase 7 | yes |
| A11 | `<x-comment::reaction-buttons>` takes an `entity-type` prop besides `can-annotate`: it carries the policy's highlight cap and the user id on its root (it is cloned into `<body>`, outside the annotable), while entity id and mode come from the `[data-annotable]` holding the selection. `capture-form.js` exports `readSelection(maxLength)`, shared by both. `openEdit({ tempId })` reads the pending adds in pending mode. The "canAnnotate false" view case uses a co-author (the policy no longer has another logged-in denial) | BUILD phase 8 | yes |
