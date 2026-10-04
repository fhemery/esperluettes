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
| 13 | 2026-10-04 | VERIFY | Cross-block selection (A17): hide « Annoter » / emoji, and what about « Citer »? | Only « Annoter » and ❤️ 🔥 👍 must require a single text block; Quote's « Citer » keeps its rule. Built as an opt-in boolean `data-requires-single-area` on the declaring element, read by the shared toolbar (the selector stays the contributing domain's) | A17 |
| 14 | 2026-10-04 | VERIFY | Stale pending edit after reload (A16): only « Tout annuler » clears it | The commenter must be able to remove just that change. Choice of means left to BUILD: the pop-up lists each pending edit / delete whose id is absent from the loaded server list as its own row, flagged `errors.stale` before any save, with « Retirer » (same `undo`). Chosen over auto-pruning in the banner, which would silently discard typed text (A3: the user discards it) | A16 |

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
| A12 | Save banner names refused items: a pending add by its highlighted passage, an edit / delete by a generic label (`changes_banner.item_edit` / `.item_delete`, two lang keys beyond the plan's list) — the banner has no saved-row text. A 404 marks every key with the `errors.stale` message. The hidden own-root « N annotations » button is also rendered by the lazy-loaded fragments (`CommentController@items` now passes `annotationsEnabled`), so an own root on page 2+ gets it too | BUILD phase 9 | yes |
| A13 | Pop-up: `remove(row)` keeps v1's immediate moderator delete and stores a pending delete only for the `commenter` role (a moderator writing their own root is `commenter`, so the two never mix). Pending adds get only « Annuler la modification » in the pop-up (no « Modifier » there; the plan listed none). « Retirer » on an error line calls the same `undo`; the row's undo button is hidden while the error line shows. Save errors for another comment id are ignored | BUILD phase 10 | yes |
| A14 | Pop-up replies: the single reply editor rests hidden at the bottom of the modal partial and `startReply` moves its DOM node under the answered row (parked back on cancel, send and on every `open`). Empty / too-long bodies are refused client-side (`replies.empty_body` / `.too_long`); a failed POST shows `server_modal.action_error`, so `postReply` / `deleteReply` keep throwing (A10 unchanged). « Répondre » follows the server's `can_reply` only (no client role check). No `unlockCommenter` helper (no test needed it). `plainText` is now exported by `capture-form.js`. Extra lang keys `replies.body_label` (sr-only editor label) and `server_modal.undo_edit`: undo reads « Annuler la modification » on an edited row, « Annuler » on a pending add / delete (01-functional gives no wording) | BUILD phase 11 | yes |
| A15 | E2E round trip runs on `chapitre-illustre-7` (no spec used its thread; `confirmed` posts the root there). `LONG_PARAGRAPH` kept: the spec checks the over-cap refusal as `admin` on the co-authored chapter (the only non-collaborator who can annotate there). Deleted as callerless: `evidence`, `storedDrafts`, `storedRootBody`, `captureHighlight`, `captureError`, `captureCancel`, `quoteButton`, `editDraft`, `allCountButtons`, `LoginPage.logout`. `ChapterPage.selectAcross` left alone: it comes from the chapter-choice feature, not v1 annotations | BUILD phase 12 | yes |
| A16 | Stale item: covered as the checklist words it (the pop-up still holds the cached row, so it is flagged and « Retirer » works). After a **reload**, the server list no longer has the row, so the pending edit has no row to flag and no per-item « Retirer »: the banner names it, and only « Tout annuler » (which also drops the valid changes) clears it. Read from `modal.js` `rows`; not reported as a FAIL — flagged for the user | VERIFY | yes |
| A17 | « Chapter, refused selection » marked ❌ for the cross-block half only: the checklist says hidden, the plan (phase 8) made an emoji click on a multi-block selection a no-op, and v1's « Annoter » already stays visible there (its form shows the error). Hiding needs the shared toolbar's `data-requires-selection-within` rule to require a single block, which also changes Quote's « Citer » — not a local fix, left to BUILD/the user. The spec keeps the expectation as `test.fail` and pins today's no-op (nothing stored) | VERIFY | yes |
| A18 | Fixed in VERIFY: « Supprimer la réponse » used `color="danger" :outline="true"`, whose shared style paints the pale error background as text (1.2:1). Now filled `danger`, like « Supprimer l'annotation » beside it. The shared `.surface-error.is-outline` style is left alone (this was its only user) | VERIFY | yes |
| A19 | Rows answered by PHP tests, not the browser: beta reader, deactivated / deleted reply writer, news article (tests named in the checklist). `ChapterPage.citeButton` now targets `.quote-toolbar-btn` (the old attribute selector also matched « Annoter » and the emoji). `evidence` / `captureHighlight` re-added with callers. Stepwise `CommentThread.scrollDown` added: at 375 px a jump to the bottom can skip the lazy-load sentinel once the banner pads the page (test artefact, not a defect — a finger scroll passes over it) | VERIFY | yes |
