# Quote contest — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-02 | REFINE | What does a non-confirmed `user` see of the contest? | Nothing — the activity is hidden from them entirely, via Calendar's `role_restrictions`. | — |
| 2 | 2026-08-02 | REFINE | Is the submitter's identity visible during voting? | Anonymous to everyone but admins and moderators. | — |
| 3 | 2026-08-02 | REFINE | Can a user vote for a quote they submitted themselves? | Yes, allowed. Blocking it would only hint at their own entry. | — |
| 4 | 2026-08-02 | REFINE | What happens to a submitted entry when the quote, story or chapter changes? | Snapshot at submission, with a privacy escape hatch: the entry is withdrawn and its votes dropped if the story turns private or becomes excluded from events. Everything else (quote edited, quote deleted, chapter deleted) leaves the entry untouched. | — |
| 5 | 2026-08-02 | REFINE | Can the admin change categories once submissions are open? | Add and edit freely at any time; delete only while the category holds no entry. | — |
| 6 | 2026-08-02 | REFINE | Do voters see vote counts during the voting period? | No — hidden from readers throughout. Counts live only in the moderator/admin *Résultats* tab. | — |
| 7 | 2026-08-02 | REFINE | What happens to the entries and votes of a deactivated or deleted user? | Both stay. Entries are anonymous to readers anyway; tallies stay stable. | — |
| 8 | 2026-08-02 | REFINE | Which of the reader's quotes appear on the submission screen? | All of them; ineligible ones greyed with the reason (*histoire privée*, *histoire exclue des événements*). | — |
| 9 | 2026-08-02 | REFINE | The lifecycle notifications overlap the `calendar-notifications/` backlog row. Where do they live? | Contest-owned now, driven by the contest's own dates. `calendar-notifications/` stays on the backlog and generalises later from this example. | — |
| 10 | 2026-08-02 | REFINE | Who receives the broadcast notifications, and which ones? | Confirmed users only. Four broadcasts: submissions open, 24 h before submissions close, voting opens, 24 h before voting closes. All link to the activity page. | — |
| 11 | 2026-08-02 | REFINE | Is moderation deletion of an entry silent? | No — the submitter is notified that their entry in category X was removed and the slot is free again. | — |
| 12 | 2026-08-02 | REFINE | Can a reader withdraw an entry without replacing it? | Yes, freely, until submissions close. | — |
| 13 | 2026-08-02 | REFINE | May two readers enter the same passage in the same category? | Yes — allowed; it is signal, and moderation arbitrates via entry deletion. | — |
| 14 | 2026-08-02 | REFINE | What do users see between the close of submissions and the opening of votes? | The submission view, read-only, with their entries visible and a countdown to the vote. | — |
| 15 | 2026-08-02 | REFINE | Where does the QuoteContest activity live, and does it need a new tab mechanism? | In Calendar, as a sub-activity under `Private/Activities/`, following SecretGift exactly: one page, one component, internal tabs via `<x-shared::tabs>`. No new mechanism. | — |
| 16 | 2026-08-02 | DESIGN | How does the admin configure the contest — a contest-owned admin page, or wire the dead `configComponentKey()` hook for real? | Wire it for real: a dedicated config sub-view inside the generic activity create/edit page. This was missed when the first activities were built, for lack of time, and the contest is the right occasion to do it properly. `ActivityRegistrationInterface` gains `configRules()` and `persistConfig()`; the activity write and the plugin config save in one transaction. | — |
| 17 | 2026-08-02 | DESIGN | Where does the contest description live? | Reuse `calendar_activities.description` — it already exists, is rich text, and is already rendered above the plugin component. No second description field. | — |
| 18 | 2026-08-02 | DESIGN | How is an entry withdrawn when its story loses eligibility — hard delete, or a soft flag? | A `withdrawn_at` soft flag. Votes stay in the table but are excluded from every count and every listing. Refines decision #4: votes are dropped from the *count*, not from the database, so an accidental visibility toggle is recoverable. | refines #4 |
| 19 | 2026-08-02 | DESIGN | Are the authors of a snapshotted entry frozen by name, or resolved live? | Store `author_user_ids` on the entry; resolve display names live via `ProfilePublicApi`, as every other surface does. A renamed author shows their current name; a deleted one is omitted and the entry stands. | — |
| 20 | 2026-08-02 | DESIGN | Cache the rendered vote screen (the user's own parked note #5)? | No cache. Because entries are full snapshots the listing is already one indexed SELECT plus a batched profile lookup — there is no cross-domain read left to save, and a cache would cost three invalidation paths to protect a query that is not slow. | supersedes note #5 |
| 21 | 2026-08-02 | DESIGN | The *Mes citations* picker on a long quote book (open question #1)? | Load the whole quote book and filter client-side with Alpine. One query, instant picking, no round trips. Revisit if real books run into the thousands. | resolves OQ#1 |
| 22 | 2026-08-02 | DESIGN | In what order does a voter see a category's entries (open question #2)? | Shuffled, seeded on (reader, category). No positional advantage for early submitters, and the order is identical on every reload so the entry a reader was considering never moves. | resolves OQ#2 |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

Replayed to the user at the end of REFINE and not vetoed.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | The reader's private *note* on a quote never enters the contest and is shown nowhere, including the *Résultats* tab. | REFINE | Yes — additive. |
| A2 | "Not private" means the quote's story visibility is `public` or `community` **and** `is_excluded_from_events` is false. | REFINE | Yes. |
| A3 | A category's description is plain text, not rich text. | REFINE | Costly — changes storage and rendering. |
| A4 | The admin form enforces `début activité ≤ fin soumissions ≤ début votes ≤ fin activité`. | REFINE | Yes. |
| A5 | Access uses Calendar's existing `role_restrictions` (`user-confirmed`, `moderator`, `admin`) rather than per-action gating. | REFINE | Yes. |
| A6 | Exactly three tabs: *Mes citations*, *Votes*, *Résultats* (moderator/admin only). No fourth screen. | REFINE | Yes — additive. |
| A7 | Before the activity starts, confirmed users see description + categories, read-only. | REFINE | Yes. |
| A8 | No minimum entry count for a category to be votable; an empty category shows empty. Nothing marks or stores a winner. | REFINE | Yes. |
| A9 | Replacing or withdrawing an entry drops any votes on it. | REFINE | Yes. |
| A10 | No abstention is recorded — "has not voted" and "chose not to vote" are the same state. | REFINE | Yes. |
| A11 | The type config panel renders after the *Dates* section, just above the save button, and receives `:activity` (null on create) as its only prop. | BUILD phase 1 | Yes. |
| A12 | `getOwnedQuote()` builds its DTO through the same `buildProfileItems()` path as `getAllForOwner()`, so a single owned quote carries story/chapter titles and author profiles (not the bare chapter-shaped DTO). The submission snapshot in phase 6 can therefore read titles from it; slugs still come from `StoryPublicApi` (open item O1, preferred path). | BUILD phase 2 | Yes. |
| A13 | An instant landing exactly on a phase boundary belongs to the **later** phase (`active_starts_at` → Submissions, `submissions_end_at` → Interlude, `votes_start_at` → Voting, `active_ends_at` → Ended). Chosen to match `Activity::state`, which reads the same two activity dates the same way. | BUILD phase 3 | Yes — one comparison operator per boundary. |
| A14 | `QuoteContestPhaseService::phaseFor()` takes the settings as a **required** argument, not a nullable one: both settings columns are `NOT NULL` and phase 4 makes the row mandatory at activity creation, so "a contest without settings" is not a state to define a phase for. | BUILD phase 3 | Yes. |
| A15 | Null activity dates: a null `active_starts_at` means the contest has not begun, so it stays `BeforeStart` even once `active_ends_at` has gone by; a null `active_ends_at` means it never reaches `Ended`. Same reading as `Activity::state`. | BUILD phase 3 | Yes. |
| A16 | The category routes use the project's `role:admin,tech-admin` middleware like every other admin route, so a non-admin is **redirected to the dashboard**, not served a 403. Plan phase 4 asked for 403; no admin route in this codebase 403s and `CheckRole` has no such mode. The denial itself is enforced and tested (confirmed user and moderator, all three routes, database unchanged). | BUILD phase 4 | Yes — a 403 would mean a new middleware mode, applied app-wide for consistency. |
| A17 | The category editor is **pushed to an `activity-config-extras` stack** that `create.blade.php` / `edit.blade.php` render *after* `</form>`. The config panel itself stays inside the activity form (A11) for the two dates. Reason: each category row needs its own `<form>`, and nested forms are illegal HTML — browsers drop the inner one and its buttons would submit the activity instead. | BUILD phase 4 | Yes — the alternative is a separate admin page for categories. |
| A18 | Reordering categories goes through the **edit** route's optional `position` field; there is no dedicated reorder route, since §3.5 lists exactly three category routes. | BUILD phase 4 | Yes — additive. |
| A19 | `configRules()` has no companion `configMessages()` hook, so the date-ordering rules are `ValidationRule` objects (`Support/DateOrderRule`) carrying their own French message. `required` reuses the project's existing `validation.required` translation, and `bail` keeps Laravel's untranslated `validation.date` message off the screen — the rule object reports an unparseable date itself. | BUILD phase 4 | Yes — a third interface method would be the alternative. |
| A20 | A quote whose story no longer resolves (the story row is gone; `quotes` has no FK to `stories`) reports the **`private_story`** reason rather than gaining a third reason key. Nobody can read that story any more, which is what the reason says, and §3.2's reason set stays the two it names. | BUILD phase 5 | Yes — a third key is additive. |
| A21 | A contest activity whose settings row is missing renders as `BeforeStart`. Phase 4 makes the row mandatory at creation, so this is a guard rather than a supported state; the reading matches A15 (a null start date means "not begun"). | BUILD phase 5 | Yes. |
| A22 | The picker is built **only** during the `Submissions` phase. In `BeforeStart` / `Interlude` / `Voting` / `Ended` the tab renders the categories and the reader's own entries, and the quote book is not read at all — one fewer cross-domain read on every read-only view. | BUILD phase 5 | Yes. |
| A23 | Tab keys are the URL-hash fragments: `my-quotes` now, `votes` and `results` when phases 8 and 9 add them — so §4.7's deep link to `#votes` lands as specified. | BUILD phase 5 | Yes. |
| A24 | Open item **O1** is resolved the preferred way: the snapshot resolves `story_slug` / `chapter_slug` from `StoryPublicApi::getStoriesByIds()` / `getChaptersByIds()`, and `author_user_ids` from `getAuthorIdsByStoryIds()` — not from `QuoteDto::$authorProfiles`, which holds Profile DTOs rather than ids and only for authors who have a profile. Three batched single-id reads at submit time; `QuoteDto` gains no field. | BUILD phase 6 | Yes. |
| A25 | A quote whose **chapter row no longer resolves** is refused at submission. `chapter_title` and `chapter_slug` are `NOT NULL`, and an entry whose link is dead the moment it is written is not worth creating. This is a guard, not a user-facing flow: the picker cannot show such a quote as eligible unless the chapter vanishes between page load and POST. | BUILD phase 6 | Yes — the alternative is storing empty strings. |
| A26 | A forged `category_id` naming a category of **another** contest is a 403, like every other refusal of this phase, rather than a 404. All of §3.3's guards are "the UI never offered this, so the request is forged", and one status for all of them keeps the surface uninformative. | BUILD phase 6 | Yes. |
| A27 | `withdraw()` filters on `withdrawn_at IS NULL`: a submitter cannot hard-delete an entry that was **privacy**-withdrawn. §2.3 makes a withdrawn row the evidence that forbids deleting its category, and the UI never offers the action (withdrawn entries are absent from `currentEntriesFor()`). | BUILD phase 6 | Yes. |
| A28 | The submit / replace buttons are **shown** by Alpine once a quote is picked (`x-show`) rather than rendered disabled. A disabled `<x-shared::button>` would fight the component's own `SubmitBtn()` scope, which sets `disabled` itself on submit. Without JS the form still posts and the form request answers with the French *choisissez une citation* error. | BUILD phase 6 | Yes. |
