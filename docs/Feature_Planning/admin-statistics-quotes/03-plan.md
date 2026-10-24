# Admin statistics — quotes section — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md)

## Prerequisite — `quote-hard-delete/` lands first

BUILD starts only once the backlog task `quote-hard-delete/` is done: a reader
deleting their own quote becomes a **hard** delete, and reactivation restores
only the quotes hidden by the deactivation (DECISIONS #7). This plan assumes
that behaviour throughout — a reader-deleted quote is gone for good and is
never restored. Phases 2 and 4 rely on it directly; phases 1 and 3 do not, but
the task is built in one go after the prerequisite. Architecture §3.2 ("after
the soft delete") and §9 risk 2 predate that decision and are superseded by it.

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Quote — event classes and bus registration | S | — | DONE |
| 2 | Quote — emit deletion / removal / restoration events | S | 1, `quote-hard-delete/` | TODO |
| 3 | Quote — daily net-change history in `QuotePublicApi` | S | — | TODO |
| 4 | Statistics — `global.total_quotes` definition (live deltas + table recompute) | M | 2, 3, `quote-hard-delete/` | TODO |
| 5 | Statistics — admin tile, « Citations » tab, E2E seed | S | 4 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

No refactor phase and no checkpoint row: no existing code is reshaped. Phase 1
only *registers* an already-emitted event (verified to change no listener
wiring, see Phase 1), and phase 2 only *adds* emissions after the existing
writes; both are covered by the existing `QuoteLifecycleTest`, and neither
touches a page.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

### Facts verified during PLAN (true at HEAD `32e7f54e`)

- `EventBus::subscribe()` with a FQCN listens directly via `LaravelEvent::listen`
  when the class exists; `registerEvent()` only flushes *pending* subscriptions
  queued under a logical name. `NotifyAuthorsOnQuoteCreated` subscribes by FQCN,
  so it **is** wired today (`QuoteLifecycleTest` › "notifies the chapter author…"
  proves it). Registering `ChapterPassageQuoted` therefore neither starts nor
  doubles a notification — architecture §9's third risk is resolved.
- Stored events are rehydrated through the factory that `registerEvent()` fills;
  `latestEventOf()` (Events test helper) returns `null` for an unregistered event.
- `QuotePolicy::canEditOrDelete()` is owner-only: the deleter is always the quoter.
- `/admin/statistics` is restricted to `admin` and `tech-admin` (not moderator),
  and stays so (DECISIONS #6).
- `QUEUE_CONNECTION=sync` in `phpunit.xml` and `.env.e2e`, so the queued
  `UpdateStatisticsOnEvent` runs inline in tests and E2E.
- Quote's lifecycle tests trigger the cascade with
  `event(new UserDeactivated($id))` etc.; the same pattern works for the new tests.

---

## Phase 1 — Quote — event classes and bus registration

**Goal.** Create the three new Quote domain events and register all four Quote
events with the bus so stored copies rehydrate.

Architecture: §3.1 (event table), §3.4, §8.

**Deliverables.**
- `app/Domains/Quote/Public/Events/ChapterPassageQuoteDeleted.php` —
  `implements DomainEvent`; ctor `(public readonly int $quoterId, public readonly int $chapterId, public readonly int $storyId)`;
  `name()` = `'Quote.ChapterPassageQuoteDeleted'`, `version()` = 1,
  `toPayload()` / `fromPayload()` round-trip (same `(int) ($payload[...] ?? 0)`
  style as `ChapterPassageQuoted`), `summary()` e.g. `"Quoter #x deleted a quote on chapter #y"`.
- `app/Domains/Quote/Public/Events/UserQuotesRemoved.php` — ctor
  `(int $userId, int $count)`; name `'Quote.UserQuotesRemoved'`; summary
  `"N quote(s) of user #x removed"`.
- `app/Domains/Quote/Public/Events/UserQuotesRestored.php` — ctor
  `(int $userId, int $count)`; name `'Quote.UserQuotesRestored'`.
- `app/Domains/Quote/Public/Providers/QuoteServiceProvider.php` — in `boot()`,
  `$eventBus->registerEvent(X::name(), X::class)` for `ChapterPassageQuoted`
  and the three new classes, before the existing `subscribe()` calls (same
  shape as `AuthServiceProvider` lines 82–93).
- No event carries quote text, note or quote id (architecture §3.3, §7).

**Tests.** New `app/Domains/Quote/Tests/Feature/QuoteEventsTest.php`:
- `it('stores and rehydrates ChapterPassageQuoted when a reader quotes')` —
  POST `/quotes` as a confirmed reader on another author's published chapter;
  `latestEventOf('Quote.ChapterPassageQuoted', ChapterPassageQuoted::class)`
  is not null and carries the right `quoterId`/`chapterId`/`storyId`. (Fails
  today: the event is not registered.)
- `it('round-trips ChapterPassageQuoteDeleted through the stored event')`,
  `it('round-trips UserQuotesRemoved …')`, `it('round-trips UserQuotesRestored …')`
  — `dispatchEvent(new X(...))` then `latestEventOf(...)` returns an equal payload.
- Existing `QuoteLifecycleTest` › "notifies the chapter author when a reader
  quotes, not the quoter" still counts exactly **1** notification (proves no
  double subscription).

**Acceptance.**
- ✅ The four Quote events resolve through `EventBus::resolveDomainEventClass()`
  by their `Quote.*` names.
- ✅ Quoting a passage still produces exactly one `quote.chapter_quoted` notification.
- ✅ No payload contains `highlightedText` except the pre-existing `ChapterPassageQuoted`.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Quote — emit deletion / removal / restoration events

**Goal.** Make every disappearance and reappearance of quotes observable, with
the affected count taken by Quote before it changes the rows.

Architecture: §2.3 (lifecycle table), §3.2, §3.4. Builds on phase 1: the
classes `ChapterPassageQuoteDeleted`, `UserQuotesRemoved(userId, count)` and
`UserQuotesRestored(userId, count)` exist in `Quote/Public/Events/` and are
registered with the bus. Also builds on the prerequisite task
`quote-hard-delete/`: `QuoteService::delete()` already **hard**-deletes the
row, and a soft-deleted quote can only come from a user deactivation.

**Deliverables.**
- `app/Domains/Quote/Private/Services/QuoteService.php` — `delete()`: after the
  existing hard delete of the row, `$this->eventBus->emit(new ChapterPassageQuoteDeleted(quoterId: $quote->user_id, chapterId: $quote->chapter_id, storyId: $quote->story_id))`.
  The event therefore fires on a hard delete: the quote is gone for good and no
  later event brings it back. Read the ids before the delete if the
  prerequisite left them unavailable afterwards.
  (`$this->eventBus` is already injected — used by `create()`.)
- `app/Domains/Quote/Private/Listeners/SoftDeleteOnUserDeactivated.php` —
  inject `EventBus`; `$count = Quote::where('user_id', …)->count()` (visible
  rows only, default soft-delete scope), soft-delete, emit
  `UserQuotesRemoved($userId, $count)` only if `$count > 0`.
- `app/Domains/Quote/Private/Listeners/DeleteQuotesOnUserDeleted.php` — count
  **visible** rows (`Quote::where('user_id', …)->count()`, i.e. not already
  soft-deleted by a deactivation) before the existing raw `DB::table` delete;
  emit `UserQuotesRemoved` only if `> 0`. The raw delete stays as is.
- `app/Domains/Quote/Private/Listeners/RestoreOnUserReactivated.php` —
  `$count = Quote::onlyTrashed()->where('user_id', …)->count()` before the
  existing `withTrashed()->restore()`; emit `UserQuotesRestored` only if `> 0`.
  Count explicitly rather than trusting `restore()`'s return value (affected-row
  semantics differ between SQLite and MySQL, and `withTrashed()` matches live
  rows too). Do **not** change which rows are restored — after the
  prerequisite, the trashed rows are exactly the ones the deactivation hid.
- `app/Domains/Quote/AGENTS.md` — "Events emitted": replace "Note edits and
  deletions emit nothing" with the three new events, their emitters and the
  "count > 0 only" rule. `app/Domains/Quote/README.md` — same, if it lists events.

**Tests.** Extend `app/Domains/Quote/Tests/Feature/QuoteEventsTest.php`:
- `it('emits ChapterPassageQuoteDeleted when the owner deletes a quote')` —
  DELETE `/quotes/{id}` as the owner; event payload = owner id, chapter, story.
- `it('emits nothing when a non-owner fails to delete a quote')` — 403, `countEvents('Quote.ChapterPassageQuoteDeleted')` = 0.
- `it('emits UserQuotesRemoved with the visible count on deactivation')` — 2
  live quotes + 1 row soft-deleted directly in setup (`createQuote()` with
  `deleted_at`; reader deletion no longer soft-deletes) → `count` = 2.
- `it('emits UserQuotesRestored without counting a quote the reader deleted')` —
  3 quotes, owner DELETEs 1, deactivate, reactivate → `UserQuotesRestored.count` = 2.
- `it('emits no UserQuotesRemoved when a deactivated user has no quote')`.
- `it('emits UserQuotesRestored with the number of restored rows on reactivation')`
  — deactivate a user with 2 quotes, reactivate → `count` = 2.
- `it('emits UserQuotesRemoved with the visible count when an active user is deleted')` — 3 quotes → 3.
- `it('emits no UserQuotesRemoved when a user deleted after deactivation')` —
  deactivate (event count 2), then delete → exactly one `Quote.UserQuotesRemoved` stored.
- Existing `QuoteLifecycleTest` "user lifecycle cascades" cases stay green unchanged.

**Acceptance.**
- ✅ Every row of architecture §2.3 except "chapter/story deleted" produces the
  matching event, and that one produces none.
- ✅ A zero count never produces an event.
- ✅ Existing cascade behaviour (soft delete, restore, hard delete) is unchanged.
- ✅ `pnpm run gate` green.

---

## Phase 3 — Quote — daily net-change history in `QuotePublicApi`

**Goal.** Expose the net daily change of the quote population derived from the
`quotes` table, for the statistic's rebuild.

Architecture: §3.1 (first paragraph), §7 #2, §9 risk 1. Independent of phases 1–2.

**Deliverables.**
- `app/Domains/Quote/Private/Services/QuoteService.php` — `getDailyNetChanges(): array`
  returning `array<string /* Y-m-d */, int>`, sorted by date ascending: `+1` per
  row on `DATE(created_at)` (all rows, `withTrashed`), `−1` per soft-deleted row
  on `DATE(deleted_at)`. Two grouped queries merged in PHP; `DATE()` works on
  SQLite and MySQL. Days whose net is 0 are kept out of the result.
- `app/Domains/Quote/Public/Api/QuotePublicApi.php` —
  `getDailyNetChanges(): array` delegating to the service, with a docblock
  stating the shape and that hard-deleted rows (quotes deleted by their reader,
  quotes of deleted users) do not appear — neither their creation nor their
  removal. Soft-deleted rows are those of currently deactivated users.

**Tests.** New `app/Domains/Quote/Tests/Feature/QuotePublicApiDailyNetChangesTest.php`
(rows via `createQuote()` with explicit `created_at` / `deleted_at`):
- `it('returns an empty array when there is no quote')`.
- `it('counts one per created quote on its creation day')` — 2 on day A, 1 on day B.
- `it('subtracts soft-deleted quotes on their deletion day')` — created day A,
  deleted day C → `[A => 1, C => -1]`.
- `it('drops a day whose creations and deletions cancel out')`.
- `it('returns dates ascending in Y-m-d format')`.

**Acceptance.**
- ✅ `array_sum(getDailyNetChanges())` equals `Quote::count()` (visible rows).
- ✅ No note, text or user id leaves the method.
- ✅ `pnpm run gate` green.

---

## Phase 4 — Statistics — `global.total_quotes` definition (live deltas + table recompute)

**Goal.** Maintain `global.total_quotes` (snapshot + daily series) from Quote's
four events, and rebuild it from `QuotePublicApi::getDailyNetChanges()` on
`statistics:compute`.

Architecture: §1, §2, §2.3, §3.2 (Statistics bullet), §5, §7 #2. Builds on:
Quote emits `Quote.ChapterPassageQuoted` (on create), `Quote.ChapterPassageQuoteDeleted`,
`Quote.UserQuotesRemoved(userId, count)` and `Quote.UserQuotesRestored(userId, count)`
(phases 1–2), and `QuotePublicApi::getDailyNetChanges(): array<Y-m-d, int>` (phase 3).
Also builds on the prerequisite task `quote-hard-delete/`: a reader's own
deletion is a hard delete, and reactivation never restores it.

**Deliverables.**
- `app/Domains/Statistics/Private/Definitions/Global/TotalQuotesStatistic.php`
  implementing `StatisticDefinition` (model: `TotalCommentsStatistic`):
  - `key()` `'global.total_quotes'`, `scopeType()` `'global'`,
    `hasTimeSeries()` true, `label()` `'Total Quotes'`.
  - `listensTo()`: the four `Quote.*` names via `X::name()`.
  - `computeDelta()`: `ChapterPassageQuoted` → `[null => 1]`;
    `ChapterPassageQuoteDeleted` → `[null => -1]`; `UserQuotesRemoved` →
    `[null => -count]`; `UserQuotesRestored` → `[null => +count]`; else `null`.
    Inline — no delta-calculator class (nothing shares it).
  - `recompute()`: **no event replay**. `use RecomputesStatisticFromEvents` only
    for its `clearData()`; then for each `day => delta` of
    `QuotePublicApi::getDailyNetChanges()`: `incrementSnapshot(key, 'global', null, delta)`
    and `incrementTimeSeries(..., Carbon::parse($day))`; then
    `recomputeCumulativeValues()`; return a `ComputeResult` (snapshot value,
    series point count, `eventsProcessed` = number of days applied). Leave a
    one-line comment saying why this statistic does not replay events.
- `app/Domains/Statistics/Public/Providers/StatisticsServiceProvider.php` —
  `registerStatistics()`: register `TotalQuotesStatistic`; `registerEventListeners()`:
  add the four Quote event classes to the subscription list (otherwise the
  statistic silently never updates).
- `deptrac.yaml` — add `QuotePublic` to `StatisticsPublic`, `StatisticsPrivate`
  and `StatisticsTests`.
- `app/Domains/Statistics/README.md` — catalogue row
  `global.total_quotes | Quote.ChapterPassageQuoted, Quote.ChapterPassageQuoteDeleted, Quote.UserQuotesRemoved, Quote.UserQuotesRestored | Implemented`,
  and in "Adding a new statistic" step 4 a sentence that a statistic may rebuild
  from its owner's public API when events are incomplete (`TotalQuotesStatistic`).
  No link to planning docs.

**Tests.** New `app/Domains/Statistics/Tests/Feature/TotalQuotesStatisticTest.php`
(`resetStatistics()` in `beforeEach`; reader = confirmed `bob`, author `alice`,
`publicStory` + `createPublishedChapter`; quotes created via POST `/quotes`,
deleted via DELETE `/quotes/{id}`, lifecycle via `event(new UserDeactivated(...))` etc.):
- `describe('event-driven updates')`:
  - `it('increments total_quotes when a reader saves a quote')` — snapshot 1.0,
    today's series 1.0.
  - `it('decrements total_quotes when the owner deletes a quote')` — 2 then 1.
  - `it('subtracts a user\'s visible quotes on deactivation and adds them back on reactivation')` — 3 → 0 → 3.
  - `it('subtracts a deleted user\'s visible quotes')` — 2 → 0.
  - `it('does not subtract twice when a deactivated user is deleted')` — 2 → 0 → 0.
  - `it('keeps quotes counted when their chapter is deleted')`.
  - `it('records a negative daily delta')` — create 1 yesterday (`travelTo`),
    delete it today → today's series value −1.0.
- `describe('recompute from the quotes table')`:
  - `it('rebuilds the same snapshot and daily series as live events')` — quotes
    by two readers created on 3 different days (`travelTo`), one reader
    deactivated on a 4th day; capture snapshot + per-day values,
    `resetStatistics()`, `recomputeStatistic('global.total_quotes')`, compare.
    No reader deletion here: a hard-deleted row leaves no trace in the table.
  - `it('keeps the snapshot right when a reader-deleted quote vanishes from history')`
    — 2 quotes on day A, one deleted by its owner on day B; live snapshot 1,
    recomputed snapshot 1, and the rebuilt series has no point on day B
    (accepted divergence, same as deleted users — architecture §9 risk 1).
  - `it('does not count a reader-deleted quote again after reactivation')` —
    reader deletes 1 of 2, deactivate, reactivate → live snapshot 1 and
    recomputed snapshot 1 (the deleted quote is gone for good).
  - `it('runs through statistics:compute')` —
    `$this->artisan('statistics:compute', ['key' => 'global.total_quotes'])->assertExitCode(0)`.
  - `it('leaves no snapshot when there is no quote')`.

**Acceptance.**
- ✅ Each row of architecture §2.3 moves `global.total_quotes` by the stated delta.
- ✅ `statistics:compute global.total_quotes` yields the same snapshot as live
  events for a table with live, soft-deleted and restored rows, and after
  reader (hard) deletions.
- ✅ A quote its reader deleted is never counted again, reactivation included.
- ✅ Statistics imports no Auth event and no Quote private class (deptrac green).
- ✅ `pnpm run gate` green.

---

## Phase 5 — Statistics — admin tile, « Citations » tab, E2E seed

**Goal.** Show the total as a sixth summary tile and its evolution in a new
« Citations » tab of `/admin/statistics`.

Architecture: §4, §6 (E2E bullet). Builds on: `global.total_quotes` exists as a
registered global statistic with a daily time series (phase 4). The page-level
« Cumulé / Par semaine » switch already applies to every `stat-widget`; nothing
to do in `charts.js`.

**Deliverables.**
- `app/Domains/Statistics/Private/Resources/lang/fr/admin.php` — `'quotes' => 'Citations'`,
  `'tab_quotes' => 'Citations'`.
- `app/Domains/Statistics/Private/Resources/views/pages/admin/index.blade.php` —
  summary grid `lg:grid-cols-5` → `lg:grid-cols-6`, sixth
  `<x-statistics::stat-summary statistic-key="global.total_quotes" :label="__('statistics::admin.quotes')" />`
  after the comments tile; tabs list gains `['key' => 'quotes', 'label' => __('statistics::admin.tab_quotes')]`;
  new panel `id="tabs-panel-quotes"` / `aria-labelledby="tabs-tab-quotes"`, same
  classes as the others, holding one `<x-statistics::stat-widget statistic-key="global.total_quotes" :label="__('statistics::admin.quotes')" />`.
- `app/Domains/Statistics/Database/Seeders/E2eStatisticsSeeder.php` — `WEEKLY`
  gains `'global.total_quotes' => [2, 1, 0, -1, 3, 1]` (zero week at index 2 like
  the others, one negative week).
- `e2e/support/fixtures.ts` — `STATISTICS.quotes: [2, 1, 0, -1, 3, 1]`.
- `e2e/pages/AdminStatisticsPage.ts` — `StatisticsTab` gains `'quotes'`.
- `e2e/tests/core/admin-statistics.spec.ts` — open the `quotes` tab, assert one
  drawn chart; in weekly mode hover week index 3 and expect the tooltip
  `Semaine du … : -1`. Do not assert the quotes tile total nor the current week
  (other specs create quotes live with a sync queue).

**Tests.**
- Extend `app/Domains/Statistics/Tests/Feature/Admin/StatisticsControllerTest.php`:
  - `it('shows the quotes tile and the Citations tab')` — sees
    `__('statistics::admin.tab_quotes')`, `id="tabs-panel-quotes"`, and
    `__('statistics::admin.evolution', ['metric' => __('statistics::admin.quotes')])`.
  - `it('shows the current quote total in the summary tile')` — seed
    `global.total_quotes` snapshot to 7 (or create quotes), assert the tile shows 7.
  - Existing "keeps the summary tiles outside the graph mode switch" stays green
    (the new tile sits before the switch).
- E2E core spec above (run by VERIFY / `pnpm run e2e`; the gate type-checks it).

**Acceptance.**
- ✅ An admin sees six tiles, the sixth labelled « Citations ».
- ✅ The « Citations » tab shows the cumulative curve, and bars (one negative)
  in « Par semaine ».
- ✅ A `user-confirmed` user still gets 403 on `/admin/statistics` (existing test).
- ✅ No quote text, note or author appears on the page.
- ✅ `pnpm run gate` green; `pnpm run e2e` core admin-statistics spec green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| `/admin/statistics`, admin, desktop (≥1024px) | Six tiles fit on one row, « Citations » last, numbers aligned with the other tiles | |
| « Citations » tab, Cumulé | Tab label legible, panel shows one widget titled « Évolution : Citations », curve drawn, no console error | |
| « Citations » tab, Par semaine | Bars per week, the seeded negative week drawn below the axis, current week lighter, tooltip « Semaine du … : -1 » | |
| Mode switch persistence | Choose « Par semaine », reload, open « Citations » → still bars | |
| `/admin/statistics`, mobile (375px) | Tiles wrap 2 per row (3 rows), tab bar with four tabs does not overflow or clip, chart fits width | |
| Empty state | With no `global.total_quotes` data (fresh DB, no quotes): tile shows the same placeholder as other empty metrics, chart shows its empty state | |
| Role: tech-admin | Same page and tab as admin | |
| Role: moderator / `user-confirmed` | `/admin/statistics` refused (403 / redirect) as today; no nav entry (moderators excluded, DECISIONS #6) | |
| Live: reader deletes a quote | Tile total decreases by 1 after reload; today's weekly bar drops by 1 | |
| Live: user deactivated / reactivated | Total drops by the user's visible quote count, comes back after reactivation; a quote the reader deleted before deactivation does not come back | |
| Live: user deleted | Total drops by their visible quotes, not again if they were already deactivated | |
| Deleted chapter | Deleting a quoted chapter leaves the total unchanged | |
| Audit log `/admin/events/domain-events` | `Quote.ChapterPassageQuoted`, `Quote.ChapterPassageQuoteDeleted`, `Quote.UserQuotesRemoved`, `Quote.UserQuotesRestored` listed with readable summaries, no quote text in the new ones | |
| Recompute | `sail artisan statistics:compute global.total_quotes` → tile and curve end at the same total as before | |

## Open items

- **Before BUILD** — prerequisite `quote-hard-delete/` must be DONE (see the
  top of this plan, DECISIONS #7); it resolves architecture §9 risk 2. Phases 2
  and 4 check its behaviour. If it is not done when BUILD starts, stop and
  report rather than building against soft deletes. Rows already soft-deleted
  by readers in production before it lands are its concern (cleanup or not);
  whatever it leaves behind is what `getDailyNetChanges()` will count.
- **Resolved during PLAN** — architecture §9 risk 3 (`NotifyAuthorsOnQuoteCreated`
  possibly dormant): it is wired (FQCN subscription); registering the event does
  not change notifications. Phase 1 keeps the existing notification test as proof.
- **Resolved during PLAN** — moderator access: the user confirmed moderators do
  not see `/admin/statistics` (DECISIONS #6); spec §3 corrected. VERIFY checks
  the existing `admin` / `tech-admin` rule as-is.
