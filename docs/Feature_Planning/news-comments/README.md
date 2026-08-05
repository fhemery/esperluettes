# News comments

> WRAP output — the compact record of the finished feature. **This is the only
> file in the folder an agent should load by default.** The phase documents
> (`01`–`03`) remain as history.

**Status:** DONE — 2026-08-05 · **Domain(s):** `News` (consumer of `Comment`,
`Notification`) · **Spec:** [functional](./01-functional.md) ·
[architecture](./02-architecture.md) · [plan](./03-plan.md) ·
[decisions](./DECISIONS.md)

## What it does

A published news article carries a one-level comment thread (roots + replies),
rendered by Comment's existing `<x-comment::comment-list-component>`. News owns
nothing but the integration: a `CommentPolicy` registered for entity type
`news`, a `CommentPosted` listener that notifies thread participants on a reply,
and a `deleteFor()` call in `NewsService::delete()`. **No table, no route, no
controller, no JS and no change inside Comment, Moderation or Notification.**
Reporting, editing, moderation, deep links and drafts are Comment's existing
machinery, entity-type-agnostic already.

## Key behaviour

- **Guests see a members-only prompt, not the thread** — read included.
  `CommentPublicApi::checkAccess()` refuses every logged-out viewer for every
  entity type; §3 of the functional spec originally claimed otherwise and was
  corrected (decision #10). Same as chapters.
- **Root comments: published articles only.** `NewsCommentPolicy::canCreateRoot()`
  returns `true` iff the article exists and `status === 'published'`; it returns
  `false` (never throws) for an unknown id. The Blade block is gated on the same
  condition, so a draft preview shows no thread at all — not an empty one.
- **Replies are gated in `validateCreate()`, not `canReply()`.** Comment's create
  path enforces `canCreateRoot()` on roots but never calls `canReply()`;
  `validateCreate()` is the only hook that runs on both, so that is where a reply
  to a since-unpublished article is refused (same `body: Comment not allowed`).
  `canReply()` stays a constant `true`: Comment calls it once per rendered
  comment, so a lookup there would be one query per comment.
- **20-character minimum on roots, nothing on replies**, no maximum either way.
  Enforced on create *and* edit by Comment, from the policy's
  `getRootCommentMinLength()`.
- **No per-user cap and no author exclusion** — unlike chapters, the same user
  (including the article's creator) may post any number of root comments.
- **Root comments notify nobody.** A reply notifies the root author plus every
  prior replier on that thread, minus the replier — same fan-out as chapters.
- **Article deleted → thread deleted**, synchronously, before the row goes away.
  Unpublished → nothing to do: the page 404s for non-admins, so the thread is
  unreachable rather than actively hidden.
- **The thread is lazy** (`page="0"`, `perPage="5"`): items are absent from the
  server-rendered HTML and arrive from `GET /comments/fragments` when the
  sentinel scrolls into view. A deep-linked `?comment=<id>` is server-preloaded.

## Where the code lives

| Concern | Path |
|---------|------|
| Policy | `app/Domains/News/Private/Services/NewsCommentPolicy.php` |
| Reply notification listener | `app/Domains/News/Private/Listeners/NotifyOnNewsComment.php` |
| Notification content | `app/Domains/News/Public/Notifications/NewsReplyCommentNotification.php` (`news.reply_comment`) |
| Registrations | `app/Domains/News/Public/Providers/NewsServiceProvider.php` |
| Cascade delete | `NewsService::delete()` → `CommentMaintenancePublicApi::deleteFor('news', $id)` |
| View | `News/Private/Resources/views/pages/show.blade.php` — `<section id="comments">`, the anchor the post/edit redirect lands on |
| Strings | `News/Private/Resources/lang/fr/notification.php` |
| Tests | `News/Tests/Feature/NewsCommentPolicyTest`, `NotifyOnNewsCommentTest`, `NewsCommentsCascadeTest`, `NewsCommentSectionTest`, `NewsCommentNotificationSettingsTest` (29 cases) |
| e2e | `e2e/tests/core/comment-thread.spec.ts` (promoted, see below) |
| Migrations | none |

## Extension points used

- **`CommentPolicyRegistry`** — `register('news', NewsCommentPolicy)`; entity
  type string `'news'` is now persisted in `comments.commentable_type`, so it
  can never be renamed.
- **`CommentPosted`** (Events bus) — subscribed by `NotifyOnNewsComment`, which
  returns early unless `entityType === 'news'` **and** the comment is a reply.
- **`NotificationFactory`** — type `news.reply_comment` in a new group
  `news-comments` (order 45), `registerGroup()` called **locally** in
  `NewsServiceProvider` (decision #11). Surfaces as its own Settings group,
  *Commentaires d'actualités*, separate from Story's *Commentaires*.
- **Moderation** — nothing registered: the shared `'comment'` topic already
  covers any `commentable_type`. `getUrl()` supplies the deep link
  `/news/{slug}?comment=<id>` for the moderator's snapshot.
- **Deptrac** — two new edges, `NewsPublic → CommentPublic` (provider
  registration) and `NewsPrivate → CommentPublic` (policy, listener, cascade),
  mirroring Story's existing pair.

## Decisions worth remembering

1. Guests cannot read the thread (#10) — accepted shared Comment behaviour
   rather than changing Comment for News.
2. `news-comments` is registered locally, and `Notification/AGENTS.md`'s
   "never call `registerGroup()` from outside Notification" invariant was
   **corrected**, not worked around (#11): single-domain groups register
   locally; only cross-cutting groups belong to `NotificationServiceProvider`.
3. Lazy thread, matching chapters (#8) — no extra query for readers who never
   scroll.
4. Draft articles carry no thread at all, in the view *and* in the policy (#9).
5. No comment counter anywhere (list, carousel, Statistics) — out of scope by
   functional §8; do not add one "helpfully".

Two shared-Comment quirks observed at VERIFY, not defects of this feature:
posting from a deep link yields a doubled `?comment=a&comment=b` (PHP reads the
last), and deactivate/reactivate or account deletion bumps `updated_at`, so an
untouched comment can display "Modifié le …".

## Not done

- **Deliberate non-goals** (functional §8): distinct moderation reasons for news
  comments, nesting deeper than one level, a one-root-per-article cap, comment
  counters, search indexing of comment bodies, any new user flag beyond the
  existing `compliant` gate.
- Plan open item #3 (a reply was still possible on an article unpublished after
  the fact) is **closed**, not carried: `validateCreate()` now refuses it, with
  three cases in `NewsCommentPolicyTest`. The underlying Comment-wide gotcha —
  `canReply()` is never consulted on the create path, so no other consumer
  should rely on it either — is recorded in `app/Domains/Comment/AGENTS.md`.
- **e2e:** `e2e/tests/features/news-comments.spec.ts` (26 tests) is **deleted** —
  everything it asserted about policy, notifications, moderation and cascade is
  covered by the PHP feature tests above. One behaviour was **promoted** to
  `e2e/tests/core/comment-thread.spec.ts`: the lazy-load contract of
  `<x-comment::comment-list-component>` (no items in the HTML, items fetched
  from `/comments/fragments` on scroll), which belongs to Comment and breaks
  every consumer at once; the reason is written in the spec header. Page objects
  `CommentThread.ts` and `NewsArticlePage.ts` survive, trimmed to what that spec
  uses; `AdminUsersPage`, `ModerationReportsPage`, `NotificationsPage` and
  `ReportModal` are deleted, and the two extra seeded articles were removed from
  `E2eNewsSeeder` / `fixtures.ts`.
- No rows pushed back to `docs/Feature_Planning/BACKLOG.md`.
