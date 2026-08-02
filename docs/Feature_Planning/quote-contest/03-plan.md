# Quote contest — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions log: [`DECISIONS.md`](./DECISIONS.md) — 22 decisions + 10 assumptions.
  Never re-open one; if a decision proves unimplementable, say so and stop.

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Calendar core — the plugin config contract | M | — | TODO |
| 2 | Cross-domain reads: Story eligibility flag + Quote owner reads | S | — | TODO |
| 3 | QuoteContest skeleton: tables, models, registration, phase service | M | 1 | TODO |
| 4 | Admin configuration: settings panel + category CRUD | M | 1, 3 | TODO |
| 5 | Reader page shell + *Mes citations*, read-only | M | 2, 3, 4 | TODO |
| 6 | Submissions: submit / replace / withdraw | M | 5 | TODO |
| 7 | Story-eligibility listeners: withdrawal on visibility loss | S | 6 | TODO |
| 8 | Voting: ballot, seeded shuffle, *Votes* tab | M | 6 | TODO |
| 9 | Moderation: *Résultats* tab, entry deletion, submitter notification | M | 8 | TODO |
| 10 | Scheduled broadcast notifications + Artisan command | M | 9 | TODO |
| 11 | Docs, i18n sweep, a11y polish | S | 10 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/11)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps the
  gate green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

### Gate — read this before phase 1

```bash
npm run gate > /tmp/gate.log 2>&1 && echo GATE_GREEN || tail -40 /tmp/gate.log
```

Never let a full gate or test run land in the transcript. Redirect and read only
the tail on failure, as above.

---

## Phase 1 — Calendar core: the plugin config contract

**Goal.** Make `ActivityRegistrationInterface::configComponentKey()` real by
adding `configRules()` and `persistConfig()`, rendering the config component on
the admin activity form, and saving activity + plugin config in one transaction —
with both built-in activity types unaffected.

This is a **core Calendar change with no QuoteContest code in it**. It is
isolated on purpose: if the two existing activity types regress, this is the
only commit to look at. Architecture §1.1 ("Calendar core — the plugin config
contract") is the section to read; nothing else of the architecture is needed.

**Deliverables.**

- `app/Domains/Calendar/Public/Api/ActivityRegistrationInterface.php` — two new
  methods, signatures exactly as §1.1 gives them:
  ```php
  /** Validation rules merged into ActivityRequest for this type. */
  public function configRules(): array;

  /** Persist the type's own config. Runs inside the activity's transaction. */
  public function persistConfig(int $activityId, array $validated): void;
  ```
- No-op implementations (`return []` / empty body) in **all four** current
  implementors — this list is complete, verified by grep:
  - `app/Domains/Calendar/Private/Activities/Jardino/JardinoRegistration.php`
  - `app/Domains/Calendar/Private/Activities/SecretGift/SecretGiftRegistration.php`
  - `app/Domains/Calendar/Tests/Support/TestActivityRegistration.php`
  - `FakeActivityRegistration` inside `app/Domains/Calendar/Tests/helpers.php`
- `app/Domains/Calendar/Private/Requests/Admin/ActivityRequest.php` — merge the
  selected type's `configRules()` into `rules()`. The type comes from
  `$this->input('activity_type')` on POST and from the route-bound
  `Activity::$activity_type` on PUT (the form does not resubmit the type on edit;
  see the `$isEdit` branch of `_form.blade.php`). An unknown/absent type
  contributes no rules — validation of the type itself already happens in
  `ValidatesActivityPayload`.
- `app/Domains/Calendar/Private/Controllers/Admin/ActivityController.php` —
  `store()` and `update()` wrap the `CalendarPublicApi` call **and** the
  registration's `persistConfig($activityId, $validated)` in one
  `DB::transaction()`. `create()` already returns the new activity id; on update
  the id is the route-bound activity's.
- `app/Domains/Calendar/Private/Resources/views/pages/admin/activities/_form.blade.php`
  — render `<x-dynamic-component>` for the selected type's
  `configComponentKey()` when it is non-null, passing `:activity="$activity"`
  (null on create). On **create**, the type is picked in the same form, so wrap
  the config panels in an Alpine `x-show` keyed on the `activity_type` select;
  on **edit** the type is fixed, so only that type's panel exists.
  `ActivityController::create()`/`edit()` pass the registry's config keys to the
  view alongside `$activityTypes`.
- `app/Domains/Calendar/AGENTS.md` — document the contract under "Registry
  Registrations": a type may now contribute validation rules and persist its own
  config inside the activity's transaction. Keep it to a short paragraph; do not
  reference `docs/Feature_Planning`.

**Tests.** New file
`app/Domains/Calendar/Tests/Feature/Admin/ActivityPluginConfigTest.php`, driven
by a purpose-built registration in `Tests/Support` (extend or parameterise
`TestActivityRegistration` so it can declare a rule and record a
`persistConfig()` call).

- `test_config_component_is_rendered_on_create_when_the_type_declares_one`
- `test_no_config_component_is_rendered_for_a_type_that_returns_null`
- `test_plugin_rules_are_enforced_on_store` — a payload violating the plugin rule
  gets a 422/redirect-with-errors on the plugin field, and **no activity row is
  created**.
- `test_persist_config_receives_the_new_activity_id_and_the_validated_payload`
- `test_persist_config_failure_rolls_back_the_activity` — a registration whose
  `persistConfig()` throws leaves zero activities in the table.
- `test_plugin_rules_are_enforced_on_update_using_the_stored_activity_type`
- Existing `ActivityControllerTest` / `ActivityImageTest` / the Jardino and
  SecretGift feature tests must pass untouched — that is the regression signal.

**Acceptance.**
- ✅ Creating a `jardino` or `secret-gift` activity through the admin form
  behaves exactly as before (no config panel, no extra rules, no extra query).
- ✅ A type declaring `configComponentKey()` gets its panel rendered on the
  create form and on its own edit form.
- ✅ A plugin validation failure leaves no `calendar_activities` row.
- ✅ A `persistConfig()` exception leaves no `calendar_activities` row.
- ✅ `npm run gate` green.

---

## Phase 2 — Cross-domain reads: Story eligibility flag + Quote owner reads

**Goal.** Add the two additive read paths the contest needs from the Story and
Quote domains, with no Calendar code yet. Architecture §1.1 ("Story — one
additive read", "Quote — one new read on the public API") is the section to read.

Eligibility (assumption A2) is *story visibility is `public` or `community`
**and** `is_excluded_from_events` is false*. Today `StorySummaryDto` carries
`visibility` but not the flag, which only travels on `StorySnapshot` inside
domain events.

**Deliverables.**

- `app/Domains/Story/Public/Contracts/StorySummaryDto.php` — add
  `public bool $is_excluded_from_events` to the constructor and populate it in
  `fromModel()` from the `stories` column. `fromModel()` is the **only**
  construction site in the codebase (verified by grep), so this is safe, but add
  the parameter at the end of the signature anyway.
- `app/Domains/Quote/Public/Api/QuotePublicApi.php` + the backing
  `App\Domains\Quote\Private\Services\QuoteService`:
  ```php
  /** Every quote this user owns, newest first, for the contest picker. */
  public function getAllForOwner(int $userId): QuoteListDto;

  /** One quote, only if $userId owns it; null otherwise. */
  public function getOwnedQuote(int $quoteId, int $userId): ?QuoteDto;
  ```
  `getAllForOwner()` returns the existing
  `App\Domains\Quote\Public\Api\Contracts\QuoteListDto` with `page: 1`,
  `viewerIsOwner: true`, `canQuote: false` and `totalCount` = the full count. No
  new DTO. Reuse the private `buildProfileItems($rows, isOwner: true, …)` helper
  — it already batches story/chapter/author metadata and is the no-N+1 path.
  `getOwnedQuote()` returns `null` (never throws) when the quote does not exist
  or belongs to someone else: it is the contest's authorization boundary.
- Neither method applies the `hide-quotes-tab` setting or any viewer filtering —
  the owner is the viewer.

**Tests.**

- `app/Domains/Story/Tests/Feature/…` — extend the existing
  `getStoriesByIds` / story-summary coverage with
  `test_story_summary_carries_is_excluded_from_events` (true and false cases).
- `app/Domains/Quote/Tests/Feature/QuotePublicApiOwnerReadsTest.php`:
  - `test_get_all_for_owner_returns_every_quote_newest_first`
  - `test_get_all_for_owner_returns_quotes_whose_story_is_private_or_unpublished`
    — the contest greys these out itself; the API must not hide them.
  - `test_get_all_for_owner_returns_an_empty_list_for_a_user_with_no_quotes`
  - `test_get_owned_quote_returns_the_quote_for_its_owner`
  - `test_get_owned_quote_returns_null_for_another_user`
  - `test_get_owned_quote_returns_null_for_an_unknown_id`

**Acceptance.**
- ✅ `StorySummaryDto::$is_excluded_from_events` is populated everywhere
  `getStoriesByIds()` / `getStory()` return DTOs.
- ✅ `getAllForOwner()` issues a bounded number of queries regardless of quote
  count (assert with `DB::listen` or the existing query-count helper if one
  exists; otherwise assert on a 20-quote fixture spanning 5 stories).
- ✅ `getOwnedQuote()` returns `null` — not an exception, not a 403 — for a
  quote the caller does not own.
- ✅ No deptrac change is needed by this phase (`QuotePublic → StoryPublic`
  already exists).
- ✅ `npm run gate` green.

---

## Phase 3 — QuoteContest skeleton: tables, models, registration, phase service

**Goal.** Stand up the sub-module: four tables, four models, the registration
(wired into `CalendarRegistry`), the service provider, the lang namespace, and
the phase enum with its service — with a still-empty display component. No
reader-visible behaviour yet.

Builds on phase 1, which added `configRules()` / `persistConfig()` to
`ActivityRegistrationInterface`; this phase's registration returns `[]` and an
empty body for those two — phase 4 fills them in.

Architecture sections to read: §2 (data model, all of it), §3.2 (the
`QuoteContestPhaseService` bullet only), §8 (file layout).

Model the sub-module on `Private/Activities/SecretGift/` — same provider shape
(`loadViewsFrom` / `loadTranslationsFrom` / `loadMigrationsFrom` /
`loadRoutesFrom` / `Blade::componentNamespace`), registered from
`CalendarServiceProvider::register()` and `::boot()` next to the other two.

**Deliverables.**

Under `app/Domains/Calendar/Private/Activities/QuoteContest/`:

- `Database/Migrations/2026_08_02_100000_create_calendar_quote_contest_settings_table.php`
- `Database/Migrations/2026_08_02_100001_create_calendar_quote_contest_categories_table.php`
- `Database/Migrations/2026_08_02_100002_create_calendar_quote_contest_entries_table.php`
- `Database/Migrations/2026_08_02_100003_create_calendar_quote_contest_votes_table.php`

  Columns, nullability, FKs and indexes exactly as architecture §2.1. Every
  migration has a `down()`. FKs point only at `calendar_activities` and at each
  other (same domain); `user_id` columns are plain `unsignedBigInteger` with no
  FK. Note §2.1's two deliberate non-constraints: **no** unique index on
  `(category_id, user_id)` for entries (a `NULL` `withdrawn_at` defeats it —
  service-level check instead), but **yes** a unique on `(category_id, user_id)`
  for votes.
- `Models/QuoteContestSettings.php`, `Models/QuoteContestCategory.php`,
  `Models/QuoteContestEntry.php`, `Models/QuoteContestVote.php` — PHP attribute
  syntax (`#[Table]`, `#[Fillable]`), `protected $casts` as a property for
  `author_user_ids => 'array'`, the four `notified_*_at` and `withdrawn_at` as
  `datetime`. Relations stay inside the sub-module (§2.2); none crosses a domain
  boundary.
- `Support/QuoteContestPhase.php` — the enum:
  `BeforeStart | Submissions | Interlude | Voting | Ended`.
- `Services/QuoteContestPhaseService.php` — **the single source of truth** for
  what phase a contest is in, derived from the activity's `active_starts_at` /
  `active_ends_at` and the settings' `submissions_end_at` / `votes_start_at`.
  Nothing else in the feature may recompute a phase from raw dates.
  Handle the boundary cases explicitly: `submissions_end_at == votes_start_at`
  means `Interlude` never occurs; null activity dates; `now()` exactly on a
  boundary (pick a convention and table-test it).
- `QuoteContestRegistration.php` — `ACTIVITY_TYPE = 'quote-contest'`,
  `displayComponentKey()` → `'quote-contest::quote-contest-component'`,
  `configComponentKey()` → `'quote-contest::quote-contest-config'`,
  `configRules()` → `[]` and an empty `persistConfig()` **for this phase only**.
- `QuoteContestServiceProvider.php` — views (`quote-contest` namespace), lang,
  migrations, Blade component namespace. Routes, listeners and the command are
  added by later phases.
- `Resources/lang/fr/quote-contest.php` — the namespace with the activity type
  label and the tab labels (*Mes citations*, *Votes*, *Résultats*).
- `Resources/views/components/quote-contest.blade.php` — placeholder, renders
  nothing but the container.
- `Resources/views/components/quote-contest-config.blade.php` — placeholder.
- `View/Components/QuoteContestComponent.php` — placeholder that renders the
  above with `$activity`.
- `app/Domains/Calendar/Public/Providers/CalendarServiceProvider.php` — register
  `QuoteContestServiceProvider` and `QuoteContestRegistration::ACTIVITY_TYPE`.
- `app/Domains/Calendar/Private/Resources/lang/fr/activities.php` — the
  `quote-contest` type label, so the admin type dropdown shows French.
- `app/Domains/Calendar/Tests/Feature/QuoteContest/helpers.php`, required from
  `app/Domains/Calendar/Tests/helpers.php` next to the Jardino and SecretGift
  requires. Provide at minimum:
  `createQuoteContest(TestCase $t, array $overrides = [], array $settings = [])`
  returning `{ id, url, activity, settings }`, and phase shortcuts
  (`createContestInSubmissions`, `…InInterlude`, `…InVoting`, `…Ended`,
  `…BeforeStart`) built by moving the dates, **not** by stubbing the phase
  service — architecture §6 requires phase tests to travel the clock.

**Tests.**

- `app/Domains/Calendar/Tests/Unit/QuoteContest/QuoteContestPhaseServiceTest.php`
  — a table test over every boundary: before start, on start, mid-submissions,
  on `submissions_end_at`, in the interlude, on `votes_start_at`, mid-voting, on
  `active_ends_at`, after. Plus `submissions_end_at == votes_start_at` (no
  interlude) and the null-date cases. This is one of only two unit tests the
  feature gets (§6).
- `app/Domains/Calendar/Tests/Feature/QuoteContest/QuoteContestSetupTest.php`:
  - `test_the_quote_contest_type_is_registered_in_the_calendar_registry`
  - `test_an_admin_can_create_a_quote_contest_activity`
  - `test_deleting_the_activity_cascades_settings_categories_entries_and_votes`
    — insert one of each directly through the models, delete the activity, assert
    all four tables are empty. This is the §5 "activity deleted" row and the only
    place the FK cascade is proven.

**Acceptance.**
- ✅ `php artisan migrate` and `migrate:rollback` both run clean.
- ✅ `quote-contest` appears in the admin activity-type dropdown with a French
  label.
- ✅ The phase service returns the right enum value at all ten boundaries.
- ✅ Deleting the activity leaves no orphan row in any of the four tables.
- ✅ `npm run gate` green.

---

## Phase 4 — Admin configuration: settings panel + category CRUD

**Goal.** The admin can configure a contest: the two dates ride along with the
activity form through the phase-1 contract, and categories are managed by their
own three routes.

Builds on phase 1 (`configRules()` / `persistConfig()` are called by
`ActivityRequest` and `ActivityController` inside one transaction) and phase 3
(tables, models, registration, provider, lang namespace).

Architecture sections to read: §1.1 ("Calendar core"), §3.2 (the
`QuoteContestConfigService` bullet), §3.5 (routes and form requests), §4 (the
config panel is Blade-first), §2.3 (the category-deletion rule).

Functional rules this phase owns: §4.1 of the spec, and decision #5 — categories
may be **added and edited at any time**, deleted **only while they hold zero
entries** (withdrawn entries count as entries; a withdrawn entry is still
evidence, per §2.3).

**Deliverables.**

- `Services/QuoteContestConfigService.php` — create/update the settings row,
  category CRUD, and the "category must be empty to delete" refusal carrying a
  French message. Controllers never touch a model.
- `QuoteContestRegistration.php` — real `configRules()` and `persistConfig()`:
  - rules for `quote_contest[submissions_end_at]` and
    `quote_contest[votes_start_at]` (both `required`, `date`), plus the ordering
    rule of assumption A4: `active_starts_at ≤ submissions_end_at ≤
    votes_start_at ≤ active_ends_at`, with **French messages** so a violation
    renders as a field error on the activity form. The activity's own two dates
    are in the same request payload, so the rule is expressible as a closure
    rule inside `configRules()` without reading the database.
  - `persistConfig()` upserts the settings row for `$activityId`. It must never
    touch the four `notified_*_at` columns (phase 10 owns those) — an admin
    editing dates does not re-arm a notification that already fired
    (architecture §3.4).
- `Resources/views/components/quote-contest-config.blade.php` — the panel:
  the two editable datetime fields, and *début des soumissions* /
  *fin des votes* rendered **greyed and read-only**, mirroring the activity's
  `active_starts_at` / `active_ends_at` (spec §4.1.3). Below them, the category
  list with add/edit/delete, using `<x-shared::input-label>`,
  `<x-shared::text-input>`, `<x-shared::input-error>` and `<x-shared::modal>`
  for the delete confirmation. Category rows are only manageable once the
  activity exists — on the **create** form the panel shows the dates and a note
  that categories are added after saving.
- `Http/routes.php` (loaded by the provider) — the three admin routes of §3.5,
  `admin` middleware, names `calendar.admin.quote-contest.categories.{store,update,destroy}`.
  **No `PATCH`** — edit is `PUT` (production WAF resets PATCH).
- `Http/Controllers/QuoteContestCategoryController.php`
- `Http/Requests/SaveCategoryRequest.php` — `title` required, max 160;
  `description` nullable text, **plain text** (assumption A3) — no Purifier, no
  rich-text editor; escaped on render.
- Lang additions in `Resources/lang/fr/quote-contest.php` for every label,
  validation message and the deletion refusal.

**Tests.** `app/Domains/Calendar/Tests/Feature/QuoteContest/AdminConfigTest.php`
and `AdminCategoryTest.php`:

- `test_creating_a_contest_persists_its_settings_row_atomically` — one POST to
  the activity form creates activity **and** settings.
- `test_submissions_end_before_activity_start_is_rejected` — French error on the
  field, no activity created.
- `test_votes_start_before_submissions_end_is_rejected`
- `test_votes_start_after_activity_end_is_rejected`
- `test_equal_submissions_end_and_votes_start_is_accepted` — the no-interlude
  configuration is legal (spec §4.4).
- `test_editing_the_activity_updates_the_settings_dates`
- `test_editing_dates_does_not_clear_the_notified_columns`
- `test_admin_can_add_edit_and_reorder_categories`
- `test_deleting_an_empty_category_succeeds`
- `test_deleting_a_category_holding_an_entry_is_refused_with_a_message`
- `test_deleting_a_category_holding_only_a_withdrawn_entry_is_also_refused`
- `test_a_non_admin_gets_403_on_every_category_route` — a confirmed user and a
  moderator both.
- `test_a_contest_with_zero_categories_is_a_valid_draft` (spec §4.1.6).

**Acceptance.**
- ✅ One save button creates the activity and its settings row, or neither.
- ✅ All four date-ordering violations are refused in French, on the right field.
- ✅ A category with any entry — withdrawn or not — cannot be deleted, and the
  admin is told why.
- ✅ A confirmed user and a moderator both get 403 on the category routes.
- ✅ `npm run gate` green.

---

## Phase 5 — Reader page shell + *Mes citations*, read-only

**Goal.** A confirmed reader opens the activity and sees the description, the
categories, their current entry in each (none yet), and their whole quote book
with ineligible quotes greyed and reasoned — all read-only. No write endpoint
exists yet.

Builds on phase 2 (`QuotePublicApi::getAllForOwner()`,
`StorySummaryDto::$is_excluded_from_events`), phase 3 (models, phase service,
component skeleton) and phase 4 (settings and categories exist to display).

Architecture sections to read: §3.2 (eligibility computation),
§3.3 (points 1 and 4 — access and the tabs array), §4 ("The reader page",
"Mes citations"), §5 (the `CalendarPrivate → QuotePublic` deptrac edge).

**This phase adds the first deptrac edge**: `CalendarPrivate → QuotePublic` in
`deptrac.yaml` under the `CalendarPrivate` ruleset. `StoryPublic` is already
allowed (Jardino established it).

**Deliverables.**

- `deptrac.yaml` — add `QuotePublic` to `CalendarPrivate`.
- `Services/QuoteContestSubmissionService.php` — for this phase, the **read**
  half only: the picker. One call to `QuotePublicApi::getAllForOwner()`, then
  one batched `StoryPublicApi::getStoriesByIds()` over the distinct `story_id`s,
  yielding per quote a reason of
  `null | 'private_story' | 'excluded_from_events'`. One extra query for the
  whole picker regardless of quote count — no N+1 (§3.2). Plus "my current entry
  per category", filtered on `withdrawn_at IS NULL`.
- `View/Components/QuoteContestComponent.php` — builds the tabs array
  server-side and hands each tab a view model. For this phase the array holds
  *Mes citations* only. `<x-shared::tabs :tabs="$tabs" tracking>` — `tracking`
  gives the URL hash so a notification can deep-link.
- `View/Models/` — the *Mes citations* view model and its quote/category rows.
  **Reader-facing view models carry no `user_id`**: that is architecture §3.3's
  "anonymity is a query-shape guarantee, not a template one". Enforce it now,
  while the shapes are being created.
- `Resources/views/partials/_my-quotes.blade.php` — one template driven by the
  phase enum, not two:
  - `BeforeStart` → description + categories, read-only, stating when
    submissions open (spec §4.2);
  - `Submissions` → the picker, live (the buttons arrive in phase 6);
  - `Interlude` / `Voting` / `Ended` → read-only with the reader's entries
    visible and a countdown / the date voting opens (spec §4.4);
  - zero categories → the "nothing to submit to" message (spec §4.1.6).
  Ineligible quotes carry their reason **as text**, not colour alone, plus
  `aria-disabled` (spec §6, accessibility).
  One `x-data` holds the filter string; filtering is a client-side substring
  match over already-rendered quotes (decision #21) — no round trip.
- Lang additions: the two ineligibility reasons (*histoire privée*,
  *histoire exclue des événements*) and every phase's banner.

**Tests.** `app/Domains/Calendar/Tests/Feature/QuoteContest/MyQuotesTabTest.php`
and `ContestAccessTest.php`:

- `test_a_non_confirmed_user_gets_404_on_the_contest_page` — decision #1 and
  assumption A5: the activity's `role_restrictions` are
  `user-confirmed` + `moderator` + `admin`, and
  `ActivityService::findVisibleBySlugOrFail()` already 404s. No new gating code —
  the test proves the configuration, not new logic.
- `test_a_non_confirmed_user_does_not_see_the_contest_in_the_activity_listing`
- `test_a_guest_is_redirected_to_login`
- `test_a_confirmed_user_sees_the_description_and_the_categories`
- `test_before_the_start_the_page_says_when_submissions_open_and_offers_no_action`
- `test_the_picker_lists_every_quote_the_reader_owns`
- `test_a_quote_whose_story_is_private_is_greyed_with_the_private_story_reason`
- `test_a_quote_whose_story_is_excluded_from_events_is_greyed_with_that_reason`
- `test_the_picker_costs_one_batched_story_read_regardless_of_quote_count`
- `test_a_reader_with_no_quotes_sees_the_empty_state`
- `test_a_contest_with_no_categories_says_there_is_nothing_to_submit_to`
- `test_the_reader_never_sees_another_readers_quotes`

**Acceptance.**
- ✅ A `user` (non-confirmed) gets 404 on the page and the activity is absent
  from `/calendar`.
- ✅ Every quote the reader owns is listed; ineligible ones carry a French
  reason in the response body.
- ✅ The picker's query count does not grow with the number of quotes.
- ✅ `Résultats` and `Votes` do not appear yet — the tabs array holds one entry.
- ✅ `npm run gate` green (deptrac included: the new edge is declared).

---

## Phase 6 — Submissions: submit / replace / withdraw

**Goal.** During the submission phase a reader can enter an eligible quote into
a category, replace what is sitting there, and withdraw an entry — with the
snapshot written and every rule enforced server-side.

Builds on phase 5, which left the picker rendering read-only with eligibility
already computed in `QuoteContestSubmissionService`, and phase 3's
`QuoteContestPhaseService`.

Architecture sections to read: §2.1 (the entries table and why there is **no**
unique index), §2.3 (withdraw/replace rows), §3.2 (snapshot construction),
§3.3 (points 2 and 3 — phase and ownership), §3.5 (reader routes), §4
("Mes citations", the replace modal).

Functional rules this phase owns: spec §4.3 in full, decisions #12, #13,
assumptions A1 and A9.

**Deliverables.**

- `Services/QuoteContestSubmissionService.php` — the write half:
  - `submit(int $activityId, int $categoryId, int $quoteId, int $userId)`:
    phase must be `Submissions`; the quote must come back non-null from
    `QuotePublicApi::getOwnedQuote($quoteId, $userId)` (the contest never queries
    `quotes` itself); eligibility is **re-checked server-side** — the greying in
    the picker is a courtesy, this is the enforcement; then the snapshot is
    written. If the (category, user) slot already holds a non-withdrawn entry,
    that row is **hard-deleted** first (votes cascade by FK) and the new one
    written, both in one transaction.
  - `withdraw(int $entryId, int $userId)` — hard delete, own entry only, phase
    must be `Submissions`.
  - The one-entry-per-(category, user) rule is a **service check filtering on
    `withdrawn_at IS NULL`**, not a unique index — see §2.1 for why an index
    cannot express it.
  - The snapshot copies `highlighted_text`, story title/slug, chapter id/title/
    slug and `author_user_ids`. The reader's private **note** is never read and
    never stored (assumption A1). `quote_id` and `story_id` are stored for
    provenance and for the phase-7 listeners; `quote_id` is **never
    dereferenced for display**.
  - *(See open item O1: `QuoteDto` exposes `storyUrl` / `chapterUrl` but not the
    raw slugs the entries table stores. Resolve before starting.)*
- `Http/routes.php` — the two reader routes of §3.5 (`web`, `auth`, `verified`):
  `POST /calendar/quote-contest/{activity}/entries` and
  `DELETE /calendar/quote-contest/{activity}/entries/{entry}`.
- `Http/Controllers/QuoteContestEntryController.php`
- `Http/Requests/SubmitEntryRequest.php` — `category_id`, `quote_id`, both
  required integers; ownership and eligibility are the service's job, not the
  request's.
- `Resources/views/partials/_my-quotes.blade.php` — the submit/withdraw buttons
  in the `Submissions` phase, and the replace flow: an occupied category shows
  the sitting quote and asks for confirmation through `<x-shared::modal>` before
  the POST — **not** a JS `confirm()`.

**Tests.** `app/Domains/Calendar/Tests/Feature/QuoteContest/SubmitEntryTest.php`:

- `test_a_confirmed_user_submits_an_eligible_quote_to_a_category`
- `test_the_entry_snapshots_the_passage_story_and_chapter`
- `test_the_entry_never_stores_the_private_note` — assert the column set and the
  rendered page (assumption A1).
- `test_submitting_into_an_occupied_category_replaces_the_sitting_entry`
- `test_the_same_quote_may_be_entered_in_several_categories` (spec §4.3.7)
- `test_two_readers_may_enter_the_same_passage_in_one_category` (decision #13)
- `test_a_reader_may_withdraw_an_entry_without_replacing_it` (decision #12)
- `test_a_reader_cannot_withdraw_another_readers_entry` → 403
- `test_submitting_a_quote_the_caller_does_not_own_is_refused` → 403, forged
  request, no row written.
- `test_submitting_an_ineligible_quote_is_refused_even_when_the_request_is_forged`
  — both reasons, server-side, no row written. **This is the security test of
  this phase and it lives here, not in a later hardening phase.**
- `test_submitting_before_the_start_is_403`
- `test_submitting_after_submissions_close_is_403`
- `test_withdrawing_after_submissions_close_is_403`
- `test_the_page_is_read_only_during_the_interlude_and_shows_the_countdown`
  (spec §4.4)
- `test_editing_or_deleting_the_source_quote_leaves_the_entry_untouched`
  (spec §5, decision #4)

**Acceptance.**
- ✅ A forged POST naming someone else's quote, an ineligible quote, or a quote
  outside the submission phase writes no row and returns 403.
- ✅ A reader holds at most one non-withdrawn entry per category, proven by a
  test that submits twice.
- ✅ Deleting the source quote from the quote book leaves the entry and its
  snapshot intact.
- ✅ The private note appears nowhere in the entries table nor in any response.
- ✅ `npm run gate` green.

---

## Phase 7 — Story-eligibility listeners: withdrawal on visibility loss

**Goal.** When a quoted story turns private or becomes excluded from events, the
entries drawn from it are withdrawn, stop counting, and free their category slot.

Builds on phase 6, which introduced `QuoteContestSubmissionService` and the
entries table whose `withdrawn_at` column every read already filters on. This
phase adds the **writer** of that column.

Architecture sections to read: §2.3 (the "story turns private" and "story
returns to public" rows), §3.4 ("Listened to"), §9 (the `withdrawn_at` risk).

Both Story events already exist and need no change — verified:
`App\Domains\Story\Public\Events\StoryVisibilityChanged` carries
`oldVisibility`/`newVisibility`, and `StoryExcludedFromEvents` carries
`storyId`. `CalendarPrivate → StoryPublic` and `→ EventsPublic` are already
allowed by deptrac; **no new edge in this phase**.

**Deliverables.**

- `Listeners/WithdrawEntriesOnStoryIneligible.php` — two handlers:
  - `handleVisibilityChanged` withdraws when `newVisibility` is neither `public`
    nor `community`. The event carries the new visibility, so the listener needs
    no Story read.
  - `handleExcludedFromEvents` withdraws unconditionally for that story.
  Both funnel into **one** `QuoteContestSubmissionService::withdrawEntriesForStory(int $storyId)`
  so the two paths cannot drift.
- `Services/QuoteContestSubmissionService.php` — `withdrawEntriesForStory()`:
  a single indexed `UPDATE … WHERE story_id = ? AND withdrawn_at IS NULL`,
  stamping `withdrawn_at`. Votes rows are **not** deleted — every count and
  listing filters on `withdrawn_at IS NULL` (decision #18 refines #4: votes are
  dropped from the *count*, not from the table).
- `QuoteContestServiceProvider.php` — subscribe both handlers on `EventBus`,
  following `JardinoServiceProvider::registerEventListeners()`.
- `app/Domains/Calendar/AGENTS.md` — add the two subscriptions to the
  "Listens To" section, and a "Non-Obvious Invariants" entry for `withdrawn_at`:
  every listing, tally and one-per-category check must filter on it; a read path
  that forgets it silently resurrects a withdrawn passage.

**Tests.** `app/Domains/Calendar/Tests/Feature/QuoteContest/StoryEligibilityWithdrawalTest.php`
— drive through the real event, using `dispatchEvent` from
`app/Domains/Events/Tests/helpers.php`, never by calling the listener directly:

- `test_turning_a_story_private_withdraws_its_entries`
- `test_turning_a_story_to_community_does_not_withdraw` — `community` is eligible.
- `test_excluding_a_story_from_events_withdraws_its_entries`
- `test_a_withdrawn_entry_frees_its_category_slot_so_the_reader_may_re_enter`
  (only while submissions are open)
- `test_a_withdrawn_entry_disappears_from_the_reader_view`
- `test_a_story_returning_to_public_does_not_restore_the_entry` (§2.3 — no
  automatic restore, deliberate)
- `test_the_listener_is_a_no_op_for_a_story_with_no_entries` — assert no write.

**Acceptance.**
- ✅ Publishing `StoryVisibilityChanged` to `private` stamps `withdrawn_at` on
  every non-withdrawn entry for that story, and on nothing else.
- ✅ The withdrawn entry vanishes from the reader's *Mes citations* and its
  category slot is re-enterable during submissions.
- ✅ A story going back to `public` leaves the withdrawal in place.
- ✅ `npm run gate` green.

---

## Phase 8 — Voting: ballot, seeded shuffle, *Votes* tab

**Goal.** During the vote phase a confirmed reader sees every category's entries
in a per-reader stable shuffle, casts one ballot per category, changes it
freely, and never sees a vote count or a submitter's name.

Builds on phase 6 (entries exist, snapshotted) and phase 7 (`withdrawn_at` is
written; every listing here must filter on it). The tabs array built in phase 5
gains a second entry.

Architecture sections to read: §2.1 (the votes table — this one **does** have a
real unique index on `(category_id, user_id)`), §3.2 (the seeded shuffle),
§3.3 (points 2 and the anonymity paragraph), §3.5 (the `PUT …/votes/{category}`
route and why not PATCH), §4 ("Votes"), §6 (the anonymity tests).

Functional rules this phase owns: spec §4.5 in full, decisions #3, #6,
assumption A10.

**Deliverables.**

- `Services/QuoteContestVoteService.php`:
  - the category listing for a reader — non-withdrawn entries only, ordered by a
    **PHP-side shuffle seeded on `crc32($userId . ':' . $categoryId)`**:
    deterministic per reader, stable across reloads, no stored column, no extra
    query (decision #22, tradeoff 7);
  - `castVote(int $categoryId, int $entryId, int $userId)` — phase must be
    `Voting`; the entry must belong to that category and not be withdrawn;
    changing a vote **updates** the existing row (that is why the unique index
    works here). A reader **may** vote for their own entry (decision #3) and
    nothing indicates which entry is theirs.
  - No abstention record (assumption A10): "has not voted" and "chose not to
    vote" are the same absent row.
- `Http/routes.php` — `PUT /calendar/quote-contest/{activity}/votes/{category}`.
  **No `PATCH`** — the production WAF resets that verb.
- `Http/Controllers/QuoteContestVoteController.php`
- `Http/Requests/CastVoteRequest.php`
- `View/Models/` — the votes view model. It carries **no `user_id` and no vote
  count** — not "carries them and the template omits them". A template mistake
  must not be able to leak either, because neither is in the object (§3.3).
- `Resources/views/partials/_votes.blade.php` — one `<fieldset>` per category
  containing a **radio group**: the accessible shape for "one choice among N",
  keyboard-operable with screen-reader state for free, no hand-rolled ARIA. Each
  entry card shows the passage, the story title as a link, the chapter title as
  a link, and the author names (resolved live from `author_user_ids` through
  `ProfilePublicApi`, batched — decision #19; a deleted author resolves to null
  and is omitted, and the entry still stands). Each category is marked
  *vous avez voté* / *vous n'avez pas voté*. After the activity ends the tab is
  read-only: the reader sees their own vote and **never** any result
  (spec §4.5.8).
- `View/Components/QuoteContestComponent.php` — add the *Votes* tab to the array.

**Tests.** `app/Domains/Calendar/Tests/Feature/QuoteContest/VoteTest.php` and
`VoteAnonymityTest.php`, plus one unit test:

- `app/Domains/Calendar/Tests/Unit/QuoteContest/SeededShuffleTest.php` —
  `test_the_order_is_stable_for_the_same_reader_and_category` and
  `test_two_readers_get_different_orders`, with fixed ids. The second of the
  feature's only two unit tests (§6).
- `test_a_confirmed_user_casts_one_vote_in_a_category`
- `test_a_reader_may_change_their_vote` — the row is updated, not duplicated.
- `test_a_second_ballot_in_the_same_category_never_creates_a_second_row`
- `test_a_reader_may_vote_for_their_own_entry` (decision #3)
- `test_voting_before_the_vote_phase_is_403`
- `test_voting_after_the_activity_ends_is_403`
- `test_voting_for_an_entry_of_another_category_is_refused`
- `test_voting_for_a_withdrawn_entry_is_refused`
- `test_a_withdrawn_entry_is_absent_from_the_vote_listing`
- **Anonymity, asserted at the response level (§6):**
  - `test_the_vote_tab_response_contains_no_submitter_name_for_an_entry_the_reader_did_not_submit`
  - `test_the_vote_tab_response_contains_no_submitter_name_for_the_readers_own_entry`
  - `test_the_vote_tab_response_contains_no_vote_count_in_any_phase` — before,
    during and after (decision #6).
- `test_an_empty_category_renders_as_empty_and_is_votable_by_nobody`
  (assumption A8)

**Acceptance.**
- ✅ A reader holds at most one vote row per category, enforced by the database.
- ✅ Reloading the vote tab returns the entries in the same order for that
  reader, and a different order for another reader.
- ✅ No submitter name and no vote count appears in a confirmed user's response
  body, in any phase.
- ✅ Voting outside the vote phase returns 403 and writes nothing.
- ✅ `npm run gate` green.

---

## Phase 9 — Moderation: *Résultats* tab, entry deletion, submitter notification

**Goal.** Moderators and admins get a permanent *Résultats* tab showing every
entry with its vote count and its submitter, can delete any entry at any point,
and the submitter is told their slot is free again.

Builds on phase 8 (entries, votes, the two-tab array) and phase 7
(`withdrawn_at`, which every tally here filters on).

Architecture sections to read: §3.2 (vote counts computed on read),
§3.3 (point 4 — moderation, and the "query-shape guarantee" paragraph),
§3.4 ("Notifications" — the `EntryRemovedNotification`), §3.5 (the moderation
route), §4 ("Résultats"), §5 (the `CalendarPrivate → NotificationPublic` edge).

Functional rules this phase owns: spec §4.6 and decision #11.

**This phase adds the second deptrac edge**: `CalendarPrivate → NotificationPublic`
in `deptrac.yaml`. Calendar has never sent a notification before.

**Deliverables.**

- `deptrac.yaml` — add `NotificationPublic` to `CalendarPrivate`.
- `Services/QuoteContestVoteService.php` — the moderator tally: a
  `GROUP BY entry_id` over the votes table scoped to one activity, filtering out
  withdrawn entries. **No denormalised counter** (tradeoff 8).
- `Services/QuoteContestSubmissionService.php` —
  `deleteEntryAsModerator(int $entryId, int $actorUserId)`: hard delete, votes
  cascade away by FK, submitter notified. Allowed **at any point in the
  contest's life**, not only during a phase.
- `Http/routes.php` — `DELETE /calendar/quote-contest/{activity}/moderation/entries/{entry}`.
- `Http/Controllers/QuoteContestModerationController.php` — role check via
  `AuthPublicApi::hasAnyRole([...])`. *(See open item O2 for the exact role
  list.)*
- `View/Models/` — the **results** view model, the only one that carries a
  submitter identity. The reader-facing models built in phases 5 and 8 must stay
  free of it.
- `Resources/views/partials/_results.blade.php` — a plain server-rendered table
  per category: entry, vote count, submitter. No Alpine. Deletion is a form POST
  behind a `<x-shared::modal>` confirmation. Withdrawn entries are shown as
  withdrawn — *Résultats* can still show what happened (tradeoff 3).
- `View/Components/QuoteContestComponent.php` — the *Résultats* entry is
  **omitted from `$tabs`** for everyone who is not a moderator or admin. It is
  never rendered and then hidden. The check lives in the controller **and** in
  the component that builds the tabs array (§3.3 point 4).
- `Notifications/EntryRemovedNotification.php` implementing
  `NotificationContent`, following
  `app/Domains/Quote/Public/Notifications/ChapterQuotedNotification.php` for
  shape. It must capture everything it displays at creation time and **never
  read the database when displayed**; it may generate route URLs. It carries the
  category title and the activity slug — never the passage's private context,
  never the note.
- `QuoteContestServiceProvider.php` — register the notification group and type
  on `NotificationFactory` (see `QuoteServiceProvider::registerNotifications()`).
  *(See open item O3 on the group id.)*
- `Resources/lang/fr/quote-contest.php` — the notification display string and
  every *Résultats* label.
- `docs/notification-types.md` — add the new type.

**Tests.** `app/Domains/Calendar/Tests/Feature/QuoteContest/ResultsTabTest.php`
and `ModerationDeleteTest.php`:

- `test_a_moderator_sees_the_results_tab_with_vote_counts_and_submitters`
- `test_an_admin_sees_the_results_tab`
- `test_the_results_tab_is_permanent_before_during_and_after_the_contest`
- `test_a_confirmed_user_gets_403_on_the_results_route`
- `test_a_confirmed_user_gets_403_on_the_moderation_delete_route`
- `test_a_moderator_can_delete_an_entry_at_any_phase`
- `test_deleting_an_entry_drops_its_votes` — the votes rows are gone (FK cascade).
- `test_deleting_an_entry_frees_the_category_slot_for_the_submitter`
- `test_the_submitter_is_notified_that_their_entry_was_removed` — assert with the
  helpers in `app/Domains/Notification/Tests/helpers.php`, **never against the
  notification table directly**.
- `test_nobody_but_the_submitter_is_notified`
- `test_the_notification_never_contains_the_private_note` (assumption A1)
- `test_an_entry_whose_submitter_was_deleted_still_appears_in_results`
  (spec §5, decision #7) — with no identifiable submitter.
- `test_withdrawn_entries_are_excluded_from_the_vote_counts`

**Acceptance.**
- ✅ A moderator and an admin see *Résultats*; a confirmed user gets 403 on both
  moderation routes.
- ✅ Deleting an entry removes its votes and notifies exactly one user.
- ✅ Vote counts exclude withdrawn entries.
- ✅ An entry whose submitter no longer exists still renders.
- ✅ `npm run gate` green (deptrac included).

---

## Phase 10 — Scheduled broadcast notifications + Artisan command

**Goal.** The four date-triggered broadcasts fire once each, to confirmed users
only, driven by the contest's own dates.

Builds on phase 9, which added the `CalendarPrivate → NotificationPublic`
deptrac edge and the notification group registration, and on phase 3's
`notified_*_at` columns.

Architecture sections to read: §3.4 ("Notifications" and "Scheduling"),
§2.1 (the four `notified_*_at` columns), §9 (the scheduler risk).

Functional rules this phase owns: spec §4.7, decisions #9 and #10.

**The trap this phase exists to avoid.** `NotificationPublicApi::createBroadcastNotification()`
targets `Roles::USER` **and** `Roles::USER_CONFIRMED` — verified in the source.
Decision #10 restricts the audience to confirmed users, for whom alone the link
is not dead. So the four broadcasts use
`createNotification($userIds, $content)` with
`AuthPublicApi::getUserIdsByRoles([Roles::USER_CONFIRMED], activeOnly: true)`.
There is a named test for this below; it is the guard on the trap.

**Deliverables.**

- `Notifications/SubmissionsOpenNotification.php`,
  `SubmissionsClosingNotification.php`, `VotesOpenNotification.php`,
  `VotesClosingNotification.php` — all `NotificationContent`, all carrying the
  activity name and slug so `display()` can build the link without a database
  read, all deep-linking with the tab hash where it helps (`#votes` for the vote
  broadcasts — phase 5's tabs component was given `tracking` for this).
- `Console/NotifyQuoteContestCommand.php`, signature
  `calendar:quote-contest-notify`. Each tick scans
  `calendar_quote_contest_settings` for contests whose trigger moment has passed
  and whose matching `notified_*_at` column is still null, sends, then stamps
  the column **in the same transaction**. The four triggers:
  activity `active_starts_at`; `submissions_end_at − 24h`; `votes_start_at`;
  activity `active_ends_at − 24h`.
  The table holds one row per contest ever created — a handful — so a full scan
  is correct.
- `QuoteContestServiceProvider.php` — register the command and the four
  notification types on `NotificationFactory`.
- `bootstrap/app.php` — `$schedule->command('calendar:quote-contest-notify')->cron('*/5 * * * *');`
  next to `story:publish-scheduled-chapters`, the app's only precedent for
  date-triggered work and the reason no queue is involved.
- `Resources/lang/fr/quote-contest.php` — the four display strings.
- `docs/notification-types.md` — add the four types.

Two behaviours that are **deliberate**, per §3.4 — do not "fix" them:
- a contest whose start is already past when created fires "submissions open" on
  the next tick (late is better than never);
- moving a date forward past an already-stamped moment re-fires nothing
  (re-notifying the whole confirmed user base on an admin's date correction
  would be spam).

**Tests.** `app/Domains/Calendar/Tests/Feature/QuoteContest/NotificationScheduleTest.php`
— assert with `app/Domains/Notification/Tests/helpers.php`, never the table:

- `test_submissions_open_fires_on_the_tick_after_the_activity_starts`
- `test_submissions_closing_fires_24h_before_submissions_end`
- `test_votes_open_fires_on_the_tick_after_votes_start`
- `test_votes_closing_fires_24h_before_the_activity_ends`
- `test_a_second_tick_sends_nothing` — for each of the four. Idempotence is the
  column.
- `test_only_confirmed_users_are_notified` — a non-confirmed `user`, a moderator
  and an admin fixture in the database; assert the non-confirmed `user` receives
  nothing. **This is the guard on the `createBroadcastNotification()` trap.**
- `test_a_contest_whose_start_is_already_past_fires_on_the_next_tick`
- `test_moving_a_date_forward_past_a_stamped_moment_does_not_re_fire`
- `test_nothing_fires_for_a_draft_or_archived_activity`
- `test_the_notification_links_to_the_activity_page`

**Acceptance.**
- ✅ Each of the four broadcasts fires exactly once, whatever the number of ticks.
- ✅ A non-confirmed `user` receives none of the four.
- ✅ The command is registered on the 5-minute cron in `bootstrap/app.php`.
- ✅ `docs/notification-types.md` lists all five contest notification types.
- ✅ `npm run gate` green.

---

## Phase 11 — Docs, i18n sweep, a11y polish

**Goal.** Leave the domain documented and the strings clean, so the feature is
maintainable without the planning folder.

Builds on phases 1–10, which delivered the whole feature.

**Deliverables.**

- `app/Domains/Calendar/Private/Activities/QuoteContest/README.md` — modelled on
  `Private/Activities/SecretGift/README.md` (84 lines is the right order of
  magnitude): what the contest is, the three tables, the phase enum, the five
  notifications, the two listeners, the moderation surface.
- `app/Domains/Calendar/README.md` and `AGENTS.md` — the `quote-contest` row in
  the registry table; the non-obvious invariants that belong at the domain level:
  the `withdrawn_at` filter rule (if phase 7 did not already add it), anonymity
  as a view-model shape rather than a template concern, and vote counts computed
  on read.
  **These files must never reference `docs/Feature_Planning`** — the gate's
  `docs` step fails on it. Planning may link to code docs, never the reverse.
- `docs/Domain_Structure.md` / the root `CLAUDE.md` domain registry — extend the
  Calendar row's responsibilities to mention the contest, if the wording there
  enumerates activity types.
- i18n sweep: every user-visible string in the sub-module resolves from
  `quote-contest::`, in French. Grep the Blade files for bare literals.
- a11y pass against spec §6: the vote radio groups are keyboard-operable and
  expose their state; ineligible quotes carry their reason **as text**, not
  colour alone, with `aria-disabled`; the tabs and modals keep focus sane.
- Confirm every relative markdown link in the new docs resolves — the gate's
  `docs` step checks this too.

**Tests.** No new behaviour, so no new feature test. If the i18n sweep changes a
key, the tests asserting on French text are the regression signal.

**Acceptance.**
- ✅ `app/Domains/Calendar/Private/Activities/QuoteContest/README.md` exists and
  describes the sub-module without pointing at `docs/Feature_Planning`.
- ✅ The Calendar registry table lists three activity types.
- ✅ No bare user-visible literal remains in the sub-module's Blade files.
- ✅ `npm run gate` green, `docs` step included.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes.
The last row is the one architecture §6 flags as structurally uncoverable by
integration tests.

| Surface | Check | OK? |
|---------|-------|-----|
| Admin — activity create | Picking *Concours de citations* in the type select reveals the config panel; picking another type hides it. | |
| Admin — config panel | *début des soumissions* and *fin des votes* are visibly greyed and read-only; the two editable dates accept input. | |
| Admin — date violation | A bad date order shows a French error on the right field, and the form keeps what was typed. | |
| Admin — categories | Add, edit, reorder. Deleting an empty one works; deleting a filled one shows the refusal message, not a stack trace. | |
| Reader — before start | Description and categories read correctly; the "submissions open on…" line is present and no action is offered. | |
| Reader — *Mes citations*, empty | A reader with no quotes gets a sensible empty state, not a blank panel. | |
| Reader — *Mes citations*, long book | The filter box feels instant on a 200+ quote book; typing causes no round trip. | |
| Reader — ineligible quotes | Greyed rows read correctly: the reason is legible **as text**, and the row cannot be picked by mouse or keyboard. | |
| Reader — replace flow | Submitting into an occupied category shows the sitting quote in the modal and asks before replacing. | |
| Reader — interlude | Read-only view with the entries still visible and the countdown to the vote. | |
| Reader — *Votes* | Radio group is fully keyboard-operable (arrow keys move, space selects); the selected state is announced. | |
| Reader — *Votes*, links | Story and chapter links open the right pages and stay tappable. | |
| Reader — after the end | *Votes* is read-only, the reader's own vote is visible, and no result appears anywhere. | |
| Reader — stale data | A story renamed after submission shows the old title but the link still resolves (slug is `{base}-{id}`). | |
| Reader — deleted parent | An entry whose source quote was deleted still displays; an entry whose chapter was deleted has a link that 404s gracefully. | |
| Moderator — *Résultats* | Vote counts and submitter names present; the delete confirmation modal behaves. | |
| Moderator — deactivated submitter | The entry renders with no identifiable submitter, and nothing crashes. | |
| Notifications | Each of the five lands in the bell with a working link; the vote ones deep-link to `#votes`. | |
| Mobile (narrow viewport) | The three tabs scroll rather than overflow; entry cards stack; links stay tappable. | |
| **Confirmed user — tab list** | **The *Résultats* tab is absent from the rendered page** — not present-and-hidden. Inspect the DOM, not just the screen. | |

## Open items

Each names the phase that must resolve it before starting.

**O1 — `QuoteDto` gives URLs, not slugs. (Phase 6.)**
The entries table stores `story_slug` and `chapter_slug` (architecture §2.1), but
`App\Domains\Quote\Public\Api\Contracts\QuoteDto` exposes `storyUrl` and
`chapterUrl` — the slugs are consumed inside `QuoteService::buildProfileItems()`
and never surfaced. Verified by reading the DTO. Two ways out, both cheap:
resolve the slugs in the snapshot builder from
`StoryPublicApi::getStoriesByIds()` / `getChaptersByIds()` (both DTOs carry
`slug`, and the submission service already batches the story read), or add
`storySlug` / `chapterSlug` to `QuoteDto` in phase 2. **Prefer the first** — it
keeps phase 2 to two methods and adds no field to a DTO three domains read.

**O2 — Which roles reach the moderation surface? (Phase 9.)**
Spec §3 says *moderator* and *admin*. The codebase also has `Roles::TECH_ADMIN`,
and Calendar's own admin checks use `[Roles::ADMIN, Roles::TECH_ADMIN]`.
The plan assumes `[Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN]` for the
*Résultats* tab and entry deletion, on the grounds that tech-admin is admin
everywhere else in Calendar. If BUILD finds a project convention that says
otherwise, follow the convention and note it in `DECISIONS.md`.

**O3 — Notification group id and sort order. (Phase 9.)**
`NotificationFactory::registerGroup()` needs an id, a `sortOrder` and a
translation key (Quote uses `id: 'quote', sortOrder: 60`). The contest's five
types need a group — most likely a new `calendar` group, so a future
`calendar-notifications/` can reuse it. Pick the id and a free sort order by
reading the existing `registerGroup()` calls across domains before phase 9.

**O4 — `role_restrictions` on the contest activity are configuration, not code.
(Phase 5.)**
Decision #1 and assumption A5 are enforced by the admin setting
`user-confirmed` + `moderator` + `admin` on the activity, through Calendar's
existing mechanism. Nothing in the code guarantees it. The phase-5 access tests
must therefore create the activity **with those restrictions** and assert the
404 — they prove the configuration is sufficient, not that new gating exists.
Worth a line in the sub-module README (phase 11) so an admin creating the second
contest does not forget.
