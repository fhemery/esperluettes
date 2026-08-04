# News comments — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions log: [`DECISIONS.md`](./DECISIONS.md)

**No migration, no new table, no new route, no new Blade component, and no
change inside the Comment or Moderation domains.** If a phase seems to need one,
the phase is wrong — stop and report instead of inventing it. The only
`deptrac.yaml` change in the whole feature is the two edges named in phase 1.

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Policy — `NewsCommentPolicy` + registration + deptrac edges | S | — | DONE |
| 2 | Lifecycle — cascade-delete comments with the article | S | — | DONE |
| 3 | Notification — reply fan-out listener, content type, `news-comments` group | M | — | DONE |
| 4 | UI — comment thread on the article page | S | 1 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/4)` resume correctly.

Phases 1, 2 and 3 are mutually independent and can be built in any order.
Phase 4 must come after phase 1 — the server-side published-only gate lands
before the UI that relies on it (never ship a thread whose only protection is
the absence of a form).

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- French only, no literal strings in Blade or PHP: every new user-visible label
  lands in `app/Domains/News/Private/Resources/lang/fr/notification.php` in the
  phase that renders it.
- Tests are Pest feature tests under `app/Domains/News/Tests/Feature/`. The
  helper functions `alice()`, `bob()`, `carol()`, `admin()`, `createComment()`,
  `listComments()`, `generateDummyText()`, `dispatchEvent()`,
  `getLatestNotificationByKey()`, `getNotificationTargetUserIds()` are already
  globally available via `tests/Pest.php` — do not redefine them.

---

## Phase 1 — Policy: `NewsCommentPolicy` + registration + deptrac edges

**Goal.** Make `'news'` a first-class comment entity type with its own rules:
published articles only, 20-character minimum on root comments, no cap, no
author exclusion.

**Depends on architecture.** §3.3 (the full method table), §9 (first risk),
§5 (deptrac edges). Decisions #5, #7, #9.

**Deliverables.**

- `app/Domains/News/Private/Services/NewsCommentPolicy.php` — new, implements
  `App\Domains\Comment\Public\Api\Contracts\CommentPolicy`. Every method of the
  interface must be implemented (there is no trait or base class to extend;
  `DefaultCommentPolicy` is a separate concrete class, do not extend it).
  Behaviour, exactly per architecture §3.3:
  - `canCreateRoot(int $entityId, int $userId): bool` — look the article up
    directly (`News::query()->find($entityId)`; same-domain Private model
    access from a Private service is legal here) and return
    `$news !== null && $news->status === 'published'`. **It must return
    `false`, never throw, for an id that does not exist** — architecture §9
    risk 1. There is no author exclusion and no per-user cap: do not call
    `CommentPublicApi::userHasRoot()` the way `ChapterCommentPolicy` does.
  - `canReply(CommentDto $parentComment, int $userId): bool` — `true`.
  - `canEditOwn(CommentDto $comment, int $userId): bool` — `true`.
  - `validateCreate(CommentToCreateDto $dto): void` and
    `validateEdit(CommentDto $comment, int $userId, string $newBody): void` —
    no-ops (empty bodies).
  - `getRootCommentMinLength(): ?int` — `20`.
  - `getRootCommentMaxLength()`, `getReplyCommentMinLength()`,
    `getReplyCommentMaxLength()` — all `null`.
  - `getUrl(int $entityId, int $commentId): ?string` — `null` when the article
    does not exist, otherwise
    `route('news.show', ['slug' => $news->slug]) . '?comment=' . $commentId`.
- `app/Domains/News/Public/Providers/NewsServiceProvider.php` — in `boot()`,
  after the existing registry wiring:
  `app(CommentPolicyRegistry::class)->register('news', app(NewsCommentPolicy::class));`
- `deptrac.yaml` — add `CommentPublic` to the allowed list of **both**
  `NewsPublic` (the provider calls the registry) and `NewsPrivate` (the policy
  implements a Comment contract). These two edges mirror the ones `StoryPublic`
  / `StoryPrivate` already have; they are the only deptrac change this feature
  is allowed to make.

**Tests.** New file `app/Domains/News/Tests/Feature/NewsCommentPolicyTest.php`.
Drive the policy through the real `CommentPublicApi` (via the `createComment()`
/ `listComments()` helpers) rather than instantiating it by hand, except for
the `getUrl` cases where a direct `new NewsCommentPolicy()` is clearer — this
is how `ChapterCommentPolicyIntegrationTest` is written.

- `it('exposes minRootCommentLength=20 in list config for entityType=news')`
- `it('rejects a root comment shorter than 20 characters')` — expects
  `ValidationException::withMessages(['body' => ['Comment too short']])`
- `it('accepts a root comment of exactly 20 characters')`
- `it('applies no minimum length to replies')` — a 3-character reply to an
  existing root comment succeeds
- `it('allows the same user to post several root comments on one article')` —
  two successive `createComment('news', $news->id, …)` both return ids > 0
- `it('allows the article creator to comment on their own article')` — acting
  as the `admin()` who is the article's `created_by`
- `it('refuses a root comment on a draft article')` — expects
  `['body' => ['Comment not allowed']]`
- `it('returns false from canCreateRoot for an article id that does not exist')`
  — call the policy directly with id `999999`; it must return `false` and not
  throw (architecture §9)
- `it('builds the moderation deep link for a news comment')` —
  `getUrl($news->id, 123) === route('news.show', ['slug' => $news->slug]) . '?comment=123'`
- `it('returns null from getUrl for an article id that does not exist')`

**Acceptance.**
- ✅ A `user`-role member can post a root comment of 20+ characters on a
  published article and gets a comment id back.
- ✅ A 19-character root comment on a published article is rejected with
  `body: Comment too short`.
- ✅ A root comment on a `draft` article is rejected with
  `body: Comment not allowed`, for every role including admin.
- ✅ `NewsCommentPolicy::canCreateRoot(999999, $someUserId)` returns `false`
  and throws nothing.
- ✅ The same user can hold two or more root comments on the same article.
- ✅ A one-character reply is accepted.
- ✅ `npm run gate` green (including deptrac with exactly the two new edges).

---

## Phase 2 — Lifecycle: cascade-delete comments with the article

**Goal.** Deleting a news article removes its whole comment thread, so no
orphan `comments` row can point at a `news` id that no longer exists
(decision #4).

**Depends on architecture.** §2.3 (lifecycle rules), §3.1 (which public API to
call). This phase is independent of the policy and of the notification work; it
does not need either to be in place.

**Deliverables.**

- `app/Domains/News/Private/Services/NewsService.php` — in the existing
  `delete(News $news): void`, call
  `$this->comments->deleteFor('news', (int) $news->id);` **before**
  `$news->delete()`, where `$comments` is a constructor-injected
  `App\Domains\Comment\Public\Api\CommentMaintenancePublicApi`. Direct call,
  not an event listener: the deletion must be synchronous with the parent row
  going away. Leave the existing carousel-cache busting untouched.
- No provider change, no deptrac change — the `NewsPrivate → CommentPublic`
  edge this needs is added by phase 1; if phase 1 has not landed yet, add that
  one edge here and phase 1 will find it already present.

**Note for BUILD.** `News` does **not** use `SoftDeletes`; `NewsService::delete()`
is reached from `NewsController@destroy` in `Private/Controllers/Admin/`. A raw
`$news->delete()` called from elsewhere (a test, a seeder) will *not* clean up
comments — that is accepted, the service is the single supported deletion path,
exactly as the AGENTS.md invariant already states for publish/unpublish.

**Tests.** New file
`app/Domains/News/Tests/Feature/NewsCommentsCascadeTest.php`.

- `it('deletes every comment of an article when the article is deleted')` —
  create a published article, post two root comments and one reply on it via
  `createComment('news', $news->id, …)`, then
  `app(NewsService::class)->delete($news)`; assert
  `listComments('news', $news->id)->total === 0` and that the `comments` table
  holds no visible row for `commentable_type = 'news'`, `commentable_id = $news->id`.
- `it('leaves comments of other articles untouched')` — a second article's
  thread still has its comments after the first is deleted.

**Acceptance.**
- ✅ After `NewsService::delete($news)`, no comment (root or reply) remains
  listable for that article.
- ✅ Another article's thread is unaffected by the deletion.
- ✅ The existing `NewsDeleted` event is still emitted (the existing
  `DeleteNewsTest` keeps passing untouched).
- ✅ `npm run gate` green.

---

## Phase 3 — Notification: reply fan-out, content type, `news-comments` group

**Goal.** When someone replies on a news comment thread, notify the root
comment's author and everyone who already replied on that thread — never the
replier, and never anyone for a root comment (decision #3, functional §4.1.3
and §4.2.3).

**Depends on architecture.** §3.4 in full (listener behaviour, notification
payload, group registration), §9 risk 2 (the fourth notification group is an
accepted tradeoff — do not re-litigate it or fold this into an existing group).
Decisions #3 and #6. This phase is independent of the policy and cascade
phases; the listener reacts to `CommentPosted` regardless of whether the
`'news'` policy is registered.

**Deliverables.**

- `app/Domains/News/Public/Notifications/NewsReplyCommentNotification.php` —
  new, implements `App\Domains\Notification\Public\Contracts\NotificationContent`.
  Modelled on `Story/Public/Notifications/ChapterReplyCommentNotification.php`
  but with no story/chapter fields.
  - Constructor: `int $commentId`, `string $authorName`, `string $authorSlug`,
    `string $newsTitle`, `string $newsSlug`.
  - `public static function type(): string` — returns `'news.reply_comment'`.
    **This string is permanent once stored** (Notification AGENTS.md invariant);
    do not rename it later.
  - `toData()` / `fromData()` — keys `comment_id`, `author_name`,
    `author_slug`, `news_title`, `news_slug`.
  - `display()` — `__('news::notification.reply_comment.posted', [...])` with
    `:author_name`, `:author_url` (`route('profile.show', ['profile' => $slug])`,
    empty string when the slug is empty), `:news_title` and
    `:news_url_with_comment` (`route('news.show', ['slug' => $newsSlug]) . '?comment=' . $commentId`).
- `app/Domains/News/Private/Listeners/NotifyOnNewsComment.php` — new. Single
  method `handle(CommentPosted $event, ?\DateTime $eventDate = null): void`,
  matching `Story/Private/Listeners/NotifyOnChapterComment.php`'s shape
  (the optional `$eventDate` exists for backfills; pass it through to
  `createNotification`). Constructor injects `NotificationPublicApi`,
  `CommentPublicApi`, `App\Domains\Shared\Contracts\ProfilePublicApi` (this one
  lives in the `Shared` layer — no new deptrac edge) and `NewsService` or the
  `News` model for the title/slug lookup. Logic:
  1. Return immediately if `$event->comment->entityType !== 'news'`.
  2. Return immediately if `!$event->comment->isReply` — a root comment
     notifies nobody.
  3. Return if `parentCommentId` is null (safety) or if the article cannot be
     resolved from `entityId`.
  4. `$root = $comments->getCommentInternal((int) $parentCommentId, true, 0);`
     recipients = `{$root->authorId} ∪ {authorId of each $root->children}`,
     de-duplicated, filtered to `> 0` and `!== $event->comment->authorId`.
     Return if the set is empty.
  5. Send one `NewsReplyCommentNotification` to that recipient set via
     `NotificationPublicApi::createNotification($recipients, $content, (int) $c->authorId, $eventDate)`.
- `app/Domains/News/Public/Providers/NewsServiceProvider.php` — in `boot()`:
  - `$eventBus->subscribe(CommentPosted::class, [app(NotifyOnNewsComment::class), 'handle']);`
    (note: `::class`, not `::name()` — that is the form the existing
    `CommentPosted` subscriptions use).
  - Before the existing `NotificationFactory::register()` call for
    `NewsPublishedNotification`, register the group and then the new type:
    ```php
    $notificationFactory->registerGroup('news-comments', 45, 'news::notification.settings.group_comments');
    $notificationFactory->register(
        type: NewsReplyCommentNotification::type(),
        class: NewsReplyCommentNotification::class,
        groupId: 'news-comments',
        nameKey: 'news::notification.settings.type_reply_comment',
    );
    ```
    Sort order `45` places the new group between `news` (40) and `moderation`
    (50). Groups must be registered before any type in them or
    `register()` throws. Local registration here is correct per decision #11
    and the corrected `Notification/AGENTS.md` invariant — no further check
    needed before writing this.
- `app/Domains/News/Private/Resources/lang/fr/notification.php` — three new
  keys, alongside the existing ones:
  - `'reply_comment.posted'` — e.g. `'<a href=":author_url">:author_name</a> a répondu à un commentaire sur l\'actualité "<a href=":news_url_with_comment">:news_title</a>"'`
  - `'settings.group_comments'` — e.g. `"Commentaires d'actualités"` (must be
    distinguishable from Story's `'Commentaires'` group in the settings UI)
  - `'settings.type_reply_comment'` — e.g. `"Un de mes commentaires d'actualité a reçu une réponse"`

**Tests.** New file
`app/Domains/News/Tests/Feature/NotifyOnNewsCommentTest.php`. Drive the
listener by dispatching a real `CommentPosted` carrying a
`App\Domains\Comment\Public\Events\DTO\CommentSnapshot`, exactly as
`Story/Tests/Feature/NotifyOnChapterCommentTest.php` does.

- `it('does nothing when the comment is not on a news article')` — snapshot
  with `entityType: 'chapter'`; `getLatestNotificationByKey('news.reply_comment')`
  is null
- `it('does nothing for a root comment on a news article')` — `isReply: false`;
  no notification of any key is created
- `it('does nothing when the article cannot be resolved')` —
  `entityId: 999999`, `isReply: true`
- `it('notifies the root author and prior repliers, excluding the replier')` —
  root by alice, first reply by bob, then a reply by carol: recipients are
  exactly `[alice, bob]`
- `it('does not notify the replier when they reply to their own thread')` —
  alice replies on her own root comment with no other participant: no
  notification is created
- `it('carries the article title, slug and comment id in the payload')` —
  asserts `content_data` keys `news_title`, `news_slug`, `comment_id`,
  `author_name`, `author_slug`

New file
`app/Domains/News/Tests/Feature/NewsCommentNotificationSettingsTest.php`:

- `it('shows the news comments group and its toggle on the notification settings tab')`
  — `GET route('settings.tab', ['tab' => 'notification'])` as `alice($this)`
  returns 200 and its body contains both
  `news::notification.settings.group_comments` (raw key — the test locale does
  not translate) and `name="prefs[news.reply_comment][website]"`.
- `it('lets a user turn the news reply notification off')` — post the
  preference form with that checkbox unchecked, then dispatch a reply
  `CommentPosted` targeting that user and assert they are **not** in
  `getNotificationTargetUserIds()`. Follow
  `Notification/Tests/Feature/NotificationPreferencesControllerTest.php` for the
  exact form payload shape.

**Acceptance.**
- ✅ A reply on a news thread creates exactly one `news.reply_comment`
  notification whose targets are the root author plus every prior replier,
  minus the replier themselves.
- ✅ A root comment on a news article creates no notification at all.
- ✅ A `CommentPosted` for `entityType = 'chapter'` produces no
  `news.reply_comment` notification (and the existing Story chapter-comment
  tests still pass unchanged).
- ✅ The notification settings tab lists the new group with a website toggle
  for `news.reply_comment`, and a user who unchecks it stops receiving it.
- ✅ `NewsReplyCommentNotification::type()` is `'news.reply_comment'` and
  `fromData(toData())` round-trips.
- ✅ No hard-coded French string outside
  `News/Private/Resources/lang/fr/notification.php`.
- ✅ `npm run gate` green.

---

## Phase 4 — UI: comment thread on the article page

**Goal.** Render Comment's existing thread component at the bottom of a
published news article, and nowhere else.

**Depends on architecture.** §4 (the exact Blade block and its lazy mode),
decisions #8 and #9. **Depends on phase 1**, which registered
`NewsCommentPolicy` for entity type `'news'` — without it the component would
render with the default policy (no minimum length, root comments allowed on
drafts). Do not ship this phase before phase 1 is DONE.

**Deliverables.**

- `app/Domains/News/Private/Resources/views/pages/show.blade.php` — at the end
  of the `<article>` (after the `.news-content` div, still inside
  `<x-app-layout>`), add:
  ```blade
  @if($news->status === 'published')
      <x-comment::comment-list-component entity-type="news" entity-id="{{ $news->id }}" page="0" perPage="5" />
  @endif
  ```
  `page="0"` is lazy mode: the component renders config + total only and the
  items arrive from the existing `GET /comments/fragments` endpoint on scroll.
  Wrap it in whatever minimal spacing/`id="comments"` anchor the page needs —
  the deep-link login redirect built into the component points at `#comments`.
- Nothing else. No new component, no new JS, no new CSS, no controller change:
  `NewsController@show` already passes `$news` and already 404s a draft for
  everyone but `ADMIN` / `TECH_ADMIN`.

**Tests.** New file
`app/Domains/News/Tests/Feature/NewsCommentSectionTest.php`.

- `it('renders the comment thread on a published article for a logged-in user')`
  — `GET route('news.show', ['slug' => $news->slug])` as `alice($this)` is 200
  and the body contains `id="comment-list"` and
  `entityType: 'news'`
- `it('does not render the comment thread on a draft article previewed by an admin')`
  — as `admin($this)`, the draft's page is 200 and the body does **not**
  contain `id="comment-list"`
- `it('still 404s a draft article for a regular user')` — regression guard on
  the existing gate; keep it here so the new `@if` is never mistaken for the
  access control
- `it('shows the comment form to a confirmed user on a published article')` —
  the body contains `action="…/comments"` (the root-comment form) for a
  `user-confirmed` member
- `it('shows the members-only prompt to a guest')` — a logged-out visit to a
  published article is 200 and contains
  `comment::comments.errors.members_only`. **This is the observed behaviour of
  Comment's `checkAccess()`, not a choice this feature makes** — see Open
  item #1; assert what actually happens and flag it, do not work around it.

**Acceptance.**
- ✅ A published article's page contains the comment list container for a
  logged-in user.
- ✅ An admin previewing a draft article sees no comment section anywhere on
  the page.
- ✅ A non-admin still gets 404 on a draft article's URL.
- ✅ The thread is not present in the initial HTML items (lazy mode): the
  response contains the `commentList(...)` Alpine bootstrap with `page: 0`, not
  the comment bodies.
- ✅ The article's existing content, header image and breadcrumbs render
  unchanged (existing `NewsDetailsTest` passes untouched).
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes.

| Surface | Check | OK? |
|---------|-------|-----|
| Published article, confirmed user, no comments yet | Empty thread renders below the article; root comment editor with the standard toolbar is visible; no error box | |
| Published article, confirmed user | Thread loads on scroll (lazy), not on page load — watch the network tab for the `/comments/fragments` call | |
| Root comment form | Submit blocked under 20 characters (counter/validation visible), accepted at 20; posted comment appears immediately | |
| Root comment, second one | The same user can post a second root comment — no "already commented" block, unlike a chapter | |
| Article creator (admin) on their own article | Can post a root comment on their own article — form is present, not hidden | |
| Reply flow | Reply box opens on a root comment, accepts a 1-character reply, and no "reply" control exists on a reply | |
| Notification | The root author's bell shows the reply notification; its link lands on `/news/{slug}?comment=<id>` and scrolls to the right comment | |
| Notification, self-reply | Replying to your own thread with no other participant produces no notification for yourself | |
| Settings → Notifications | A distinct "Commentaires d'actualités" group appears with one toggle, visually separate from Story's "Commentaires" group; unchecking it persists after reload | |
| Edit own comment | Edit control present on own root comment and own reply, with no time limit; the 20-char rule still applies on a root edit | |
| Report a comment | Report dialog opens with the shared comment reasons; the report appears in the Moderation admin panel with a working deep link back to the article | |
| Moderator view | A moderator sees the moderation actions on comments of a published article | |
| Draft preview (admin) | No comment section at all on an unpublished article — not an empty one, not a disabled one | |
| Guest, published article | Members-only prompt + login button, same as chapter comments (decision #10) | |
| Deactivated commenter | A comment from a deactivated account is hidden from the thread; reactivating restores it | |
| Deleted commenter | A comment whose author was deleted still renders, with the author reference gone and no broken profile link | |
| Article deleted | After deleting the article from the admin panel, no orphan thread is reachable and no error appears anywhere it was counted | |
| Mobile (375px) | The thread, editor toolbar and reply controls are usable at mobile width on the article page | |

## Open items

Each must be resolved before the phase that needs it starts.

1. ~~**Guests cannot read the thread — functional §3 says they can.**~~
   **Resolved (decision #10).** `CommentPublicApi::checkAccess()` throws unless
   the viewer has the `user` or `user-confirmed` role, so `CommentListComponent`
   catches it and renders the "members only" box with a login button instead of
   the comments — same as chapters today. `01-functional.md` §3 updated to
   match observed behaviour; no Comment domain change. Phase 4's test
   (`it('shows the members-only prompt to a guest')`) asserts this directly.
2. ~~**`registerGroup()` from outside the Notification domain.**~~
   **Resolved (decision #11).** Phase 3 registers `news-comments` locally in
   `NewsServiceProvider`, per architecture §3.4, mirroring Story's existing
   `'publication'` group. `app/Domains/Notification/AGENTS.md`'s invariant has
   been corrected to state the actual rule: single-domain groups register
   locally, only cross-cutting groups register centrally. No further action
   needed in phase 3 beyond what's already written above.
3. **A reply is still possible on an article that was unpublished after the
   fact.** Relates to **phase 1**. `CommentPublicApi::create()` never calls
   `canReply()` — the reply path only validates the parent. So a user who knows
   a root comment id could `POST /comments` a reply to a thread on an article
   that has since gone back to draft. The article page itself is unreachable
   (404), so the reply would be invisible. Architecture §3.3 locks
   `canReply() === true` and reasons that a draft has no visible root comments;
   that reasoning holds for never-published articles but not for
   published-then-unpublished ones. Low impact (invisible content, no
   escalation), out of scope unless the user says otherwise — recorded so it is
   not discovered as a surprise later.
4. **No comment counter anywhere.** Confirmed out of scope by functional §8 —
   noted only so BUILD does not add one to the news list or carousel out of
   helpfulness.
