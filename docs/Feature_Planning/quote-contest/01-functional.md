# Quote contest — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

**Concours de citations** is a new Calendar activity type. Confirmed readers pick
passages from their own quote book and enter them into admin-defined categories;
once submissions close, everyone confirmed votes for one quote per category.
The value is twofold: it surfaces the best passages the community has collected,
and it drives readers towards stories they had not met — each quote carries a
link back to its story and chapter.

It follows the SecretGift shape exactly: one activity type, one Blade component,
one page, internal tabs via `<x-shared::tabs>`.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| **Concours de citations** | The activity type. One contest = one Calendar activity. |
| **Catégorie** | An admin-defined bucket for submissions: a *titre* and a short *description*. A contest has one or more. |
| **Soumission** (entry) | One reader's quote entered into one category. Anonymous to other readers. |
| **Vote** | One reader's single ballot inside one category. |
| **Période de soumission** | From the activity start to *fin des soumissions*. |
| **Période de vote** | From *début des votes* to the activity end. |
| **Entre-deux** | The optional gap between *fin des soumissions* and *début des votes*. |

User-facing wording is French. Tab labels: **Mes citations**, **Votes**,
**Résultats**.

## 3. Roles & visibility

Access to the activity as a whole is gated by Calendar's existing
`role_restrictions`, set to `user-confirmed` + `moderator` + `admin`.

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Nothing — the activity is behind `auth`. | — |
| `user` (non-confirmed) | **Nothing.** The activity is not listed and its page is refused. | — |
| `user-confirmed` | Description, categories, the *Mes citations* tab, the *Votes* tab. Never a vote count, never a submitter's name, never a result. | Submit / replace / withdraw one quote per category during the submission period. Cast and change one vote per category during the vote period. |
| Author / co-author of a quoted story | Exactly what any `user-confirmed` sees. Being quoted grants no extra visibility and no veto. | — |
| Moderator | Everything a confirmed user sees, **plus** the permanent *Résultats* tab: every category, every entry, its vote count, and **who submitted it**. | Delete any entry, at any point in the contest's life. |
| Admin | Same as moderator, plus the activity's admin configuration (description, categories, dates). | Create/configure the contest; same entry deletion. |

The *Résultats* tab is permanent for moderators and admins — before, during and
after the contest. It is never rendered for anyone else.

## 4. Functional requirements

### 4.1 Configuring the contest (admin)

1. An admin creates a Calendar activity and picks the type **Concours de citations**.
2. Beyond the generic activity fields, the contest configuration holds:
   - a **description** of the contest;
   - a list of **catégories**, each with a *titre* and a short *description* (plain text — assumption A3);
   - **fin des soumissions** (datetime);
   - **début des votes** (datetime).
3. The form also displays two **greyed, read-only** fields for orientation:
   *début des soumissions* = the activity's start, and *fin des votes* = the
   activity's end. They are not editable here.
4. Date rule, enforced by the form:
   `début activité ≤ fin soumissions ≤ début votes ≤ fin activité`.
   A violation is a validation error, in French.
5. Categories may be **added and edited at any time**, including after
   submissions open. A category may be **deleted only while it holds no entry**;
   a category with entries refuses deletion with an explanatory message.
6. A contest with zero categories is a valid draft but has nothing to submit to;
   the *Mes citations* tab says so.

### 4.2 Before the contest starts (preview)

1. Confirmed users can open the activity and read the description and the
   categories with their descriptions.
2. No submission is possible. The screen states when submissions open.

### 4.3 Submitting a quote

Active from the activity start until *fin des soumissions*.

1. All confirmed users receive a notification when submissions open, linking to
   the activity page.
2. The **Mes citations** tab lists the reader's own quotes. This list belongs to
   the activity — it is *not* the profile quote-book tab and does not reuse its
   screen.
3. **Every** quote the reader owns is listed. Ineligible ones are shown **greyed,
   with the reason**:
   - *histoire privée* — the quote's story is not `public` or `community`;
   - *histoire exclue des événements* — the story carries `is_excluded_from_events`.
   An ineligible quote cannot be selected.
4. Alongside, the reader sees the list of **catégories** with their descriptions,
   the **date de fin des soumissions**, and, for each category, the quote they
   have currently entered (if any).
5. The reader selects an eligible quote and assigns it to a category.
6. **One quote per category.** If the category is already filled, the system
   shows the reader which quote is already there and offers to **replace** it.
   Replacing discards the previous entry.
7. **The same quote may be entered in several categories.**
8. The reader may **withdraw** an entry at any time before submissions close,
   leaving the category slot empty, without having to replace it.
9. Two different readers may enter the **same passage** in the same category.
   The system does not prevent it; moderation arbitrates (§4.6).
10. Submissions are **anonymous**: nothing in any reader-facing screen states who
    entered a quote.
11. On submission the entry **snapshots** the quote: the passage text and the
    story / chapter / author references as they stand at that moment.
    From then on, edits or deletion of the source quote do not affect the entry.
12. The reader's private **note** on the quote is never carried into the entry
    and is never displayed anywhere in the contest, including the *Résultats* tab
    (assumption A1).
13. 24 hours before *fin des soumissions*, all confirmed users receive a reminder.

### 4.4 The interlude

From *fin des soumissions* to *début des votes*:

1. The *Mes citations* tab stays reachable and becomes **read-only**. The
   reader's entries remain visible.
2. The screen shows a countdown / the date at which voting opens.
3. No *Votes* tab content yet.

If the admin set the two dates equal, this state simply never occurs.

### 4.5 Voting

Active from *début des votes* to the activity end.

1. All confirmed users receive a notification when voting opens, linking to the
   activity page.
2. The **Votes** tab lists all categories, each marked clearly as *vous avez
   voté* / *vous n'avez pas voté*.
3. Opening a category shows **all** its entries. Each entry displays:
   - the quoted passage;
   - the **titre de l'histoire**, as a link;
   - the **titre du chapitre**, as a link;
   - the **noms des auteurs**.
4. **No vote count is shown to readers**, in any category, at any time.
5. A reader casts **one vote per category** and may **change it** freely until
   the vote period ends. There is no abstention record (assumption A10).
6. A reader **may vote for their own entry**. Nothing indicates which entry is
   theirs; the anonymity of §4.3.10 is not broken to enforce a rule.
7. 24 hours before the activity ends, all confirmed users receive a reminder.
8. When the activity ends, the *Votes* tab becomes **read-only**. Readers see
   their own vote, and **never see the results** — not at the end, not later.

### 4.6 Moderation and results (moderator / admin)

1. The **Résultats** tab is available to moderators and admins permanently.
2. For each category it lists every entry with:
   - the quoted passage and its story / chapter / author links;
   - its **vote count**;
   - **who submitted it**.
3. A moderator or admin may **delete an entry** — typically a duplicate. Deleting
   an entry **drops the votes cast on it**.
4. The submitter is **notified** that their entry in category *X* was removed by
   moderation and that the slot is free again. If submissions are still open they
   may enter another quote there.
5. Winners are announced **by hand, in a News article**. Nothing in the app marks
   or stores a winner (assumption A8).

### 4.7 Notifications

All four broadcasts go to **confirmed users only** — the activity is invisible to
non-confirmed users, so a link would be dead for them. All carry a link to the
activity page.

| Trigger | Audience |
|---------|----------|
| Submissions open (activity start) | all confirmed users |
| 24 h before *fin des soumissions* | all confirmed users |
| Voting opens (*début des votes*) | all confirmed users |
| 24 h before the activity ends | all confirmed users |
| An entry is deleted by moderation | the submitter only |

These are owned by the contest and driven by the contest's own dates. The generic
Calendar mechanism stays on the backlog as `calendar-notifications/` (decision #8).

## 5. Lifecycle

| Event | Effect |
|-------|--------|
| The reader **edits** the quote's text or note after submitting | No effect. The entry holds a snapshot. |
| The reader **deletes** the quote from their book after submitting | No effect. The entry stands, with its votes. |
| The quote's story **turns private** (no longer `public`/`community`) | The entry is **withdrawn** from the contest and its votes are dropped. The passage must not stay on display once the author has made the story private. |
| The quote's story becomes **excluded from events** | Same: entry withdrawn, votes dropped. |
| The **chapter is deleted or unpublished** | The entry stands (snapshot). The chapter link may lead nowhere — same behaviour readers already meet in the quote book. |
| The **submitter is deactivated** | Entries and votes both **stay**. Entries are anonymous to readers anyway; the *Résultats* tab still shows the submitter's name and profile link — deactivation alone does not anonymize them, only account deletion does (verified in VERIFY). |
| The **submitter is deleted** | Entries and votes both **stay**. The *Résultats* tab shows the entry with no identifiable submitter ("Compte supprimé"). |
| A **voter** is deactivated or deleted | Their ballots stay counted. |
| An **entry is deleted by moderation** | Its votes are dropped; the submitter is notified; the category slot frees up. |
| An entry is **replaced or withdrawn** by its submitter | The previous entry and any votes on it are dropped. Only reachable during the submission period, when no vote exists yet — except via the privacy withdrawal above. |
| A **category** is deleted | Only possible while empty, so nothing cascades. |
| The **activity** is deleted or archived | Its categories, entries and votes go with it. |

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Whole activity gated to `user-confirmed`, `moderator`, `admin` via Calendar's `role_restrictions`. Non-confirmed `user` sees nothing at all (decision #1). *Résultats* tab rendered for `moderator`/`admin` only. |
| Visibility / privacy | Eligibility = story is `public` or `community` **and** not `is_excluded_from_events` (assumption A2). Submissions anonymous to readers, identified to moderation. Vote counts and results never shown to readers. The quote's private note never leaves the Quote domain. Losing story eligibility withdraws the entry mid-contest (decision #4). |
| Settings | N/A — no new user preference. The Quote domain's `hide-quotes-tab` setting governs the *profile* quote book only and has no bearing here: the contest reads the reader's own quotes on their own screen, and entries are anonymous. |
| Notifications | Five, per §4.7. Four scheduled broadcasts to confirmed users, one targeted on moderation deletion (decisions #8, #9, #10). |
| Domain events | Consumed: the contest must react to a story losing eligibility (`StoryExcludedFromEvents` and the equivalent visibility change) to withdraw entries. Emitted: none required by this spec. |
| Statistics | N/A for this version — no contest metric is added to the Statistics domain. |
| Moderation | Handled in-feature by the *Résultats* tab (delete an entry, notify the submitter). Not routed through the Moderation domain's report registry — there is no user-facing "signaler cette citation" flow here. Reporting quotes remains the separate `quotes-moderation/` backlog row. |
| Lifecycle / cascade | Per §5. Snapshot semantics with one privacy escape hatch. |
| Media | N/A — entries are text only. The activity's own image is the generic Calendar activity image. |
| Search | N/A — contest entries are not indexed by the Search domain. |
| i18n | All strings French, in the activity's own lang namespace, following SecretGift/Jardino. |
| Mobile | The three tabs and the category → entries drill-down must work on a narrow viewport. Quote cards stack; story/chapter links stay tappable. |
| Accessibility | Vote selection must be operable by keyboard and expose its state to a screen reader (a radio group per category is the natural shape). Greyed-out ineligible quotes must carry their reason as text, not colour alone. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | What does a non-confirmed `user` see? | Nothing — the activity is hidden from them entirely. |
| 2 | Is the submitter's identity visible during voting? | Anonymous to everyone but admins and moderators. |
| 3 | Can a user vote for their own entry? | Yes, allowed. |
| 4 | What happens to an entry when the quote or its story changes? | Snapshot at submission, with a privacy escape hatch: the entry is withdrawn (votes dropped) if the story turns private or becomes excluded from events. |
| 5 | Can the admin change categories after submissions open? | Add and edit freely; delete only if the category is empty. |
| 6 | Do voters see live vote counts? | No — hidden from users throughout, before, during and after. |
| 7 | What happens to entries and votes of a deactivated/deleted user? | Both stay. A deactivated submitter's name and profile link still show in *Résultats* — only a deleted account anonymizes to "Compte supprimé". |
| 8 | Which quotes appear on the submission screen? | All of the reader's quotes; ineligible ones greyed with the reason. |
| 9 | Where do the lifecycle notifications live? | Contest-owned now, driven by the contest's own dates. `calendar-notifications/` generalises later. |
| 10 | Who receives the broadcast notifications? | Confirmed users only. |
| 11 | Is moderation deletion of an entry silent? | No — the submitter is notified that the slot is free again. |
| 12 | Can a reader withdraw an entry without replacing it? | Yes, freely, until submissions close. |
| 13 | May two readers enter the same passage in one category? | Yes — allowed; moderation arbitrates. |
| 14 | What do users see between the close of submissions and the opening of votes? | The submission view, read-only, with a countdown to the vote. |

## 8. Out of scope

- **No results screen for readers.** Not at the end, not after archiving. Winners
  are announced by hand in a News article.
- **No generic Calendar lifecycle-notification mechanism.** That is
  `calendar-notifications/`; this contest ships its own (decision #9).
- **No enrolment or participant cap.** The contest uses no subscription flow;
  `requires_subscription` / `max_participants` stay unused. That is
  `calendar-subscription/`.
- **No winner entity.** Nothing stores, marks or displays a winner.
- **No reporting flow on entries.** No "signaler cette citation" — that is
  `quotes-moderation/`.
- **No statistics.** No contest metric feeds the Statistics domain.
- **No editing of the quoted passage.** Neither the reader nor moderation may
  alter the snapshotted text; moderation's only lever is deletion.
- **No multiple votes, weighted votes, or ranked ballots.** One vote per category.
- **No comments on entries.**
- **No export of results.** Moderators read the *Résultats* tab on screen.

## 9. Open questions

- **Non-blocking** — the reader's quote book may be long. The *Mes citations* tab
  will need pagination or search; the exact affordance is a DESIGN/BUILD call, not
  a functional one.
- **Non-blocking** — a category with many entries makes a long vote screen.
  Whether entries are paginated, and in what order they are listed (random per
  reader? stable?), is unresolved. Ordering is worth a deliberate choice, since a
  stable order advantages the top of the list.

## Notes parked for DESIGN

Not requirements — findings from the REFINE research that DESIGN must resolve.

1. **Tabs are a settled pattern.** `<x-shared::tabs>` (Alpine, client-side,
   `tracking`) is what SecretGift uses inside its single
   `displayComponentKey()` component. The contest follows it; the *Résultats* tab
   is simply omitted server-side from the tabs array for non-moderators. No new
   Calendar tab mechanism is needed.
2. **Admin configuration has no precedent.** `ActivityRegistrationInterface::configComponentKey()`
   exists but is **dead code** — nothing renders it. Both built-in activities
   return `null`. The contest needs a real admin UI for description, categories
   and the two dates. DESIGN chooses: wire `configComponentKey()` for the first
   time (touches `ActivityController` and the admin `_form.blade.php`), or give
   the contest its own admin routes as Jardino/SecretGift do for their own
   endpoints.
3. **Story eligibility is not readable.** `is_excluded_from_events` lives on
   `stories` but is not on `StoryPublicApi` / `StorySummaryDto`; it only travels
   on domain events via `StorySnapshot`. Per-story visibility likewise needs a
   read path. A new `StoryPublicApi` accessor is required.
4. **deptrac forbids Calendar → Quote.** `CalendarPrivate`'s ruleset must gain
   `QuotePublic`. `QuotePublicApi` also has no contest-shaped read — nothing
   returns "this user's quotes, for picking" outside the paginated profile shape,
   nor a single quote by id for a given owner. New public API methods are likely.
5. **Caching the vote screen** — the user's own suggestion. Since entries are
   already snapshots, the vote screen reads no live Quote data; caching the
   rendered category listing for the vote period is cheap and safe. Vote counts on
   the *Résultats* tab must **not** be cached. Moderation deletion and the privacy
   withdrawal must invalidate the cache.
6. **Scheduling.** The four broadcast notifications are date-triggered. The app
   already runs a scheduler (`bootstrap/app.php`), with
   `story:publish-scheduled-chapters` on a 5-minute cron as the nearest precedent.
   The 24 h reminders need idempotence — fire once, not once per tick.
