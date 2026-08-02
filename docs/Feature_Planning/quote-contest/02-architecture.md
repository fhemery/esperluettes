# Quote contest — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Decisions log: [`DECISIONS.md`](./DECISIONS.md)

## 1. Domain placement

The contest is a **Calendar activity type**, living at
`app/Domains/Calendar/Private/Activities/QuoteContest/`, alongside Jardino and
SecretGift. It is not a new domain: an activity is never useful on its own, it
always hangs off an `Activity` row, and nothing outside Calendar would call its
public API. This is the placement Calendar's README already argues for, and
decision #15 settled it.

Type key: `quote-contest`. Display component: `quote-contest::quote-contest-component`.
Config component: `quote-contest::quote-contest-config` — the first real user of
`configComponentKey()`.

### 1.1 Changes in other domains

#### Calendar core — the plugin config contract

`configComponentKey()` exists today and is **dead code**: nothing renders it and
both built-in types return `null`. This feature makes it real, because a
component key alone cannot save anything — `ActivityController` validates through
`ActivityRequest` and persists through the fixed `ActivityToCreateDto` /
`ActivityToUpdateDto`, neither of which knows about plugin fields.

`ActivityRegistrationInterface` grows two methods:

```php
/** Validation rules merged into ActivityRequest for this type. */
public function configRules(): array;

/** Persist the type's own config. Runs inside the activity's transaction. */
public function persistConfig(int $activityId, array $validated): void;
```

Both are given no-op defaults in the two existing registrations (`[]` and an
empty body) — the contest is the only implementor.

The admin create/edit form renders the config component when the selected type
declares one, and `ActivityController::store()` / `update()` wrap the activity
write and `persistConfig()` in one `DB::transaction()`. One save button, atomic:
a contest activity can never exist without its settings row.

Two consequences of the existing invariants, not new decisions:

- **Activity type is immutable after creation** (`CalendarPublicApi::update()`
  rejects changes), so the config component never has to migrate between types.
- On **create**, the type is chosen in the same form, so the config panel is
  shown/hidden client-side on the type `<select>`; only the selected type's
  rules are applied server-side.

#### Story — one additive read

`StorySummaryDto` carries `visibility` but **not** `is_excluded_from_events`,
which today only travels on `StorySnapshot` inside domain events. Eligibility
(assumption A2) needs both. `StorySummaryDto` gains a
`bool $is_excluded_from_events` field, populated in `fromModel()`. Purely
additive; `StoryPublicApi::getStoriesByIds()` already returns these DTOs in a
batch, which is the read the picker needs.

The two events the contest listens to already exist and need no change:
`StoryVisibilityChanged` (carries `oldVisibility`/`newVisibility`) and
`StoryExcludedFromEvents` (carries `storyId`).

#### Quote — one new read on the public API

`QuotePublicApi` has no contest-shaped read: `getForProfile()` is paginated and
viewer-scoped, and there is no "one quote by id for this owner". Two methods:

```php
/** Every quote this user owns, newest first, for the contest picker. */
public function getAllForOwner(int $userId): QuoteListDto;

/** One quote, only if $userId owns it; null otherwise. */
public function getOwnedQuote(int $quoteId, int $userId): ?QuoteDto;
```

`getAllForOwner()` returns the existing `QuoteListDto` with `page: 1` and
`totalCount` = the full count — no new DTO. `getOwnedQuote()` is the
authorization boundary for submission: the contest never queries `quotes`
itself and never sees a quote it does not own.

The private `note` is on `QuoteDto` and the contest simply never reads it
(assumption A1). It is never copied into an entry, so it cannot leak later.

#### Notification — no change

Five notification content classes are added, all owned by the contest. No
Notification-side change: `createNotification(array $userIds, …)` already does
what the broadcasts need (see §3.4 for why `createBroadcastNotification()` is
the wrong call).

## 2. Data model

Three tables, all owned by the activity sub-module, all prefixed
`calendar_quote_contest_`. FKs point at `calendar_activities` and at each other
— same domain, so constraints are allowed. User ids are plain
`unsignedBigInteger` with no FK, per the project rule.

### 2.1 Tables

**`calendar_quote_contest_settings`** — one row per contest activity.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `id` | id | no | |
| `activity_id` | unsignedBigInteger | no | FK → `calendar_activities`, cascade delete, **unique** |
| `submissions_end_at` | datetime | no | *fin des soumissions* |
| `votes_start_at` | datetime | no | *début des votes* |
| `notified_submissions_open_at` | datetime | yes | scheduler idempotence marker |
| `notified_submissions_closing_at` | datetime | yes | idem |
| `notified_votes_open_at` | datetime | yes | idem |
| `notified_votes_closing_at` | datetime | yes | idem |
| timestamps | | | |

No description column — the contest reuses `calendar_activities.description`,
which is already rich text and already rendered above the plugin component on
the activity page. The submission/vote window bounds (*début des soumissions*,
*fin des votes*) are the activity's own `active_starts_at` / `active_ends_at`,
shown greyed and read-only in the config panel per §4.1.3.

Index: the unique on `activity_id` is the only lookup key. The scheduler scans
this table whole — it holds one row per contest ever created, which is a handful.

**`calendar_quote_contest_categories`**

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `id` | id | no | |
| `activity_id` | unsignedBigInteger | no | FK → `calendar_activities`, cascade delete |
| `title` | string(160) | no | |
| `description` | text | yes | plain text (assumption A3), escaped on render |
| `position` | unsignedInteger | no | display order, admin-controlled |
| timestamps | | | |

Index: `(activity_id, position)`.

**`calendar_quote_contest_entries`** — the snapshot.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `id` | id | no | |
| `activity_id` | unsignedBigInteger | no | FK → `calendar_activities`, cascade delete |
| `category_id` | unsignedBigInteger | no | FK → `…_categories`, cascade delete |
| `user_id` | unsignedBigInteger | no | submitter, no FK |
| `quote_id` | unsignedBigInteger | no | provenance only — **never dereferenced for display** |
| `story_id` | unsignedBigInteger | no | drives the eligibility listeners |
| `highlighted_text` | text | no | snapshot |
| `story_title` | string(255) | no | snapshot |
| `story_slug` | string(255) | no | snapshot |
| `chapter_id` | unsignedBigInteger | no | |
| `chapter_title` | string(255) | no | snapshot |
| `chapter_slug` | string(255) | no | snapshot |
| `author_user_ids` | json | no | snapshot of *who* — names resolved live |
| `withdrawn_at` | datetime | yes | privacy withdrawal (§2.3) |
| timestamps | | | |

Indexes: `(category_id, withdrawn_at)` — the vote and results listings;
`(activity_id, user_id)` — "my entries"; `(story_id, withdrawn_at)` — the
eligibility listeners.

`quote_id` exists so a moderator can trace an entry back and so a future feature
is not blocked, but no read path resolves it: the entry is self-sufficient by
design, which is what makes it survive the source quote's deletion (§5).

**One entry per (category, user)** is enforced **in the service, not by a unique
index.** A unique index cannot express "one *non-withdrawn* entry per user" —
MySQL treats each `NULL` `withdrawn_at` as distinct, so the index would allow
exactly the duplicates it is meant to stop. Withdrawal by the submitter
hard-deletes the row (no votes can exist yet), so the only rows that accumulate
are privacy-withdrawn ones, and the service filters on `withdrawn_at IS NULL`.

**`calendar_quote_contest_votes`**

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `id` | id | no | |
| `category_id` | unsignedBigInteger | no | FK → `…_categories`, cascade delete |
| `entry_id` | unsignedBigInteger | no | FK → `…_entries`, cascade delete |
| `user_id` | unsignedBigInteger | no | voter, no FK |
| timestamps | | | |

Unique: `(category_id, user_id)` — one ballot per reader per category, at the
database level. This one *is* expressible, because changing a vote updates the
row rather than adding one.
Index: `(entry_id)` for the tally.

### 2.2 Models

`QuoteContestSettings`, `QuoteContestCategory`, `QuoteContestEntry`,
`QuoteContestVote`, all with PHP attribute syntax:

```php
#[Table('calendar_quote_contest_entries')]
#[Fillable([...])]
class QuoteContestEntry extends Model
{
    protected $casts = [
        'author_user_ids' => 'array',
        'withdrawn_at' => 'datetime',
    ];
}
```

Relations stay inside the sub-module: `Settings belongsTo Activity`,
`Category hasMany Entry`, `Entry hasMany Vote`, `Entry belongsTo Category`.
No relation crosses a domain boundary.

### 2.3 Lifecycle rules

Mapped from §5 of the spec.

| Trigger | Mechanism |
|---------|-----------|
| Quote edited or deleted | Nothing. The entry is a snapshot and holds no live reference it reads. |
| Chapter deleted or unpublished | Nothing. The stored `chapter_slug` link may 404 — the same behaviour the quote book already has. |
| Story turns private / excluded from events | Listener sets `withdrawn_at` on every non-withdrawn entry for that `story_id`. Votes rows stay; every count and every listing filters on `withdrawn_at IS NULL`, so they stop counting. The category slot frees up (the service's one-per-category check ignores withdrawn rows), so the submitter may enter another quote while submissions are open. |
| Story returns to public | **No automatic restore.** Re-entering is the reader's action, during the submission period. Auto-restoring would resurrect an entry the author may have withdrawn deliberately. |
| Submitter deactivated or deleted | Nothing. Entries and votes stay (decision #7). *Résultats* shows the entry with an unresolvable submitter. |
| Voter deactivated or deleted | Nothing. Ballots stay counted. |
| Moderation deletes an entry | Hard delete. Votes cascade away by FK. Submitter notified. |
| Submitter withdraws or replaces | Hard delete of the old row; votes cascade. Only reachable during submissions, when no vote exists. |
| Category deleted | Only permitted while it holds **zero** entries (withdrawn included — a withdrawn entry is still evidence). Service-level check, not a DB constraint, so the refusal can carry a French message. |
| Activity deleted | Everything cascades by FK from `calendar_activities`. |

## 3. PHP architecture

### 3.1 Public API

**None.** The contest exposes no public API — nothing outside Calendar consumes
it. It lives entirely under `Private/Activities/QuoteContest/`, which is exactly
why it is a sub-module and not a domain.

The only cross-domain contracts this feature adds are on *other* domains'
public APIs: `QuotePublicApi::getAllForOwner()` / `getOwnedQuote()`,
`StorySummaryDto::$is_excluded_from_events`, and the two new
`ActivityRegistrationInterface` methods (§1.1).

### 3.2 Services

Four services, each with one job. Controllers and view components call these;
neither ever touches a model.

- **`QuoteContestConfigService`** — settings and categories CRUD for the admin.
  Owns the date-ordering rule and the "category must be empty to delete" refusal.
- **`QuoteContestPhaseService`** — the single source of truth for *what phase are
  we in*, derived from the activity's `active_starts_at` / `active_ends_at` and
  the settings' two dates. Returns one enum:

  ```php
  enum QuoteContestPhase { case BeforeStart; case Submissions; case Interlude;
                           case Voting; case Ended; }
  ```

  Every screen and every write authorization asks this one question. Nothing
  else recomputes phase from raw dates — that is how the read-only states of
  §4.4 and §4.5.8 stay consistent with the write guards.
- **`QuoteContestSubmissionService`** — the picker's eligibility computation,
  submit / replace / withdraw, and snapshot construction.
- **`QuoteContestVoteService`** — cast/change a vote, the seeded shuffle, and
  the moderator tally.

**Eligibility** is computed in one place, in the submission service: batch
`StoryPublicApi::getStoriesByIds()` over the distinct `story_id`s of the
reader's quotes, then per quote a reason of
`null | 'private_story' | 'excluded_from_events'`. One extra query for the whole
picker regardless of quote count — no N+1.

**Snapshot construction** reads `QuotePublicApi::getOwnedQuote()` (which already
carries story/chapter titles and slugs and `authorProfiles`) and re-checks
eligibility server-side before writing. The picker greying out an ineligible
quote is a courtesy; the service refusal is the enforcement.

**The seeded shuffle** orders a category's entries with a PHP-side shuffle
seeded on `crc32($userId . ':' . $categoryId)`. Deterministic per reader, stable
across reloads, no stored column, no extra query, and reproducible in tests by
fixing the ids.

**Vote counts are computed on read** — a `GROUP BY entry_id` on the votes table,
scoped to one activity. No denormalised counter: the only reader is the
*Résultats* tab, seen by a handful of moderators, and a counter column would add
a write path to keep correct for no measurable gain.

### 3.3 Policy / authorization

Enforcement is layered, and the layers are deliberate:

1. **Activity access** — `ActivityService::findVisibleBySlugOrFail()` already
   404s on `role_restrictions`. The admin sets `user-confirmed` + `moderator` +
   `admin`, which is the whole of decision #1 and assumption A5. A non-confirmed
   `user` gets a 404 on the page and never sees the activity in the listing.
   No new gating code.
2. **Phase** — every write route asks `QuoteContestPhaseService` first. Submit /
   replace / withdraw require `Submissions`; vote requires `Voting`. A request
   that arrives outside its phase is a 403, not a redirect — the UI never offers
   the action, so reaching it means a forged request.
3. **Ownership** — a submission may only reference a quote the caller owns, which
   `QuotePublicApi::getOwnedQuote()` decides, not the contest.
4. **Moderation** — entry deletion and the *Résultats* tab require
   `moderator` or `admin` via `AuthPublicApi`, checked in the controller **and**
   in the view component that builds the tabs array. The tab is omitted
   server-side for everyone else (spec §"Notes parked for DESIGN" #1) — it is
   never rendered and then hidden.

**Anonymity is a query-shape guarantee, not a template one.** The view models the
reader-facing tabs receive carry no `user_id` at all: the submission and vote
services map entries into a DTO without the submitter. Only the *Résultats* path
maps a DTO that includes it. A template mistake therefore cannot leak a
submitter's identity, because the identity is not in the object.

### 3.4 Events and listeners

**Emitted:** none. The spec requires none, and speculative events are exactly
what rule #2 of `AGENTS.md` forbids.

**Listened to**, wired in `QuoteContestServiceProvider` via `EventBus`, following
`JardinoServiceProvider::registerEventListeners()`:

- `Story::VisibilityChanged` → withdraw entries when `newVisibility` is neither
  `public` nor `community`. The event carries both old and new visibility, so
  the listener needs no Story read.
- `Story::ExcludedFromEvents` → withdraw entries for that story unconditionally.

Both funnel into one `withdrawEntriesForStory(int $storyId)` on the submission
service, so the two paths cannot drift.

The listener is a no-op for stories with no entries, which is almost all of
them — it is a single indexed `UPDATE … WHERE story_id = ? AND withdrawn_at IS NULL`.

**Notifications.** Five content classes implementing `NotificationContent`:
`SubmissionsOpenNotification`, `SubmissionsClosingNotification`,
`VotesOpenNotification`, `VotesClosingNotification`, `EntryRemovedNotification`.

The four broadcasts **must not** use
`NotificationPublicApi::createBroadcastNotification()` — it targets
`Roles::USER` **and** `Roles::USER_CONFIRMED`, and decision #10 restricts the
audience to confirmed users, for whom alone the link is not dead. They use
`createNotification($userIds, $content)` with
`AuthPublicApi::getUserIdsByRoles([Roles::USER_CONFIRMED], activeOnly: true)`.

**Scheduling.** A `calendar:quote-contest-notify` Artisan command on a 5-minute
cron in `bootstrap/app.php`, mirroring `story:publish-scheduled-chapters` — the
app's only precedent for date-triggered work, and the reason no queue is
involved. Each tick scans `…_settings` for contests whose trigger moment has
passed and whose matching `notified_*_at` column is still null, sends, then
stamps the column in the same transaction. Idempotence is the column, so a
double tick, a redeploy mid-run or a replayed cron sends nothing twice.

Two behaviours that follow from this and are deliberate:

- A contest whose start date is already in the past when it is created fires
  "submissions open" on the next tick. Correct: the audience should be told the
  contest is open, late rather than never.
- If an admin moves a date *forward* past a moment already stamped, nothing
  re-fires. Re-notifying on an admin's date correction would spam the whole
  confirmed user base.

This is the mechanism `calendar-notifications/` will generalise from later
(decision #9). It stays inside the sub-module until there is a second example to
generalise *from* — one instance is not a pattern.

### 3.5 Routes, controllers, form requests

Admin routes (`admin` middleware, `calendar.admin.quote-contest.*`) for category
CRUD only — the settings ride along with the activity form:

| Verb | Path | Purpose |
|------|------|---------|
| POST | `/admin/calendar/quote-contest/{activity}/categories` | add |
| PUT | `/admin/calendar/quote-contest/{activity}/categories/{category}` | edit |
| DELETE | `/admin/calendar/quote-contest/{activity}/categories/{category}` | delete, refused if non-empty |

Reader routes (`web`, `auth`, `verified`), following SecretGift's `routes.php`:

| Verb | Path | Purpose |
|------|------|---------|
| POST | `/calendar/quote-contest/{activity}/entries` | submit or replace |
| DELETE | `/calendar/quote-contest/{activity}/entries/{entry}` | withdraw (own) |
| PUT | `/calendar/quote-contest/{activity}/votes/{category}` | cast or change a vote |

Moderation route (`web`, `auth`, role-checked in the controller):

| Verb | Path | Purpose |
|------|------|---------|
| DELETE | `/calendar/quote-contest/{activity}/moderation/entries/{entry}` | delete any entry |

**No `PATCH` anywhere** — the production WAF resets the connection on that verb.
Vote changes are `PUT` on the category, which is the right shape anyway: the
resource is "this reader's ballot in this category", and it is idempotent.

Form requests: `SaveContestConfigRequest`-shaped rules are contributed by
`configRules()` into the existing `ActivityRequest` rather than living in their
own request class — that is the point of the contract. `SaveCategoryRequest`,
`SubmitEntryRequest` and `CastVoteRequest` are ordinary form requests. The
date-ordering rule (`début activité ≤ fin soumissions ≤ début votes ≤ fin
activité`, assumption A4) is a rule in `configRules()`, with French messages, so
a violation renders as a field error on the activity form.

## 4. Frontend architecture

Blade-first, Alpine only where there is genuine client state. Nothing here needs
a build-step JS module.

**The reader page** is one component, `QuoteContestComponent`, rendered by the
generic activity page through `displayComponentKey()` — the SecretGift shape
exactly. It builds the tabs array server-side and hands each tab a view model:

```
<x-shared::tabs :tabs="$tabs" tracking>   {{-- Mes citations · Votes · Résultats --}}
```

`tracking` gives the URL hash, so a notification can deep-link to `#votes`.
The *Résultats* entry is simply absent from `$tabs` for non-moderators.

**Mes citations.** Server-rendered: the categories with the reader's current
entry in each, and the full quote list. One `x-data` holds the filter string and
the selected quote id; the filter is a client-side substring match over the
already-rendered quotes, so typing costs no round trip. Ineligible quotes render
with their reason as **text** next to them, not colour alone (spec §6,
accessibility), and carry `aria-disabled`. Replacing an occupied category shows
the sitting quote and asks for confirmation before the POST — a
`<x-shared::modal>`, not a JS `confirm()`.

Outside the submission phase the same view renders read-only with the countdown
(§4.4) — one template, driven by the phase enum, not two templates to keep in
sync.

**Votes.** One `<fieldset>` per category containing a **radio group**, which is
the accessible shape for "one choice among N" for free: keyboard operable,
state exposed to a screen reader, no ARIA hand-rolling. Entries are pre-shuffled
server-side (§3.2). Each entry card shows the passage, the story title as a
link, the chapter title as a link, and the author names. No vote count appears
anywhere in this template — there is none in the view model to print.

**Résultats.** A plain server-rendered table per category: entry, vote count,
submitter. No Alpine. Deletion is a form POST with a confirmation modal.

**Mobile.** Entry cards stack at `sm:`; the tabs component already handles
narrow viewports with its `scrollable` mode. The category → entries drill-down
is the existing `<x-shared::tabs>` plus stacked cards — no new responsive
machinery.

**Reused, not duplicated:** `<x-shared::tabs>`, `<x-shared::modal>`,
`<x-shared::badge>`, `<x-shared::title>`, `<x-shared::input-label>`,
`<x-shared::input-error>`. The contest builds no new shared component. It also
does **not** reuse Quote's `profile-tab` component — the spec (§4.3.2) is
explicit that this is a different screen with different affordances.

**i18n.** One namespace, `quote-contest`, loaded by the sub-module's service
provider from `Resources/lang/fr/`. Every user-visible string, including the
ineligibility reasons and the validation messages, lives there.

## 5. Deptrac

`CalendarPrivate` already allows `Shared`, `CalendarPublic`, `AuthPublic`,
`StoryPublic`, `EventsPublic`, `MediaPublic`. Two edges are missing:

| New edge | Justification |
|----------|---------------|
| `CalendarPrivate → QuotePublic` | The picker reads the reader's own quotes and resolves one owned quote to snapshot it. Nothing else in Calendar touches Quote. |
| `CalendarPrivate → NotificationPublic` | The contest owns its five notifications (decision #9); Calendar has never sent one before. |

`StoryPublic` needs no new edge — Jardino already established it, which is why
the eligibility read and the two story listeners cost nothing architecturally.

No edge is needed in the other direction: Quote and Story know nothing about the
contest. Note that `QuotePublic → StoryPublic` already exists, so
`StorySummaryDto` gaining a field breaks nothing.

## 6. Testing strategy

Integration (feature) tests are the default, under
`app/Domains/Calendar/Tests/Feature/QuoteContest/`, with a `helpers.php` next to
Jardino's and SecretGift's.

**Integration — the bulk.** Admin config (date ordering rejected in both
directions, category CRUD, delete refused on a non-empty category, config
persisted atomically with the activity). Access by role, including the
non-confirmed `user` getting a 404 on the page *and* an absence from the
listing. Each phase's read-only-ness, driven by travelling the clock rather than
by stubbing the phase service. Submit / replace / withdraw, one-per-category,
same quote in several categories, ineligible quote refused server-side even when
the request is forged. Vote cast, vote changed, unique ballot per category, vote
for one's own entry allowed. Moderation deletion dropping votes and notifying
the submitter. The two story listeners withdrawing entries and freeing the slot.
The scheduler command: fires once, does not re-fire on a second tick, and
targets confirmed users only — that last assertion is the guard on the
`createBroadcastNotification()` trap in §3.4.

**Anonymity gets its own tests**, asserted at the *response* level: a confirmed
user's vote-tab response contains no submitter name and no vote count, for an
entry they did not submit and for one they did.

**Unit — two places only**, where the logic is genuinely isolated: the phase
enum derivation (a pure function of five datetimes, cheap to table-test across
every boundary) and the seeded shuffle's determinism.

**Vitest — none.** The Alpine here is a substring filter and a radio group; there
is no extracted JS module to test, and inventing one to have something to unit
test would be backwards.

**VERIFY only** — what tests cannot see: the tabs actually switching and the hash
deep-link working, the filter box feeling instant on a long quote book, the
replace-confirmation modal, the greyed ineligible rows reading correctly, the
radio group's keyboard path, and the three tabs on a narrow viewport. Plus the
one thing integration tests structurally cannot cover: that the *Résultats* tab
is **absent** from a confirmed user's rendered page.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | How does the admin configure the contest? | (a) contest-owned separate admin page, Jardino/SecretGift style; (b) wire `configComponentKey()` for real, with a rules+persist contract | **(b)** | The dead hook was an acknowledged gap from the first activities, not a deliberate omission. The contest is its first real consumer, so the contract gets designed against a concrete need instead of guessed at. One form, one save, atomic. |
| 2 | Where does the contest description live? | (a) reuse `calendar_activities.description`; (b) a second description on the settings row | **(a)** | It already exists, is already rich text, and is already rendered above the plugin component. A second field is additive later if the admin ever wants activity blurb and contest rules separated. |
| 3 | How is an entry withdrawn when its story loses eligibility? | (a) hard-delete the entry and its votes, as decision #4 reads literally; (b) a `withdrawn_at` soft flag, votes preserved but uncounted | **(b)** | Same read cost (one indexed column filter), but an author flipping visibility by mistake no longer destroys other readers' votes irrecoverably, and *Résultats* can still show what happened. Refines decision #4: votes are dropped from the count, not from the table. |
| 4 | How are an entry's authors snapshotted? | (a) freeze the names as text; (b) store `author_user_ids`, resolve names live via `ProfilePublicApi` | **(b)** | A renamed author shows their current name, as they do on every other surface in the app; a frozen name would read as a bug. Deleted users resolve to null and are omitted, and the entry still stands per §5. |
| 5 | Cache the rendered vote screen? (user's own parked note #5) | (a) cache the category listing for the vote period; (b) no cache | **(b)** | Because entries are full snapshots, the listing is already one indexed `SELECT` plus one batched profile lookup — there is no cross-domain read left to save. A cache would buy nothing measurable and cost three invalidation paths (moderation delete, privacy withdrawal, profile rename), one of which going missing means a withdrawn passage stays on display. Revisit if it ever appears in slow-query logs. |
| 6 | The *Mes citations* picker on a long quote book (open question #1) | (a) server-paginated picker with search; (b) load all, client-side Alpine filter | **(b)** | One query, and picking stays instant with no round trips. A 500-quote book is ~150 KB of passages. Flip to (a) if real books run into the thousands. |
| 7 | Order of entries on the vote screen (open question #2) | (a) stable by id; (b) reshuffled every load; (c) shuffled, seeded on (reader, category) | **(c)** | (a) systematically advantages whoever submitted first — the fairness problem the spec flagged. (b) reorders under the reader between reloads. (c) removes the positional advantage while keeping the list identical on every reload, for no stored column and no extra query. |
| 8 | Vote counts: stored counter or computed? | (a) denormalised counter on the entry; (b) `GROUP BY` on read | **(b)** — decided, not arbitrated | The only reader is the *Résultats* tab, seen by a handful of moderators. A counter adds a write path to keep correct in five places for no measurable gain. |
| 9 | One entry per category: unique index or service check? | (a) unique index on `(category_id, user_id)`; (b) service-level check | **(b)** — decided, not arbitrated | With soft withdrawal, MySQL treats every `NULL` `withdrawn_at` as distinct, so the index would permit exactly the duplicates it exists to prevent. Votes keep their real unique index, because changing a vote updates a row rather than adding one. |

## 8. File layout

New files, under `app/Domains/Calendar/Private/Activities/QuoteContest/`:

```
QuoteContestRegistration.php          implements the 4-method interface
QuoteContestServiceProvider.php       views, lang, migrations, routes, listeners, command
Console/
  NotifyQuoteContestCommand.php
Database/Migrations/
  2026_08_02_100000_create_calendar_quote_contest_settings_table.php
  2026_08_02_100001_create_calendar_quote_contest_categories_table.php
  2026_08_02_100002_create_calendar_quote_contest_entries_table.php
  2026_08_02_100003_create_calendar_quote_contest_votes_table.php
Http/
  routes.php
  Controllers/
    QuoteContestCategoryController.php    admin
    QuoteContestEntryController.php       reader
    QuoteContestVoteController.php        reader
    QuoteContestModerationController.php  moderator
  Requests/
    SaveCategoryRequest.php
    SubmitEntryRequest.php
    CastVoteRequest.php
Listeners/
  WithdrawEntriesOnStoryIneligible.php
Models/
  QuoteContestSettings.php
  QuoteContestCategory.php
  QuoteContestEntry.php
  QuoteContestVote.php
Notifications/
  SubmissionsOpenNotification.php
  SubmissionsClosingNotification.php
  VotesOpenNotification.php
  VotesClosingNotification.php
  EntryRemovedNotification.php
Resources/
  lang/fr/quote-contest.php
  views/
    components/quote-contest.blade.php          reader page, the 3 tabs
    components/quote-contest-config.blade.php   admin config panel
    partials/_my-quotes.blade.php
    partials/_votes.blade.php
    partials/_results.blade.php
Services/
  QuoteContestConfigService.php
  QuoteContestPhaseService.php
  QuoteContestSubmissionService.php
  QuoteContestVoteService.php
Support/
  QuoteContestPhase.php          enum
View/
  Components/QuoteContestComponent.php
  Models/                        per-tab view models; the reader-facing ones
                                 structurally carry no submitter identity
```

Tests: `app/Domains/Calendar/Tests/Feature/QuoteContest/` + `helpers.php`.

Notifications live under the sub-module rather than in a `Public/` folder
because nothing outside Calendar constructs them — unlike Quote's, which the
Story domain triggers. Deptrac allows `CalendarPrivate → NotificationPublic`
once §5's edge is added, so implementing `NotificationContent` from here is
legal.

## 9. Risks acknowledged

**The plugin config contract is designed from one example.** `configRules()` +
`persistConfig()` fit the contest exactly; a future type needing conditional
rules or a multi-step config will strain them. Accepted deliberately — the
alternative was designing the contract with no consumer at all, which is how it
became dead code the first time. Revisit when a second type implements it.

**Snapshot rot.** Story and chapter titles freeze at submission. A story renamed
mid-contest shows its old title on the vote screen while its link points at the
new slug — and the slug is `{base}-{id}`-shaped, so the link still resolves.
Cosmetic, and the price of §4.3.11's snapshot requirement.

**The scheduler is a 5-minute cron with no catch-up log.** If the app is down
across a trigger moment, the notification fires late on the next tick, which is
correct. But if the *send* half-succeeds — some users notified, then a crash
before the column is stamped — the next tick re-notifies everyone. The window is
one command run, and the failure mode is a duplicate notification rather than a
lost one, which is the right way round. Revisit if it ever happens.

**Loading a whole quote book in one page** (tradeoff 6) is bounded by nothing
today. The trigger to revisit is a real user reporting a slow *Mes citations*
tab, or the Quote domain gaining a bulk-import path.

**`withdrawn_at` is a filter that must not be forgotten.** Every listing, every
tally and the one-per-category check filter on it. A new read path that omits it
silently resurrects a withdrawn passage — the exact privacy failure decision #4
exists to prevent. Mitigated by keeping all reads inside the four services and
covering the withdrawal path in integration tests, not by a database constraint,
because there is no constraint that expresses it.
