# Quote Contest — *Concours de citations*

Confirmed readers enter passages from their own quote book into admin-defined
categories; once submissions close, everyone confirmed votes for one quote per
category. Entries are anonymous to readers and identified only to moderation,
which never publishes a result: vote counts exist for moderators alone.

This is a Calendar activity plugin. The generic parts — activity states, role
restrictions, the registry — are documented in the
[Calendar README](../../../README.md).

## How it plugs in

- `QuoteContestRegistration` (type key `quote-contest`) exposes the display
  component `quote-contest::quote-contest-component` **and** a config component,
  `quote-contest::quote-contest-config`. It is the only type that uses the
  config half of `ActivityRegistrationInterface`: `configRules()` validates the
  two contest dates inside the activity form, and `persistConfig()` writes the
  settings row in the activity's own transaction — a contest activity can never
  exist without one.
- `QuoteContestServiceProvider` loads the activity's views (`quote-contest::`),
  translations, migrations, routes, the notify command and the notification
  registrations, and subscribes the two Story listeners.
- The broadcast command is scheduled in `bootstrap/app.php`, every five minutes.

**Access is configuration, not code.** Nothing here gates the page: the contest
relies on Calendar's `role_restrictions`, which an admin must set to
`user-confirmed` + `moderator` + `admin` when creating the activity. Forget it
and a non-confirmed `user` reaches the contest. The write routes re-check phase
and ownership themselves, since a forged POST never went past a rendered page.

## Tables

| Table | Holds |
|---|---|
| `calendar_quote_contest_settings` | One row per contest: `submissions_end_at`, `votes_start_at`, and the four `notified_*_at` idempotence markers of the broadcasts. |
| `calendar_quote_contest_categories` | The admin-defined buckets: `title`, plain-text `description`, `position`. |
| `calendar_quote_contest_entries` | One reader's quote in one category — a full snapshot (passage, story/chapter title and slug, `author_user_ids`), plus the soft `withdrawn_at`. |
| `calendar_quote_contest_votes` | One ballot per (category, user), unique in the database. Changing a vote updates the row. |

## The phase

`QuoteContestPhaseService::phaseFor()` is the single source of truth for what a
contest is doing; nothing else may derive a phase from raw dates. Four datetimes
build the timeline — the activity's own `active_starts_at` / `active_ends_at`
bound it, the settings' two dates cut it up:

```
active_starts_at ─── submissions_end_at ─── votes_start_at ─── active_ends_at
   Submissions            Interlude              Voting
```

`BeforeStart` before the first, `Ended` after the last. An instant landing
exactly on a boundary belongs to the **later** phase, matching how
`Activity::state` reads the same two activity dates. Equal `submissions_end_at`
and `votes_start_at` simply means no interlude. A null start date keeps the
contest in `BeforeStart` forever; a null end date means it never `Ended`.

## Rules a reader would get wrong

**An entry is a snapshot, and `quote_id` is provenance only.** No read path
dereferences it. Editing or deleting the source quote, renaming the story,
deleting the chapter — none of it touches the entry. Only losing the *right to
read the passage* does, and that is the withdrawal below.

**`withdrawn_at` is a filter, never a deletion.** It is stamped when the quoted
story turns private or is excluded from events; the row and its votes stay in
the table so an accidental visibility toggle is recoverable. Every listing,
every tally and the one-per-category check must filter on `withdrawn_at IS NULL`
— a read path that forgets it resurrects a passage nobody may read any more.
Nothing ever clears the column: a story returning to public does not restore its
entries, the reader re-enters by hand. The entries table deliberately carries
**no** unique index on `(category_id, user_id)` — MySQL treats each NULL
`withdrawn_at` as distinct, so the rule is not index-expressible and
`QuoteContestSubmissionService` enforces it instead.

**Anonymity is a query shape, not a template rule.** A submitter's identity and
a vote count exist in exactly one family of view models, `Results*ViewModel`,
built only by `QuoteContestVoteService::resultsFor()`. `VoteEntryViewModel` and
`MyEntryViewModel` have nowhere to put either, so no Blade slip can leak who
submitted what to the *Votes* tab that every confirmed user sees. The
*Résultats* tab is likewise **absent** from the tabs array for non-moderators,
not rendered and hidden.

**Vote counts are computed on read**, with one `GROUP BY entry_id` per results
page. There is no denormalised counter: its only readers would be the handful of
moderators who open the tab, and it would cost an invalidation path on every
vote, withdrawal and deletion.

**Entry order on the vote screen is shuffled, seeded on (reader, category).** No
positional advantage for early submitters, and the order is identical on every
reload so the entry a reader was considering never moves. See `SeededShuffle`.

**The author names on an entry are resolved live**, from the stored
`author_user_ids`, through `ProfilePublicApi` — never frozen into the row. They
are the *story's* authors and never the submitter.

**Eligibility is computed in one place.** A quote is enterable when its story is
`public` or `community` and not `is_excluded_from_events`;
`QuoteContestSubmissionService` decides that both for the picker (which greys
the row and prints the reason as text) and for the submission itself. Greying is
a courtesy, the service is the enforcement point.

**The reader's private note on a quote never enters the contest** and appears
nowhere, including *Résultats*.

## Screens

One page, three tabs, keyed on the URL hash fragment so a notification can
deep-link straight to `#votes`:

| Tab | Key | Who | What |
|---|---|---|---|
| *Mes citations* | `my-quotes` | every confirmed user | The categories with the reader's own entry in each, plus — during the submission phase only — their whole quote book, filtered client-side. |
| *Votes* | `votes` | every confirmed user | Present in every phase; the ballot is only built once the votes open. One `<fieldset>` radio group per category, disabled outside the vote phase. |
| *Résultats* | `results` | `moderator`, `admin`, `tech-admin` | Every entry with its vote count and submitter, ordered by count descending, with the delete action. |

Outside the phase that needs them, the picker and the ballot are not built at
all — the reader's quote book and the contest's entries are simply not read.

## Moderation

`QuoteContestModerationController::ROLES` — `[moderator, admin, tech-admin]` —
gates both the *Résultats* tab and the single moderation write, a DELETE on an
entry, allowed at any point in the contest's life. There is **no** results
route: the tab lives inside the activity page a confirmed user may legitimately
open, and the denial is that the view model is never built for them.

Deleting an entry drops its votes, frees the category slot, and notifies the
submitter — unless the moderator is deleting their own entry, or the submitter's
account no longer resolves.

## Notifications

Five, all in the Calendar-wide `calendar` notification group. Four are
date-triggered broadcasts to confirmed users, sent by
`calendar:quote-contest-notify`; the fifth is targeted.

| Type | Trigger |
|---|---|
| `SubmissionsOpenNotification` | The activity's `active_starts_at` has passed. |
| `SubmissionsClosingNotification` | 24 h before `submissions_end_at`. |
| `VotesOpenNotification` | `votes_start_at` has passed. |
| `VotesClosingNotification` | 24 h before the activity's `active_ends_at`. |
| `EntryRemovedNotification` | Moderation deleted this reader's entry. |

**Idempotence is a column, not a lock.** Each broadcast stamps its own
`notified_*_at` in the same transaction as the send, so a double tick, a
redeploy mid-run or a replayed cron sends nothing twice. Two consequences are
deliberate: a contest whose dates are already past when it is created fires its
due broadcasts on the next tick, and moving a date forward past a stamped moment
re-fires nothing. A tick that finds no confirmed user at all sends nothing and
stamps nothing.

## Listens to

Both funnel into `QuoteContestSubmissionService::withdrawEntriesForStory()`, so
the two paths cannot drift.

- `Story::VisibilityChanged` → `WithdrawEntriesOnStoryIneligible::handleVisibilityChanged()`
  — withdraws unless the new visibility is `public` or `community`. The event
  carries the visibility, so no Story read is made.
- `Story::ExcludedFromEvents` → `WithdrawEntriesOnStoryIneligible::handleExcludedFromEvents()`
  — withdraws unconditionally.

Both are subscribed as **closures that resolve the listener when the event
fires**. Resolving it at boot would drag the Quote and Story public APIs into
the container on every request and freeze what those singletons hold at boot
time.

## Admin configuration

The two contest dates live in the config panel rendered inside the activity
create/edit form, just above the save button; the activity's own start and end
are mirrored beside them, greyed and read-only. Validation enforces
`début activité ≤ fin soumissions ≤ début votes ≤ fin activité` from the payload
alone — the activity dates travel in the same request.

Categories are managed by their own three routes — middleware allows
`admin`, `tech-admin`, and `moderator` (same staff set as Calendar activity
admin). Each row needs its own `<form>` and the block is **pushed to the
`activity-config-extras` stack** that the activity pages render *after*
`</form>`: nesting forms is illegal HTML and browsers silently drop the inner
one. Categories can be added and edited at any time; deletion is refused while
the category holds any entry, withdrawn or not. Content moderation (*Résultats*,
entry delete) uses `QuoteContestModerationController::ROLES` separately; the role
set is the same three roles.

## Not done

- Nothing marks or stores a winner, and no result is ever published to readers —
  a moderator reads the tally and announces it elsewhere.
- No abstention is recorded: "has not voted" and "chose not to vote" are the
  same state.
- No minimum entry count for a category to be votable; an empty category shows
  empty.
- The reader's quote book is loaded whole and filtered client-side. Fine for a
  few hundred quotes; revisit if real books run into the thousands.
