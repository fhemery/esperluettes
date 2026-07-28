# Quotes — in-chapter author view (vNext) — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.

> **BLOCKED for implementation** (decision #21) — chapters migrate to MultiEdit
> first. This design stands as written; re-read §4 and risk 2 when the migration
> lands, since both depend on the article's DOM shape.
>
> **Depends on the author-check prequel** (decision #20): `isAuthorOrCoAuthor()`
> is removed and `isAuthor()` becomes the single check. §3.3 below is written
> against the post-prequel API.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Decisions log: [`DECISIONS.md`](./DECISIONS.md)

## 1. Domain placement

**The Quote domain owns everything.** No new domain, no new table, no new
extension point. The feature is a second read path over data Quote already owns,
plus a reversal of one existing lifecycle listener. Every element it needs
already exists:

| Need | Already available |
|------|-------------------|
| Author-or-co-author check | `StoryPublicApi::isAuthorOrCoAuthor()` — already used by `QuotePolicy::canQuote()` |
| Reader display name / avatar / slug | `ProfilePublicApi::getPublicProfiles()` (a **Shared** contract, already used by `QuoteService`) |
| Re-anchoring | `Shared/Resources/js/anchoring/` — `buildCanonicalText`, `findAnchor` |
| Badge + popover chrome | `<x-shared::badge>`, `<x-shared::popover>` |
| Query index | `quotes.index(['chapter_id','user_id','deleted_at'])` — leading column serves both new queries |

### 1.1 Changes in other domains

**Story** — one public API method, plus two Blade lines.

```php
// StoryPublicApi
public function getStoryIdByChapterId(int $chapterId): ?int;
```

Story owns the chapter→story relation and exposes no way to walk it today
(`StoryChapterDto` carries no `storyId`). Quote needs it to authorise the
aggregate endpoint from a `chapter_id` alone (§3.3). It is a read-only accessor
over data Story already has, useful beyond this feature, and it lands on an API
`QuotePrivate` is already allowed to call — no deptrac change.

Then two Blade lines in
[`chapters/show.blade.php`](../../../app/Domains/Story/Private/Resources/views/chapters/show.blade.php),
no PHP:

1. `<x-quote::author-badge>` inside the existing metric row (line ~146, beside
   the reads and word-count badges), guarded by the `$vm->isAuthor` flag the
   view already carries.
2. `<x-quote::author-heat>` wrapping the existing `<article data-quote-article>`
   region, alongside the current `quoteHighlighter` root.

This is the **same Blade-only coupling as `<x-quote::toolbar-button>` today**:
Story composes Quote's components, Quote's PHP never knows about it, and deptrac
sees nothing. No `StoryPrivate → QuotePublic` edge is created.

Nothing else changes. Not Profile, not Settings, not Notification, not Events.

## 2. Data model

### 2.1 Tables

**No schema change. No migration.** The existing `quotes` table serves both new
queries, and its `['chapter_id','user_id','deleted_at']` index is used by the
leading column for:

```sql
-- badge count
SELECT COUNT(*) FROM quotes WHERE chapter_id = ? AND deleted_at IS NULL;
-- aggregate rows
SELECT id, user_id, story_id, highlighted_text, prefix, suffix, created_at
  FROM quotes WHERE chapter_id = ? AND deleted_at IS NULL ORDER BY created_at DESC;
```

`user_id` stays nullable. Decision #6 established there are no orphaned rows, and
decision #5 means none will be created, so no data migration and no read-time
`user_id IS NULL` filter.

Note the aggregate `SELECT` **does not list the `note` column** — see §3.1.

### 2.2 Model

`Quote` is unchanged — no new column, cast or relation.

### 2.3 Lifecycle rules

One behaviour change, implementing decision #5:

| Event | v1 | Now |
|-------|----|-----|
| `Auth::UserDeleted` | `NullifyUserOnUserDeleted` — `UPDATE quotes SET user_id = NULL` | **`DeleteQuotesOnUserDeleted`** — `DELETE FROM quotes WHERE user_id = ?` |
| `Auth::UserDeactivated` | soft-delete | unchanged |
| `Auth::UserReactivated` | restore | unchanged |

The delete is a **raw `DB::table()` DELETE**, deliberately bypassing the
soft-delete scope: a user who deactivated and then deleted their account has
soft-deleted rows, and those must go too. The old listener class is renamed, not
kept alongside; its registration in `QuoteServiceProvider` is updated, and the
invariant in `app/Domains/Quote/AGENTS.md` ("`user_id` nullable; nullified not
deleted") is rewritten.

Consequence, already accepted at REFINE: a reader deleting their account
retroactively lowers the author's counts. That is the point — an orphan row can
never be rendered because it can never exist.

## 3. PHP architecture

### 3.1 Public API

Three additions to `QuotePublicApi`:

```php
public function countForChapter(int $chapterId): int;
public function canViewChapterAggregate(int $chapterId, int $userId): bool;
public function getChapterAggregate(int $chapterId, int $userId): ChapterAggregateDto;
```

New DTOs under `Public/Api/Contracts/`:

```php
final class ChapterAggregateDto {
    public function __construct(
        public readonly array $items,      // AggregateQuoteDto[]
        public readonly int $totalCount,
    ) {}
}

final class AggregateQuoteDto {
    public function __construct(
        public readonly int $id,
        public readonly string $highlightedText,
        public readonly ?string $prefix,
        public readonly ?string $suffix,
        public readonly \DateTimeInterface $createdAt,
        public readonly object $quoter,    // Shared profile DTO
    ) {}
}
```

**`AggregateQuoteDto` has no `note` property at all.** This is the design's
central privacy move: §4.4.6 of the spec ("the popover never contains a note")
becomes structurally impossible to violate rather than a condition someone can
forget. `QuoteDto` is left untouched — reusing it would carry a `note` field into
the author's response body, one `null` away from a leak.

### 3.2 Services

`QuoteService` gains two methods:

- `countForChapter(int $chapterId): int` — one `COUNT`, no authorisation (the
  caller is the Blade component, which is already behind the policy).
- `getChapterAggregate(int $chapterId, int $userId): ChapterAggregateDto` —
  loads the chapter's rows, resolves quoter profiles in **one batched
  `getPublicProfiles()` call** (no N+1), maps to `AggregateQuoteDto` ordered
  newest-first. A row whose profile cannot be resolved is skipped defensively;
  after decision #5 this should never happen.

Grouping, heat depth and staleness are **not** computed here — see §4.

### 3.3 Policy / authorization

```php
// QuotePolicy
public function canViewChapterAggregate(int $chapterId, int $userId): bool
{
    $storyId = $this->storyApi->getStoryIdByChapterId($chapterId);

    return $storyId !== null
        && $this->storyApi->isAuthor($userId, $storyId);
}
```

`isAuthor()` filters to `role = 'author'`, which is what "author or co-author"
means in this design — collaborators holding that role are indistinguishable and
hold identical rights.

**Never `isAuthorOrCoAuthor()`**: despite the name it delegates to
`getCollaboratorIds()`, which returns *every* collaborator including
`beta-reader`, so it would show reader identities to beta readers (decision #19).
The prequel refactoring (decision #20) deletes that method outright, which is
what makes this mistake unavailable rather than merely discouraged.

**The story is resolved server-side from the chapter, never taken from the
client.** The endpoint accepts a `chapter_id` and nothing else — there is no
`story_id` parameter to forge. This closes the obvious hole where an author
passes their own story alongside someone else's chapter.

That requires one small addition to `StoryPublicApi` (§1.1); Story owns the
chapter→story relation, so it is the right domain to answer the question.

The endpoint joins the **existing** `role:` . `Roles::USER_CONFIRMED` route
group, so the role check comes from the middleware and the policy only has to
answer "is this user an author of the chapter's story?".

### 3.4 Events and listeners

- **Emitted**: none. The view is read-only.
- **Listened to**: no new subscription. `NullifyUserOnUserDeleted` becomes
  `DeleteQuotesOnUserDeleted` (§2.3); the deactivate/reactivate listeners are
  untouched.
- `ChapterPassageQuoted` and `ChapterQuotedNotification` are unchanged.

### 3.5 Routes, controllers, form requests

One route added to the **existing** confirmed-only group in
[`routes.php`](../../../app/Domains/Quote/Private/routes.php) — no new group:

```php
Route::get('/chapter-aggregate', [ChapterAggregateController::class, 'show'])
    ->name('chapter-aggregate');
```

It therefore inherits `['web', 'auth', 'compliant', 'role:user-confirmed']`.
Ordering is unambiguous: the group's only other `GET` is `/quotes/`.

`GET` only, so no `PATCH` question arises. The endpoint takes **`chapter_id`
alone** — no `story_id` parameter exists to forge (§3.3). No form request: the
single query parameter is validated inline in the controller, as
`QuoteController::index` already does. The controller calls
`QuotePublicApi`, never a model, and serialises the DTO explicitly — no
`toArray()` on a model, so no column can slip into the payload.

## 4. Frontend architecture

The browser does the aggregation (tradeoff #1), reusing the v1 pipeline.

### 4.1 Alpine store — `quoteAggregate`

Registered beside the existing `quotes` store; it is what lets the badge (in the
page header) talk to the heat (around the article), which are far apart in the
DOM:

```js
{
    rows: [],          // AggregateQuoteDto[] as JSON
    totalCount: 0,     // seeded server-side, authoritative for the badge
    loaded: false,
    visible: false,    // the heat toggle — NOT persisted (decision #14)
    async ensureLoaded(chapterId),   // fetch once, on first need
    toggle(chapterId),
    focus(groupKey),   // scroll + open the passage popover
}
```

`visible` starts `false` on every page load and is never written to
`localStorage` or a setting — decision #14.

### 4.2 Derived state, computed client-side

Two pure functions, unit-testable in isolation and therefore the one place unit
tests beat integration tests here:

- **`groupPassages(rows)`** — groups by **normalised `highlighted_text`**
  (trimmed, whitespace collapsed), producing one entry per passage with its
  count and its readers. This is the summary's row set (decision #10). Exact-text
  grouping is well defined; partial overlaps are a *heat* concern, not a grouping
  one.
- **`segmentByDepth(ranges)`** — takes the re-anchored `{start,end}` ranges,
  splits the canonical text at every boundary and returns non-overlapping
  segments each carrying a `depth`. This is what makes the tint deepen with
  overlap (decision #2). It is necessary because overlapping ranges **cannot** be
  expressed as nested `<mark>` elements via `Range.surroundContents()` — each
  segment is wrapped exactly once, at its own depth.

Staleness falls out of `findAnchor()` returning `missing`: those rows get no
range, so they are absent from the heat and land in the summary's stale section
(§4.2.4 of the spec).

### 4.3 Blade components (Quote domain)

| Component | Role |
|-----------|------|
| `<x-quote::author-badge>` | The `« n citations »` badge, wrapped in `<x-shared::popover>` whose panel is the chapter summary. Seeds `totalCount` server-side. Renders nothing unless the policy passes — **not** guarded on `$vm->isAuthor`, so the component and the endpoint can never disagree about who may see it. |
| `<x-quote::author-heat>` | Alpine root around the article: renders the depth segments and the `md+` margin markers, and hosts the toggle icon's target. |
| `<x-quote::author-passage-panel>` | The reader list popover for one passage. **Separate from `<x-quote::chapter-panel>`**, which is the reader's own note/edit/delete panel — different data, different audience, no shared state. |

The badge must sit visually flush with its neighbours in the metric row, which
use `<x-shared::metric-badge>`. That component's popover panel is text-only
(`label` + `tooltip`), so it cannot host the summary list; `author-badge`
therefore composes the same underlying `<x-shared::popover>` / `<x-shared::badge>`
primitives with a rich panel, and must match `metric-badge`'s `size` and `color`
defaults so the row stays homogeneous.

The toggle icon sits inside `author-badge` (decision #11), flipping
`$store.quoteAggregate.visible`.

Margin markers are absolutely positioned in a gutter container that is
`hidden md:block`, so below `md` they are not merely invisible but never built
(§4.3.5 of the spec). Positions come from the first client rect of each group's
first segment, recomputed on resize.

### 4.4 JS module layout

```
Resources/js/quote/
  api/client.js                 + getChapterAggregate(chapterId)
  stores/aggregate-store.js     new
  ui/author-heat.js             new — Alpine component
  ui/author-summary.js          new — grouping/segmentation pure functions
```

`index.js` registers the new store and component. The existing reader modules are
untouched: an author cannot quote their own story, so the reader tint and the
author heat never coexist on the same page and need no interaction.

### 4.5 i18n

New keys in `Private/Resources/lang/fr/ui.php`: badge label and tooltip, summary
heading, toggle aria-label, passage popover heading, marker aria-label. Counted
strings use Laravel's `trans_choice` for French plural agreement
(« 1 citation » / « n citations », « 1 lecteur » / « n lecteurs »). The stale
badge **reuses the existing** « Passage plus présent dans le chapitre » string
rather than adding a second wording.

## 5. Deptrac

**No new edge.** Verified against `deptrac.yaml`:

- `QuotePrivate` already allows `StoryPublic` (line 755 ff.) — used by the policy.
- Profile data comes from `App\Domains\Shared\Contracts\ProfilePublicApi`, which
  lives under `Shared`, already allowed and already used by `QuoteService`.
- The Story ↔ Quote coupling is **Blade-only** (§1.1), invisible to deptrac, and
  identical in kind to the existing `<x-quote::toolbar-button>` arrangement.

## 6. Testing strategy

**Integration (feature) tests — the default here.**

| Case | Expectation |
|------|-------------|
| Guest hits the endpoint | redirected / 401 |
| Confirmed reader, not an author | 403 |
| Moderator, admin | 403 — decision #4 |
| **Beta reader on the story** | **403** — decision #19, the case `isAuthorOrCoAuthor()` would have wrongly allowed |
| Author | 200 |
| Co-author | 200 — assumption A1 |
| Author demoted to non-confirmed `user` | 403 — accepted (decision #17); the role middleware answers before the policy |
| `chapter_id` belonging to another author's story | 403 — the story is resolved from the chapter server-side (§3.3) |
| `chapter_id` that does not exist | 403 — `getStoryIdByChapterId()` returns `null` |
| Chapter with no quotes | 200, empty aggregate |
| **Response body** | contains **no `note` key**, for any role, on any row — asserted on the raw JSON, not on the DTO |
| Deactivated reader's quotes | absent from rows and count |
| Reactivated reader's quotes | present again |
| `countForChapter` | matches the row count, excludes soft-deleted |

**Listener test** — `Auth::UserDeleted` leaves **no** `quotes` row for that user,
including rows already soft-deleted by a prior deactivation. This *replaces* the
existing nullify assertion in `QuoteLifecycleTest`.

**Vitest** — `groupPassages()` (exact-text grouping, whitespace normalisation,
count aggregation, ordering by count with stale last) and `segmentByDepth()`
(disjoint ranges, nested ranges, partial overlaps, identical ranges, adjacent
boundaries). These are pure and isolated, so unit tests are the right level.

**VERIFY only** — Alpine does not run in PHPUnit: heat gradient legibility across
depths and in dark mode, margin-marker vertical alignment, absence of markers
below `md`, scroll-then-open-popover from a summary row, stale row inertness,
and the badge reading « 0 citation » on an unquoted chapter.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where is the aggregate computed? | (a) ship raw rows, aggregate client-side; (b) server computes groups, counts and reader lists | **(a)** | Staleness is only knowable after re-anchoring, which is client-side by v1 design — a server-side aggregate literally cannot mark stale passages, and its grouping would disagree with the tint on screen. Reuses the existing pipeline. |
| 2 | What does an author's chapter load pay? | (a) server-side COUNT + lazy detail; (b) everything lazy; (c) everything server-rendered | **(a)** | The badge is the entry point and must be correct at first paint; one indexed COUNT for authors only is cheap, and the rows — usually never opened — are not paid for. |
| 3 | Reuse `QuoteDto` for the author payload? | (a) new `AggregateQuoteDto` with no note field; (b) reuse `QuoteDto` with `note: null` | **(a)** — design call | Makes the privacy guarantee structural instead of conditional. A field that does not exist cannot leak. |
| 4 | How is the endpoint authorised, given `StoryChapterDto` has no `storyId`? | (a) add `StoryPublicApi::getStoryIdByChapterId()`; (b) derive the story from the quote rows' denormalised `story_id`; (c) trust the client's `story_id` | **(a)** — user call, overriding an earlier design call for (b) | (c) is a hole: an author could pass their own story with another's chapter. (b) closes it but by inference, and breaks down on a chapter with no quotes. (a) asks the domain that owns the relation — plainly, and reusable elsewhere. |
| 5 | How is overlapping heat rendered? | (a) segment by depth, wrap each segment once; (b) nested `<mark>` per quote | **(a)** — design call | (b) is not implementable: `Range.surroundContents()` throws on ranges that partially overlap existing elements. |
| 6 | Summary grouping key | (a) normalised exact `highlighted_text`; (b) resolved range overlap | **(a)** — design call | Decision #10 says one row per passage; partial overlap has no well-defined "same passage". Overlap remains a heat concern. |

## 8. File layout

```
app/Domains/Quote/
  Private/
    Controllers/ChapterAggregateController.php            new
    Services/QuoteService.php                             + countForChapter, getChapterAggregate
    Services/QuotePolicy.php                              + canViewChapterAggregate
    Listeners/DeleteQuotesOnUserDeleted.php               renamed from NullifyUserOnUserDeleted.php
    View/Components/AuthorBadge.php                       new
    Resources/views/components/author-badge.blade.php     new
    Resources/views/components/author-heat.blade.php      new
    Resources/views/components/author-passage-panel.blade.php  new
    Resources/lang/fr/ui.php                              + badge / summary / marker keys
    routes.php                                            + aggregate route (existing confirmed group)
  Public/
    Api/QuotePublicApi.php                                + 3 methods
    Api/Contracts/ChapterAggregateDto.php                 new
    Api/Contracts/AggregateQuoteDto.php                   new
    Providers/QuoteServiceProvider.php                    listener registration renamed
  Resources/js/quote/
    api/client.js                                         + getChapterAggregate
    stores/aggregate-store.js                             new
    ui/author-heat.js                                     new
    ui/author-summary.js                                  new
    index.js                                              + registrations
  Tests/Feature/ChapterAggregateTest.php                  new
  Tests/Feature/QuoteLifecycleTest.php                    nullify assertions → delete
  AGENTS.md                                               lifecycle invariant rewritten
  README.md                                               author view added to scope

app/Domains/Story/
  Public/Api/StoryPublicApi.php                           + getStoryIdByChapterId()
  Private/Resources/views/chapters/show.blade.php         2 Blade lines
```

No migration. No `deptrac.yaml` change.

## 9. Risks acknowledged

1. **Payload growth on heavily quoted chapters.** Every row ships to the browser.
   At a few hundred quotes this is still small; at a few thousand it is not.
   *Trigger to revisit*: a chapter passing ~500 quotes, or a slow summary open —
   the fix is a server-side pre-grouping that keeps stale detection client-side.
2. **`surroundContents` skips segments crossing element boundaries.** Inherited
   from v1's `_applyHighlight`: a segment spanning e.g. an `<em>` boundary is
   silently dropped, so heat can be locally incomplete on richly formatted prose.
   *Trigger*: VERIFY showing visible gaps — the fix is a per-text-node walk
   instead of a single range.
3. **Badge count vs. tinted passages can differ**, by design (assumption A5), with
   the summary as the explanation. *Trigger*: VERIFY judging it still reads as a
   bug even with the summary — then the badge count moves client-side.
4. **Marker alignment is measured from client rects**, so it depends on fonts and
   images having settled. *Trigger*: markers drifting on load — recompute after
   `document.fonts.ready` and on the article's resize observer.
5. **Deleting quotes on account deletion is irreversible** and silently lowers
   author counts. Accepted at REFINE (decision #5), noted here because it is the
   one change that touches existing v1 behaviour.
