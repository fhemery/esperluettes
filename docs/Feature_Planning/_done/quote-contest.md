# Quote contest — *Concours de citations*

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-08-03 · **Domain(s):** `Calendar` (+ read-only additions
to `Quote`, `Story`)

**The living documentation is
[`app/Domains/Calendar/Private/Activities/QuoteContest/README.md`](../../../app/Domains/Calendar/Private/Activities/QuoteContest/README.md)**
— tables, phases, screens, notifications, invariants. This file records only
what that one cannot: what was decided, what was cut, what is still open.

## What it does

A third Calendar activity type (`quote-contest`). Confirmed readers enter
passages from their own quote book into admin-defined categories — one entry per
category, replaceable until submissions close — then vote once per category,
anonymously. Moderators get a *Résultats* tab with vote counts and submitter
names, and may delete an entry (notifying its submitter). Nothing is ever shown
to readers: no counts, no winner, no results screen. Four date-driven broadcasts
plus one targeted notification carry the lifecycle.

It is also the first activity type to use the **config half** of
`ActivityRegistrationInterface`, which this task built: `configComponentKey()` +
`configRules()` + `persistConfig()`, run in the activity's own transaction.

## Key behaviour

- **Access is configuration, not code.** No code gates the contest page; the
  admin must set `role_restrictions` = `user-confirmed` + `moderator` + `admin`
  on the activity. Write routes re-check phase and ownership themselves.
- **Phase comes from `QuoteContestPhaseService::phaseFor()` and nowhere else.**
  Four datetimes, boundary instants belong to the *later* phase.
- **An entry is a snapshot**; `quote_id` is provenance only and is never
  dereferenced. Editing/deleting the source quote or chapter does nothing.
- **`withdrawn_at` is a filter, never a deletion** — stamped when the quoted
  story turns private or is excluded from events, never cleared. Every read must
  filter on it; there is no index expressing the rule.
- **Anonymity is a query shape**: submitter + count exist only in
  `Results*ViewModel`, built only by `QuoteContestVoteService::resultsFor()`.
  The *Résultats* tab is absent from the tabs array for non-moderators.
- **Notification idempotence is a column** (`notified_*_at`), stamped in the
  send's transaction — so a backdated contest fires all four on the next tick,
  and moving a date past a stamped moment re-fires nothing.

## Where the code lives

| Concern | Path |
|---------|------|
| The whole feature | `app/Domains/Calendar/Private/Activities/QuoteContest/` |
| Plugin contract (new) | `Calendar/Public/Api/ActivityRegistrationInterface.php`, `Private/Controllers/Admin/ActivityController.php`, `Private/Requests/Admin/ActivityRequest.php` |
| Cross-domain reads (new) | `Quote/Public/Api/QuotePublicApi::getAllForOwner()` / `getOwnedQuote()`; `Story` `StorySummaryDto::$is_excluded_from_events` |
| Routes | `…/QuoteContest/Http/routes.php` — admin category CRUD, 3 reader writes, 1 moderation DELETE. No results route (A34). |
| Schedule | `bootstrap/app.php` → `calendar:quote-contest-notify`, every 5 min |
| Tests | `app/Domains/Calendar/Tests/Feature/QuoteContest/` (+ `Unit/QuoteContest/`), `Tests/Feature/Admin/ActivityPluginConfigTest.php`, `Quote/Tests/Feature/QuotePublicApiOwnerReadsTest.php` |
| Migrations | 4 tables, `…/QuoteContest/Database/Migrations/2026_08_02_1000*` |

## Extension points used

- **Calendar activity registry** — `QuoteContestRegistration`, type key `quote-contest`.
- **Notification registry** — new Calendar-wide `calendar` group (`sortOrder: 70`), 5 types; catalogued in `docs/notification-types.md`.
- **Events** — subscribes `Story::VisibilityChanged` / `Story::ExcludedFromEvents` as lazy closures (A29: eager resolution froze singletons at boot and broke unrelated tests).
- **Deptrac** — two new edges, `CalendarPrivate → QuotePublic` and `→ NotificationPublic`.

## Decisions worth remembering

- **#9** — the contest owns its own notifications; `calendar-notifications/` generalises later *from* this example rather than blocking it.
- **#16** — `configComponentKey()` was a dead hook; wiring it for real was in scope on purpose. Config panel lives in the activity form; a panel needing its own `<form>` pushes to the `activity-config-extras` stack (A17), since nested forms are illegal HTML.
- **#18** (refines #4) — soft `withdrawn_at`, votes kept in the table but excluded everywhere, so an accidental visibility toggle is recoverable.
- **#22** — vote order shuffled, seeded on (reader, category): no first-mover advantage, stable across reloads.
- **#23** (refines #7, taken at VERIFY) — a **deactivated** submitter keeps their name and profile link in *Résultats*; only a **deleted** account shows *Compte supprimé*.
- **A32** — moderation roles are `[MODERATOR, ADMIN, TECH_ADMIN]` on `QuoteContestModerationController::ROLES`; the view reads the same constant so tab and write cannot drift.

## Not done

**Deliberate non-goals**: no reader-visible results screen ever, no winner
entity, no enrolment or participant cap (`calendar-subscription/`), no reporting
on entries (`quotes-moderation/`), no statistics, no editing of a snapshotted
passage, no ranked or weighted ballots, no comments, no export. No abstention is
recorded; no minimum entry count makes a category votable.

**Known drift / accepted nits** (from VERIFY):

- A quote greyed *Histoire privée* in the picker still renders its story title as
  a link, and that link 404s. Cosmetic — the row already states the reason in
  words — and it is the pre-existing shape of the quote book, not contest code.
- The reader's whole quote book is loaded and filtered client-side (#21).
  Revisit past a few thousand quotes.

**e2e specs retired at WRAP** — `e2e/tests/features/quote-contest.spec.ts` and
its page objects `QuoteContestPage.ts` / `CalendarActivityFormPage.ts` are
**deleted**; nothing else imported them. The **seeding stays committed**:
`E2eCalendarSeeder` (five contests, one per phase — a phase comes from the
clock, so it cannot be fast-forwarded), the deactivated account in
`E2eAccountsSeeder`, the extra stories/quotes, and the `CONTESTS` / `CONTEST`
fixture blocks. They have no consumer right now, on purpose:
`calendar-notifications/` and `calendar-subscription/` will want exactly this
world, and seeding runs once at global setup.

**Pushed back to the backlog, since done:** [`shared-a11y`](./shared-a11y.md) —
`<x-shared::tabs>` panels carry no `role="tabpanel"` / `aria-labelledby`, and
`<x-shared::confirm-modal>` does not forward `focusable` (A40). One-line fixes,
but in components every domain renders.
