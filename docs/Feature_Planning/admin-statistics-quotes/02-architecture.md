# Admin statistics — quotes section — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Statistics** owns the new statistic `global.total_quotes` and the admin
tile/tab, exactly like `global.total_comments`. **Quote** owns the facts: it is
the only domain that knows when a quote appears or disappears, so it emits the
events and exposes its history. Statistics never reads Quote's table.

### 1.1 Changes in other domains

**Quote** (direct additions, no new extension point):

- registers its events with the bus (`ChapterPassageQuoted` is currently not
  registered, so stored events cannot be rehydrated — a prerequisite for the
  statistic, and for any replay);
- emits the new events of §3.4 from `QuoteService::delete` and from its three
  existing user-lifecycle listeners (deactivated / reactivated / deleted);
- exposes one read method giving the net daily change of the quote population
  (§3.1).

**Auth**: nothing. Statistics does not listen to Auth events.

## 2. Data model

No new table, no migration. The statistic uses `statistic_snapshots` and
`statistic_time_series` (daily granularity) like every other global statistic.
Key `global.total_quotes`, scope `global`, time series on.

### 2.3 Lifecycle rules

Mapped from spec §4.2 to the events of §3.4:

| What happens | Delta |
|--------------|-------|
| Reader saves a quote | +1 |
| Reader deletes one of their quotes | −1 |
| User deactivated | −(number of their currently visible quotes) |
| User deleted | −(number of their currently visible quotes); already-hidden rows leave a total that already excluded them |
| User reactivated | +(number of rows actually restored) |
| Chapter / story deleted | 0 — rows remain, they still count |

"Visible" = not soft-deleted. Reader deletion is a hard delete (task
`quote-hard-delete`, lands first), so soft-deleted rows only come from
deactivated users. The count is taken by Quote, inside its own
listener, before it changes the rows — so there is no ordering race with
Statistics.

## 3. PHP architecture

### 3.1 Public API

`QuotePublicApi` gains one method returning the net change of the quote
population per day, derived from the table: `+1` on `created_at`, `−1` on
`deleted_at` for soft-deleted rows. Shape: `array<string /* Y-m-d */, int>`.
Rows hard-deleted (by their reader or with their user) are gone and cannot
appear (see §9).

New public events in `Quote/Public/Events/`, same conventions as
`ChapterPassageQuoted` (name `Quote.<Class>`, version 1, payload round-trip):

| Event | Payload |
|-------|---------|
| `ChapterPassageQuoteDeleted` | `quoterId`, `chapterId`, `storyId` |
| `UserQuotesRemoved` | `userId`, `count` (> 0) — deactivation or deletion |
| `UserQuotesRestored` | `userId`, `count` (> 0) |

### 3.2 Services

- `QuoteService::delete` emits `ChapterPassageQuoteDeleted` after the hard delete.
- The three Quote user-lifecycle listeners count the affected rows first and
  emit `UserQuotesRemoved` / `UserQuotesRestored` only when the count is > 0.
- Statistics: `TotalQuotesStatistic` implements `StatisticDefinition`:
  `listensTo` the four Quote events; `computeDelta` returns `[null => ±n]`;
  `recompute` **does not replay events** — it clears the key's snapshot and
  series and rebuilds them from the §3.1 method, then fills cumulative values
  (the one place this statistic differs from the comments recipe, see §7 #2).
  The new event classes are added to `StatisticsServiceProvider`'s subscription
  list, otherwise the statistic silently never updates.

### 3.3 Policy / authorization

Unchanged: the existing `/admin/statistics` route middleware. Aggregate count
only; the events carry ids, never text or notes.

### 3.4 Events and listeners

Emitted: the three events above. Listened to (Statistics): those three plus
the existing `ChapterPassageQuoted` (+1). Quote keeps listening to Auth events
as today; Statistics does not.

### 3.5 Routes, controllers, form requests

None. The page controller passes no data; components read the statistic.

## 4. Frontend architecture

Reuse `x-statistics::stat-summary` for a sixth tile (summary grid goes from
five to six columns on large screens) and `x-statistics::stat-widget` inside a
new « Citations » tab of the existing `x-shared::tabs`. The page-level
« Cumulé / Par semaine » switch needs no work: both datasets are already
embedded per widget. No breakdown chart. French lang keys in Statistics'
`admin.php`. The E2E statistics seed and its TypeScript mirror gain the key.

## 5. Deptrac

`StatisticsPublic` and `StatisticsPrivate` → `QuotePublic` (the provider imports
the event classes, definitions and the recompute read `QuotePublicApi`); add to
`StatisticsTests` if tests use Quote's API. No edge needed from Quote (it
already allows `AuthPublic` and `EventsPublic`).

## 6. Testing strategy

Integration tests by default:

- **Quote**: each event is emitted with the right payload by delete,
  deactivate, reactivate and user-delete (including count 0 → no event);
  `ChapterPassageQuoted` and the new events round-trip through the stored-event
  rehydration; the history method returns the expected daily net changes.
- **Statistics**: create / delete / deactivate / reactivate / user-delete move
  the snapshot and daily series as per §2.3; `statistics:compute
  global.total_quotes` rebuilds the same totals as live events from a table
  holding live, soft-deleted and restored rows; the tile and tab render.
- **E2E / VERIFY**: tab and tile on desktop and mobile, weekly bars able to go
  negative.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | How does the total learn of deactivation, reactivation, user deletion? | **A** Quote emits count-carrying events · **B** Statistics listens to Auth and asks Quote for a count | **A** | B races with Quote's own listeners and cannot know the count of a hard-deleted user; A keeps Statistics ignorant of Auth and puts the events in the audit log |
| 2 | Where does history come from? | **A** `recompute` rebuilds from the `quotes` table · **B** replay stored events like the other statistics | **A** | Past deletions were never recorded, and `events_domain` can be pruned after 90 days; the table has `created_at`/`deleted_at`. Costs a Quote API method and one recipe deviation |

Decided without arbitration: events carry no quote id (not needed by any
consumer); `UserQuotesRemoved` covers both deactivation and deletion since the
statistic treats them identically; no clamp on the snapshot (other statistics
have none).

## 8. File layout

```
app/Domains/Quote/Public/Events/
    ChapterPassageQuoteDeleted.php
    UserQuotesRemoved.php
    UserQuotesRestored.php
app/Domains/Statistics/Private/Definitions/Global/
    TotalQuotesStatistic.php
```

Anything else is an edit to an existing file (see §1.1, §3.2, §4).

## 9. Risks acknowledged

- **Hard-deleted quotes (reader-deleted, or deleted with their user) are
  missing from rebuilt history**: after a `statistics:compute`, their creations
  and removal both vanish, so the curve differs slightly from the live-event
  curve for those periods. Accepted; the total stays right.
- **`RestoreOnUserReactivated` restores every soft-deleted quote of the user**
  (`withTrashed()->restore()`). Since reader deletion became a hard delete
  (`quote-hard-delete`), soft-deleted rows only come from deactivation, so
  reader-deleted quotes no longer come back on reactivation and the statistic
  counts exactly the restored rows.
- **`NotifyAuthorsOnQuoteCreated` may never have been wired** (it subscribes by
  class to an event that was not registered). PLAN verifies it when registering
  the event; if it was dormant, registering could start sending notifications.
