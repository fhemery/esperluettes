# News comments — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Any logged-in, compliant user can comment on a published news article —
one-level threaded (root comments + replies), reusing the Comment domain's
existing rules, editor and moderation machinery. Readers can react, ask
questions or cheer the news author's work; the news author can join the
conversation too.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Commentaire racine | A top-level comment on a news article |
| Réponse | A reply to a root comment (one level only, no reply-to-reply) |
| Utilisateur conforme | A user who has accepted the terms and, if a minor, has verified parental authorization (existing `compliant` gate) |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | A members-only prompt with a login link, not the thread — same as chapter comments today (decision #10) | Nothing — must log in to read or post |
| `user` (non-confirmed) | Full thread, plus own draft state | Post root comments and replies, edit own comments, report others' — same rights as `user-confirmed`, gated only by the `compliant` middleware |
| `user-confirmed` | Full thread | Same as above |
| Author of the news article | Full thread | Same as any user — **can** post root comments on their own article (unlike a story author on their own chapter) |
| Moderator | All comments, including reported ones | Review reports, hide/delete via the existing shared `'comment'` Moderation topic |
| Admin | Everything a moderator sees | Same as moderator |

Non-compliant users (terms not accepted, or a minor without verified parental
authorization) are blocked from posting by the existing `compliant` middleware
— the same gate the Comment domain's own routes already use. This is not a new
concept.

## 4. Functional requirements

### 4.1 Posting a root comment

1. A logged-in, compliant user (`user` or `user-confirmed`, including the
   article's own author) views a **published** news article.
2. They write a comment of at least 20 characters using the standard rich-text
   toolbar (bold, italic, underline, strike, blockquote, align, list, emoji —
   same preset as chapter comments).
3. On submit, the comment is posted immediately, visible to all readers of the
   article. No notification is sent to anyone for a root comment.
4. A user may post any number of root comments on the same article — no
   one-per-user cap (unlike chapter comments).

### 4.2 Replying to a comment

1. A logged-in, compliant user replies to an existing root comment. There is
   no minimum length for a reply.
2. Replying to a reply is rejected — threading is one level deep, identical to
   chapter comments.
3. On submit, a notification is sent to: the root comment's author, plus
   everyone else who has already replied on that thread — excluding the
   replier themselves. This is the same fan-out chapter-comment replies
   already use.
4. Recipients can turn this notification off from Settings, under a new
   "comments" group specific to News (separate from the existing "article
   published" group).

### 4.3 Editing a comment

1. Any user can edit their own root comment or reply, at any time — no time
   window, matching chapter comments.
2. The 20-character minimum still applies when editing a root comment; replies
   remain unrestricted.

### 4.4 Reporting a comment

1. Any logged-in user can report a root comment or reply.
2. The report lands in the Moderation domain's existing `'comment'` topic —
   the same topic and reason list chapter comments already use. No new,
   news-specific reasons.
3. A moderator reviews and hides/deletes as they already do for chapter
   comments.

## 5. Lifecycle

- **Article deleted**: its entire comment thread (root comments and replies)
  is deleted with it.
- **Article unpublished**: it is only reachable by admins/tech-admins (draft
  preview); regular users can no longer reach its page, so its comment thread
  becomes unreachable too. Nothing extra to build for this state — it falls
  out of the existing publish-gated route.
- **Commenting user deactivated**: their comments are hidden, and restored if
  the account is reactivated — existing generic Comment-domain behavior, no
  News-specific override.
- **Commenting user deleted**: existing generic Comment-domain behavior
  applies (author reference nullified, comment content kept) — no
  News-specific override.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | `user` and `user-confirmed` both allowed, gated by `compliant` middleware; article author has no special restriction (unlike Story) |
| Visibility / privacy | Comments are public on a published article; no private field |
| Settings | New "comments" notification group under News, mirroring Story's pattern; user can toggle reply notifications off |
| Notifications | Root comments notify nobody; replies notify the root author + prior repliers on the thread, excluding the replier |
| Domain events | News should emit/consume events for comment cleanup on article deletion (technical detail for DESIGN) |
| Statistics | N/A — no existing comment-count metric to extend; out of scope for this version |
| Moderation | Reuses the Comment domain's existing shared `'comment'` topic and reason list — no distinct reasons for news comments |
| Lifecycle / cascade | Comments deleted when the article is deleted; unreachable (not actively hidden) when unpublished |
| Media | N/A — comments are rich text only, no image attachment |
| Search | N/A — comments are not indexed in global search |
| i18n | All user-facing strings in French, via the News/Comment domains' lang files |
| Mobile | N/A — reuses the existing Comment UI components as-is |
| Accessibility | N/A — reuses the existing Comment UI components as-is |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Moderation reasons: share with chapter comments, or extend Moderation for distinct reasons? | Share the existing `'comment'` topic and reason list — no Moderation change |
| 2 | Thread depth: one level (current Comment capability) or deeper nesting? | One level (root + replies), same as chapters |
| 3 | Reply notification fan-out | Root author + prior repliers on the thread, excluding the replier — same as chapter comments |
| 4 | What happens to comments when the article is deleted? | Cascade-deleted with the article |
| 5 | Does the 20-char minimum apply to replies? | No — root comments only, replies unrestricted |
| 6 | Settings grouping for the new notification | Own "comments" group under News, separate from the existing "article published" group |
| 7 | One root comment per user per article, like chapters? | No cap — unlimited root comments per user per article |

Mirror of `DECISIONS.md`, restricted to functional decisions.

## 8. Out of scope

- Distinct moderation reasons for news comments vs. chapter comments.
- Nesting deeper than one level (replies to replies).
- A one-root-comment-per-article cap.
- Comment counters in Statistics or on the news list/homepage carousel.
- Search indexing of comment content.
- Any new "banned" or "restricted" user flag — only the existing `compliant`
  gate applies.

## 9. Open questions

None blocking. Everything above was confirmed with the user during REFINE.
