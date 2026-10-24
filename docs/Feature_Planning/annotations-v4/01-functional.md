# Chapter annotations — v4 (per-annotation Report) — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

An annotation, or a reply to one, can be reported to the moderators on its own,
instead of reporting the whole root comment it hangs from. This reopens v1
decision #3 (reports targeted the root comment). It lets a chapter author
report an abusive reader annotation, and a commenter report an abusive author
reply. Neither of those people could reach the other's text through an
existing report. Image annotation (theme H of `00-request.md`) is split out
into its own task (decision #1).

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Annotation | A highlighted passage plus a body, attached to a reader's root comment (v1–v3). A *reaction* (❤️ 🔥 👍) is an annotation whose body is a single emoji. |
| Réponse à une annotation | One-level reply in an annotation thread, author ↔ commenter (v2). |
| « Signaler » | The existing report action of the Moderation domain, now offered on an annotation or reply. |
| Sujet « Annotation » | The new moderation topic that reports on annotations and replies are filed under. |

## 3. Roles & visibility

Who sees an annotation thread is unchanged from v1–v3: the commenter, the
chapter's authors / co-authors and moderators. Nobody else does. Reporting
never widens that.

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Nothing | Nothing |
| `user` (non-confirmed) | Annotation threads they are a party to (as v1–v3) | « Signaler » on any annotation or reply in such a thread that is not their own (A6) |
| `user-confirmed` | Same | Same |
| Commenter (annotation's author) | Their annotations and the replies to them | « Signaler » on the authors' replies; not on their own annotations or replies |
| Author / co-author of the chapter | All published annotations and replies on the chapter | « Signaler » on readers' annotations and replies, and on another co-author's reply; not on their own |
| Moderator | All published annotations (pop-up only, as today) | « Signaler » is shown as on comments (A3). Acting on the content stays with the existing « Supprimer » / « Vider le contenu ». Reviews reports in the existing admin. |
| Admin | As moderator | As moderator |

## 4. Functional requirements

### 4.1 Reporting an annotation or a reply

1. The viewer sees an annotation or reply they can see but did not write, in
   one of two places:
   - the in-chapter popover (v3);
   - the « N annotations » pop-up under a root comment (v1/v2).
2. A « Signaler » action is offered on it, on **both surfaces** (decision #3).
   It is offered on root annotations and replies alike (decision #2), and on
   reactions too, without a special case (A4).
3. It is **not** offered:
   - on the viewer's own annotation or reply;
   - on an annotation whose content was emptied by a moderator (A3).
4. Clicking it opens the existing moderation report modal: a reason select and
   an optional description of up to 1000 characters.
5. On submit, a report is created under the topic « Annotation » (A5) and the
   modal confirms as it does for every other topic.
6. **Server-side guard** (A1): the report is refused in three cases:
   - the reporter cannot see the annotation (not the commenter, not an
     author / co-author of its chapter, not a moderator);
   - the reporter wrote it;
   - the annotation does not exist.

   Without this guard, a private annotation could be pulled into a moderator
   snapshot by id.
7. Duplicate reports by the same user on the same annotation are accepted, as
   for every other topic (A2).

### 4.2 What the moderator receives

1. The report appears in the existing moderation reports admin, with topic
   « Annotation ».
2. Its snapshot is frozen at report time (decision #4) and holds:
   - the highlighted passage;
   - the annotation's body;
   - for a reply, also the annotation it answers (passage + body), since a reply
     alone is often unreadable.
3. « Ouvrir » leads to the root comment the annotation hangs from, on its
   chapter (decision #5). From there the moderator opens the « N annotations »
   pop-up and uses the existing actions: « Supprimer » on a root or reply,
   « Vider le contenu » on a root.
4. Approve / Dismiss / Delete report behave as for every other topic. Approving
   a report does not touch the annotation.

### 4.3 Reasons

The topic ships with a single seeded reason, « Autre », like every other topic
(decision #6). Moderators curate the list in the existing reasons admin.

## 5. Lifecycle

- **Annotation deleted or emptied after the report** (by its writer, by a
  moderator, or by emptying / deleting the root comment): the report survives
  with its frozen snapshot, as reports on comments do. « Ouvrir » may then lead
  to a root comment with nothing left to show, or to a missing comment.
- **Chapter edited** so the passage no longer re-anchors: no effect on the
  report. The snapshot holds the passage as it was.
- **Reporter or reported user deactivated or deleted**: existing Moderation
  behaviour, unchanged. The pop-up already hides deactivated users' annotations,
  so they cannot be reported while deactivated.
- **Chapter / story deleted**: as above, the report survives with its snapshot.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Anyone who sees the thread, except the writer; `user` and `user-confirmed` alike (A6). |
| Visibility / privacy | No widening. The server refuses reports from people who cannot see the annotation (A1). The snapshot is seen only by moderators. |
| Settings | N/A — no preference. |
| Notifications | None new. The reported user is not notified; Moderation behaviour is unchanged. |
| Domain events | Existing `ReportSubmitted` etc. from Moderation, nothing new for annotations. |
| Statistics | N/A. |
| Moderation | New topic « Annotation », formatter, seeded « Autre » reason. Content actions stay the existing pop-up ones. |
| Lifecycle / cascade | Reports outlive the annotation (§5). |
| Media | N/A — text only (image annotation split out). |
| Search | N/A. |
| i18n | French labels: « Signaler », topic « Annotation », reason « Autre ». |
| Mobile | The pop-up carries « Signaler » on every width. The in-chapter popover exists only on `md+`, as in v3. |
| Accessibility | Same report button and modal as elsewhere, so they keep their existing keyboard and labelling behaviour. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Keep image annotation (H) in v4? | No. Split into its own backlog task; v4 is the per-annotation Report only. |
| 2 | Are replies reportable too? | Yes, roots and replies. |
| 3 | Where is « Signaler » offered? | Both the in-chapter popover and the per-commenter pop-up. |
| 4 | Snapshot content | Highlighted passage + body; for a reply, also the parent annotation. |
| 5 | « Ouvrir » link target | The root comment anchor on the chapter (existing comment URL). No auto-opened pop-up. |
| 6 | Seeded reasons | « Autre » only, like every other topic. |

## 8. Out of scope

- Image annotation, and the stable block identity it needs (→ its own backlog
  task).
- Content actions on the admin report page (moderators act from the pop-up, as
  for comments).
- An auto-opened / scrolled pop-up from the « Ouvrir » link.
- A moderator in-chapter overlay (v3 non-goal, unchanged).
- Duplicate-report or self-report guards on other topics. The guard in §4.1.6
  covers the annotation topic only.
- Notifying the reported user or the reporter.
- A dedicated reason set beyond « Autre ».

## 9. Open questions

None.
