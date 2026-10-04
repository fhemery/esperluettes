# Chapter annotations — v2 (writer side) — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.
>
> Starts from [`vision-spec.md`](../annotations-v3/vision-spec.md) (moved there at WRAP) (the original full spec) and
> what v1 shipped ([`_done/annotations.md`](../_done/annotations.md)). Everything
> v1 settled still holds unless this document says otherwise. v2 covers items
> **A** (quick-emoji reactions), **B** (post-publish add / edit / delete) and
> **C** (replies) of the v2 request; the rest moved to `annotations-v3/` and
> `annotations-v4/` (decision #1).

## 1. Overview

v1 lets a reader attach annotations to passages, but only once — they travel
with the root comment and are frozen afterwards; the chapter author can only
read them and mark them processed. v2 completes the *writer* side of the
conversation: one-click emoji reactions, adding / editing / deleting
annotations after the root comment is published (saved together from a banner),
and a short author ↔ commenter reply thread under each annotation.

There is still **no in-chapter display** of annotations (v3): everything after
capture happens in the existing « N annotations » pop-up on the root comment.

## 2. Vocabulary

Terms from v1 (*annotation*, *draft*, *root comment*, *processed*) are unchanged.

| Term | Meaning |
|------|---------|
| **Réaction** | An annotation whose body is a single unicode emoji (❤️, 🔥 or 👍), created in one click from the selection toolbar. Technically an ordinary annotation: it can later be edited into text. |
| **Modification en attente** (pending change) | An add, edit or delete of an annotation made by the commenter **after** their root comment is published, kept in the browser until saved. |
| **Bandeau d'enregistrement** (save banner) | The sticky bottom bar « Vous avez {N} annotations non sauvegardées » with an « Enregistrer » button that sends all pending changes at once. |
| **Réponse** (reply) | A second-level note under a root annotation, written by a chapter author/co-author or by the commenter. One level deep. |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Nothing (unchanged). | Nothing. |
| `user` (non-confirmed) | Same as `user-confirmed` — annotation rights follow the comment rights, which do not distinguish the two (unchanged from v1). | Same as `user-confirmed`. |
| `user-confirmed` (commenter) | Their own annotations, drafts, pending changes and the replies under their annotations. Never the processed flag. | Annotate before publishing (v1) **and after** (new, §4.2); react in one click (§4.1); edit / delete own annotations after publish (pending, §4.2); reply under an annotation **once an author has replied** (§4.3); delete own replies. |
| Author / co-author of the chapter (not a beta reader) | All annotations + all replies, processed flag (unchanged + replies). | Mark processed (v1); **reply** to any root annotation; delete own replies. Cannot annotate (cannot comment own chapter). |
| Beta reader | As any reader (no author rights). | As any reader. |
| Moderator / admin / tech-admin | All annotations + replies (+ processed flag in data, unchanged). | Delete an annotation (v1) **or a reply** with the existing « Supprimer ». Never replies. |
| Other readers | Nothing. | — |

## 4. Functional requirements

### 4.1 Quick-emoji reactions

1. A reader who may annotate selects a passage in a text block. The selection
   toolbar shows « Citer » (if they may quote), « Annoter », and three emoji
   buttons ❤️ 🔥 👍 (decision #8). No « + » button.
2. Clicking an emoji immediately creates an annotation whose body is that
   unicode emoji, anchored to the selection — no form opens. The selection is
   cleared.
   - **Before** the reader's root comment exists: it is a **draft** (v1 drafts
     slot, same banner / drafts pop-up).
   - **After**: it is a **pending add** (§4.2).
3. Same capture rules as « Annoter »: text blocks only, no cross-block
   selection, highlight length cap. A refused selection hides the emoji buttons
   the same way it hides « Annoter ».
4. A reaction is edited/deleted like any annotation (drafts pop-up before
   publish, pending changes after).
5. Touch: same toolbar, same buttons.

### 4.2 Post-publish add / edit / delete (lifts v1's A5)

1. A reader who has a root comment on the chapter can now annotate: the
   toolbar shows « Annoter » and the emojis (v1 hid them). This holds even when
   a moderator has **emptied** that root comment (decision #6).
2. **Add**: « Annoter » opens the v1 capture form; « Enregistrer » in the form
   stores a **pending add** locally (nothing reaches the server yet).
3. **Edit / delete**: in the « N annotations » pop-up on their own root comment,
   each of the commenter's rows gains « Modifier » (reopens the capture form
   with the body) and « Supprimer ». Both store a **pending change**.
   - Deleting an annotation that has replies asks for confirmation
     (« Les réponses seront aussi supprimées. ») — decision #7.
4. The pop-up shows the commenter's saved rows with their pending state
   (« Modifiée — non enregistrée », « Sera supprimée ») and lists pending adds
   (assumption A2). A pending change can be undone from the row.
5. While at least one pending change exists, a **sticky bottom banner** shows
   « Vous avez {N} annotation(s) non sauvegardée(s) » with « Enregistrer »
   (and a way to discard all). It survives reloads (pending changes are stored
   per chapter **and** per user in the browser, like drafts).
6. « Enregistrer » sends all pending changes in **one atomic request**
   (decision #2). The root comment body is untouched.
   - Success: pending changes cleared, banner gone, pop-up reflects the server.
   - Failure (network, validation, or a **stale item** — annotation deleted by
     a moderator meanwhile, root comment gone): **nothing is applied**, pending
     changes and banner stay, the error names the offending item, and the user
     can discard it and save again (assumption A3).
7. A saved **edit** of an annotation the author had marked « traitée » resets it
   to unprocessed (decision #4). The commenter never sees the flag.
8. A saved **delete** removes the annotation and all its replies (decision #7).
9. No notification, no event (decision #3 for replies; v1 for annotations).
10. Before publish the v1 flow is unchanged (drafts, banner inside the
    root-comment form, posted with the root).

### 4.3 Replies

1. In the « N annotations » pop-up, under each **root** annotation, the
   thread of replies is shown in date order, each with the writer's display
   name and date (assumption A1).
2. **Who may reply** (decision #5):
   - a chapter author or co-author (not a beta reader) — on any root annotation;
   - the commenter (owner of the annotation) — **only once at least one author
     reply exists** under it;
   - nobody else; moderators never reply.
3. « Répondre » opens the same inline editor as annotations (bold, italic,
   emoji), same length limits (1–1000 plain chars). « Envoyer » saves
   **immediately** (no pending/banner).
4. After an **author** sends a reply, a short hint invites them to also reply
   to the root comment if they want the reader to be notified (decision #3).
5. The writer of a reply can **delete** it immediately (with confirmation).
   Replies cannot be edited (decision #9).
6. A moderator's existing « Supprimer » works on a reply too (removes that
   reply only).
7. Replies cannot be marked processed (processed stays on root annotations,
   unchanged). A root annotation hidden/removed takes its replies with it.
8. Replies are never shown outside the pop-up in v2.

## 5. Lifecycle

| Event | Effect |
|-------|--------|
| Commenter deletes an annotation (pending → saved) | Annotation and its replies removed (decision #7). |
| Commenter edits an annotation | Body updated; processed reset (decision #4); replies kept. Anchor (highlight, prefix, suffix) is immutable — editing changes the body only. |
| Moderator deletes an annotation | Annotation + replies removed (v1). |
| Moderator deletes a reply | That reply only. |
| Moderator empties the root comment | Annotations (and their replies) removed, as v1 #4. The commenter may annotate again afterwards (decision #6). |
| Root comment deleted / chapter deleted | Everything cascades (v1). Pending changes for it become stale → save refused, user discards (A3). |
| User deactivated | Their annotations hide with their root comment (v1 #5). Their **replies** under other people's annotations are hidden from everyone while deactivated and come back on reactivation; no row is touched (decision #10). |
| User deleted | Annotations and replies kept, anonymised (v1 A3 extended to replies). |
| Chapter text edited | Anchors unchanged; no re-anchoring in v2 (v3). |

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | §3. `user` and `user-confirmed` treated alike (follows comment rights, unchanged). Beta readers have no author rights. |
| Visibility / privacy | Replies visible exactly to who sees the annotation: commenter, authors/co-authors, moderators. Processed stays author-only. Pending changes never leave the browser until saved. |
| Settings | N/A — no preference. |
| Notifications | None for reactions, post-publish changes or replies (decision #3); author nudge after replying. |
| Domain events | None (as v1, A4). |
| Statistics | None (A4). |
| Moderation | Moderator delete extends to replies. No per-annotation Report (v4). No « Vider le contenu » on one annotation (v3). |
| Lifecycle / cascade | §5. |
| Media | N/A — text only. |
| Search | N/A — annotations are never searchable. |
| i18n | French strings, plural-aware (« {N} annotation(s) non sauvegardée(s) »). |
| Mobile | Same toolbar (with emojis) on touch; pop-up and banner must work on phones; banner must not cover the comment form's submit button. |
| Accessibility | Emoji buttons carry accessible labels (« Réagir avec un cœur », …); banner announced politely. |

### v1 leftovers folded in (assumption A5)

- The highlight length limit on posting reads the per-type policy limit rather
  than a hard-coded 500.
- The annotation counts shown to authors/moderators also respect the policy's
  `canAnnotate`-type gate (v1 gave a count without asking it).
- A direct test that a beta reader sees no count.
- Unused e2e helpers left by v1's deleted spec are reused by v2's specs or pruned.

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | v2 scope | Writer side: A (reactions) + B (post-publish add/edit/delete) + C (replies). D/E/G → `annotations-v3`, F/H → `annotations-v4`. |
| 2 | Post-publish commit mode | Pending changes in the browser + sticky save banner, one atomic save (keeps vision #18). |
| 3 | Reply notifications | None; hint nudging the author to reply on the root comment (keeps vision #8). |
| 4 | Commenter edits a processed annotation | Processed reset to unprocessed. |
| 5 | Who may reply | Author/co-author (not beta) on any root annotation; commenter only once an author replied; moderators never. |
| 6 | Annotating after a moderator emptied the root comment | Allowed (recommendation was "no" — user kept it open). |
| 7 | Commenter deletes an annotation with replies | Replies go with it; confirmation asked. |
| 8 | Toolbar | « Annoter » + ❤️ 🔥 👍 (unicode), no « + » button. |
| 9 | Reply edit / delete | Writer may delete, immediately; no edit. |
| 10 | Replies of a deactivated user | Hidden while deactivated, back on reactivation, no row touched. |

## 8. Out of scope

- In-chapter display (tint, margin avatars, popover, re-anchoring, `missing`
  badge), filter menu, moderator « Vider le contenu » on one annotation →
  `annotations-v3/`.
- Per-annotation Report (`chapter-annotation` topic), image annotation →
  `annotations-v4/`.
- De-duplicating the custom-emoji class list in `config/purifier.php` → v3
  (nothing in v2 touches it: reactions are unicode).
- Editing a reply; replies deeper than one level; moderator replies.
- Notifications or events for annotations/replies; cross-device sync of
  drafts/pending changes; bulk operations.
- Changing an annotation's anchor (re-selecting the passage) — delete and
  re-create instead.
- Reactions other than ❤️ 🔥 👍 in the toolbar (other emojis via « Annoter »).

## 9. Open questions

None blocking.

- *Non-blocking (DESIGN)*: `vision-spec.md` lives in this folder and is the
  input for v3/v4; v2's WRAP must move it to `annotations-v3/` rather than
  delete it.

### Assumptions (made without asking, user saw them at replay)

| # | Assumption |
|---|------------|
| A1 | Replies show writer name + date; visible to commenter, authors, moderators only. |
| A2 | Commenter's pop-up shows saved rows with pending markers and lists pending adds; a pending change can be undone per row. |
| A3 | A stale pending item makes the whole save fail; the error names it and the user can discard it. |
| A4 | No events, no statistics, no new moderation topic. |
| A5 | v1 leftovers folded in (highlight cap from policy, counts respect the gate, beta-reader test, e2e helpers); emoji-list de-dup → v3. |
| A6 | v3 = D+E+G (+ emoji-list de-dup); v4 = F+H. |
