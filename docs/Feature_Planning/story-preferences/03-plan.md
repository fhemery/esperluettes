# Story preferences — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Settings tab « Histoires » + `StoryPreferenceService` | S | — | DONE |
| 2 | Hide-from-lists — library `/stories` | S | 1 | DONE |
| 3 | Hide-from-lists — dashboard discover + story search | S | 1 | TODO |
| 4 | Hide-TW-display — shared component + story show | S | 1 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

Phases 2, 3 and 4 are independent of each other — only of phase 1. They may
ship in any order after phase 1; the order above is "cheapest to eyeball first".

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Settings tab « Histoires » + `StoryPreferenceService`

**Goal.** Register the new Settings tab with its two booleans, and add the
request-memoized service that phases 2–4 read.

Architecture: §2.2 (constants), §3.2 (service), §4 (Settings UI), §8 (layout).

**Deliverables.**

- `app/Domains/Story/Public/Providers/StoryServiceProvider.php`
  - New constants next to the existing `TAB_PROFILE` / `KEY_HIDE_COMMENTS_SECTION`:

    ```php
    public const TAB_STORIES = 'stories';
    public const SECTION_READING = 'reading';
    public const KEY_HIDE_TRIGGER_WARNINGS = 'hide-trigger-warnings';
    public const KEY_HIDE_STORIES_WITH_TW = 'hide-stories-with-tw';
    ```

  - New private method `registerStoryPreferenceSettings()`, called from the
    **existing** `$this->app->booted(...)` closure in `boot()`, right after the
    current `$this->registerSettings();` call.
    **Do not extend `registerSettings()`** — it starts with
    `if ($settingsApi->getParameter(self::TAB_PROFILE, self::KEY_HIDE_COMMENTS_SECTION) !== null) return;`,
    so anything appended to it is skipped on the second boot (tests). The new
    method carries its own idempotency guard:
    `if ($settingsApi->getTab(self::TAB_STORIES) !== null) return;`.
  - It registers, in this order: `registerTab` → `registerSection` →
    `registerParameter` ×2.
    - Tab: `id: self::TAB_STORIES`, `order: 15`, `nameKey: 'story::settings.tabs.stories'`,
      `icon: 'menu_book'`. (Existing tab orders: Général 10, Pile à lire 20,
      Profil 30, Notifications 30 — 15 slots « Histoires » second.)
    - Section: `tabId: self::TAB_STORIES`, `id: self::SECTION_READING`,
      `order: 10`, `nameKey: 'story::settings.sections.reading.name'`,
      `descriptionKey: 'story::settings.sections.reading.description'`.
    - Parameters: both `type: ParameterType::BOOL`, `default: false`;
      `KEY_HIDE_TRIGGER_WARNINGS` at `order: 10`,
      `KEY_HIDE_STORIES_WITH_TW` at `order: 20`; name/description keys under
      `story::settings.params.<key>.*`.
    - Copy the shape from `app/Domains/ReadList/Public/Providers/ReadListServiceProvider.php`
      (`registerSettings()`, lines ~131–168) — same three calls, same guard style.
  - Add a `register(): void` method binding the service as a **singleton**:
    `$this->app->singleton(StoryPreferenceService::class);`. Without it, every
    `app(StoryPreferenceService::class)` in Blade builds a fresh instance and the
    memoization required by architecture §3.2 / decision #12 never fires.

- `app/Domains/Story/Private/Services/StoryPreferenceService.php` — **new**:

  ```php
  public function __construct(private SettingsPublicApi $settings) {}
  public function hidesTriggerWarnings(?int $userId = null): bool
  public function hidesStoriesWithTriggerWarnings(?int $userId = null): bool
  ```

  - `$userId ??= Auth::id();` — return `false` when it is still null (guests
    never persist preferences, architecture §3.3).
  - Reads `SettingsPublicApi::getValue($userId, StoryServiceProvider::TAB_STORIES, $key)`
    and casts with `(bool)`.
  - Memoizes in `private array $memo = []` keyed by `"{$userId}:{$key}"` so a
    library grid of 12 cards costs one Settings lookup.
  - Deptrac: `StoryPrivate → SettingsPublic` is already an allowed edge (used by
    nothing in `Private/` yet, but listed in `deptrac.yaml`). No ruleset change.

- `app/Domains/Story/Private/Resources/lang/fr/settings.php` — **new**. The
  `story::` namespace is already loaded from that `lang` directory by
  `loadTranslationsFrom` in the provider, so no wiring is needed. Shape:

  ```php
  return [
      'tabs' => ['stories' => 'Histoires'],
      'sections' => ['reading' => ['name' => …, 'description' => …]],
      'params' => [
          'hide-trigger-warnings' => ['name' => 'Masquer les avertissements', 'description' => …],
          'hide-stories-with-tw' => ['name' => 'Masquer les histoires avec avertissement', 'description' => …],
      ],
  ];
  ```

  Working titles come from assumption #3; tightening the French wording here is
  allowed and expected (functional §9), the keys are not.

**Tests.** New `app/Domains/Story/Tests/Feature/Settings/StoryPreferencesSettingsTest.php`
(Pest, `uses(TestCase::class, RefreshDatabase::class)`), mirroring
`app/Domains/ReadList/Tests/Feature/Settings/ReadListSettingsTest.php`:

- `it('registers the stories settings tab')` — `getTab('stories')` not null, right `nameKey`.
- `it('registers the reading section')` — one section, id `reading`.
- `it('registers both preferences as booleans defaulting to false')` —
  `getParametersForSection('stories', 'reading')`, both keys, `ParameterType::BOOL`, `default === false`.
- `it('shows the Histoires tab on the settings page')` —
  `actingAs($user)->get(route('settings.index'))` sees `__('story::settings.tabs.stories')`.
- `it('lets a non-confirmed user toggle each preference')` and
  `it('lets a confirmed user toggle each preference')` —
  `putJson(route('settings.update', ['tab' => …, 'key' => …]), ['value' => true])`
  → 200 + `getValue(...) === true` (decision #6; PUT, never PATCH).
- `it('returns false for a user who never toggled anything')` and
  `it('returns false for a guest')` — on `StoryPreferenceService` directly.
- `it('returns the stored value for each preference')` — after
  `setSettingsValue($user->id, StoryServiceProvider::TAB_STORIES, …, true)`
  (global helper from `app/Domains/Settings/Tests/helpers.php`).
- `it('resolves the preference service as a singleton')` —
  `expect(app(StoryPreferenceService::class))->toBe(app(StoryPreferenceService::class))`.
  This is the memoization contract; do not try to count queries.

**Test caveat to carry into later phases** (state it in a comment in this file):
the container is *not* rebuilt between two HTTP calls inside one test, so a test
that flips a preference *between* two requests must call
`app()->forgetInstance(StoryPreferenceService::class)` after `setSettingsValue`,
or split into two `it()` blocks. Setting the preference before the first request
— the normal case — needs nothing.

**Acceptance.**
- ✅ An « Histoires » tab appears on `route('settings.index')` for both `user` and `user-confirmed`, with two toggles, and does not appear for a guest (settings page is authenticated).
- ✅ `SettingsPublicApi::getParameter('stories', 'hide-trigger-warnings')` and `('stories', 'hide-stories-with-tw')` are `ParameterType::BOOL` with `default === false`.
- ✅ `PUT settings.update` persists each preference and `getValue` reads it back.
- ✅ `StoryPreferenceService::hidesTriggerWarnings()` / `hidesStoriesWithTriggerWarnings()` return `false` for a guest and for a user with no stored value.
- ✅ The pre-existing `hide-comments-section` registration is untouched: `app/Domains/Story/Tests/Feature/ProfileCommentsPolicyTest.php` still green.
- ✅ No user-visible change outside the settings page — nothing filters or hides yet.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Hide-from-lists on the library `/stories`

**Goal.** When the reader's « masquer les histoires avec avertissement »
preference is on, `/stories` lists only stories whose `tw_disclosure` is
`no_tw`, without touching the library checkbox's own state.

Architecture: §3.1 (`listStories` must *not* auto-apply), §3.2 (discovery paths),
decision #14 and functional §4.3–§4.4.

Phase 1 left behind `App\Domains\Story\Private\Services\StoryPreferenceService`,
a container singleton with
`hidesStoriesWithTriggerWarnings(?int $userId = null): bool` — memoized per
user, `false` for guests, backed by the `stories` / `hide-stories-with-tw`
setting.

**Deliverables.**

- `app/Domains/Story/Private/Controllers/StoryController.php`, `index()`
  (the relevant lines today are ~89–105 for the filter and ~135–153 for the
  view data):
  - inject `StoryPreferenceService` into the constructor;
  - keep `$noTwOnly = request()->boolean('no_tw_only', false);` — that stays the
    *checkbox* state;
  - add `$effectiveNoTwOnly = $noTwOnly || $this->preferences->hidesStoriesWithTriggerWarnings();`
  - pass `noTwOnly: $effectiveNoTwOnly` into `new StoryFilterAndPagination(...)`;
  - leave `$appends['no_tw_only']` and `'currentNoTwOnly' => $noTwOnly` on the
    **raw** checkbox value. The preference must not tick the checkbox nor leak
    into pagination links (functional §4.4, architecture §3.1).

- **Nothing else changes.** In particular, do **not** apply the preference
  inside `StoryService::searchStories` or `StoryRepository::searchStories`:
  that same method also serves `StoryPublicApi::listStories` (decision #14
  forbids auto-applying there) and `ProfileStoriesComponent` (others' profile
  story tabs stay unfiltered, functional §4.3). The library controller is the
  only call site that is the library and nothing else.
- No repository work either: `StoryRepository::searchStories` already does
  `$query->where('tw_disclosure', Story::TW_NO_TW)` when `$filter->noTwOnly`
  (~line 124).

**Tests.** New `app/Domains/Story/Tests/Feature/Stories/StoryListPreferenceFilterTest.php`.
Build the fixture the way
`app/Domains/Story/Tests/Feature/Stories/ListStoriesTest.php` does in
`it('filters to only explicit No TW stories when no_tw_only=1')`: three public
stories, `tw_disclosure` forced to `Story::TW_NO_TW` / `TW_LISTED` /
`TW_UNSPOILED` via `saveQuietly()`, each with a published chapter.

- `it('hides listed and unspoiled stories from /stories when the preference is on')`
- `it('leaves /stories unfiltered when the preference is off')`
- `it('does not tick the no_tw_only checkbox when only the preference is on')` —
  `$resp->viewData('currentNoTwOnly')` is `false` while the list is filtered
- `it('behaves identically when the checkbox and the preference are both on')` — `?no_tw_only=1`
- `it('still filters with the checkbox alone when the preference is off')` (regression guard on the existing behaviour)
- `it('does not filter /stories for a guest')`
- `it('hides the reader own trigger-warned story from /stories')` — no author exemption (assumption #1)
- `it('does not filter the profile stories tab when the preference is on')` —
  render `<x-story::profile-stories-component>` (see
  `app/Domains/Story/Tests/Feature/Stories/Components/` for the existing pattern)
  or hit the author's profile route as the preferring reader; the TW story is still listed
- `it('does not filter StoryPublicApi::listStories when the preference is on')` —
  call the public API directly while acting as the preferring reader (decision #14)

**Acceptance.**
- ✅ With the preference on, `GET /stories` shows the `no_tw` story and neither the `listed` nor the `unspoiled` one.
- ✅ With the preference on, `viewData('currentNoTwOnly')` is `false` and pagination links carry no `no_tw_only` param.
- ✅ With the preference off, `GET /stories` is byte-for-byte the behaviour of today (existing `ListStoriesTest` still green).
- ✅ A guest gets an unfiltered `/stories`.
- ✅ `StoryPublicApi::listStories` and the profile stories tab return the trigger-warned story even when the caller's preference is on.
- ✅ `pnpm run gate` green.

---

## Phase 3 — Hide-from-lists on dashboard discover and story search

**Goal.** Apply the same `no_tw`-only rule to the dashboard's « à découvrir »
carousel and to story search results when the viewer is known.

Architecture: §1.1 (Search/Dashboard need no changes of their own), §3.1
(`searchStories` keeps its signature), §3.2; functional §4.3; decision #14.

Phase 1 left behind `App\Domains\Story\Private\Services\StoryPreferenceService`,
a container singleton with
`hidesStoriesWithTriggerWarnings(?int $userId = null): bool` — memoized per
user, `false` for guests, backed by the `stories` / `hide-stories-with-tw`
setting.

**Deliverables.**

- `app/Domains/Story/Private/Services/StoryService.php`, `getRandomStories()`
  (~line 562): inject `StoryPreferenceService`, resolve the preference for the
  **viewer id the method already receives** and forward it:

  ```php
  return $this->storiesRepository->getRandomStories(
      $userId, $nbStories, $visibilities,
      $this->preferences->hidesStoriesWithTriggerWarnings($userId),
  );
  ```

  `RandomStoriesComponent` (`Private/View/Components/RandomStoriesComponent.php`)
  is the only caller of this service method, so no other surface moves and the
  component itself needs no edit.

- `app/Domains/Story/Private/Repositories/StoryRepository.php`,
  `getRandomStories()` (~line 318): new trailing parameter
  `bool $noTwOnly = false`; when true, add
  `$query->where('tw_disclosure', Story::TW_NO_TW);` alongside the existing
  visibility / published-chapter / not-authored-by-viewer clauses.

- `app/Domains/Story/Private/Services/StorySearchService.php`, `search()`: new
  trailing parameter `bool $noTwOnly = false`; when true, add
  `$base->where('tw_disclosure', Story::TW_NO_TW);` **before**
  `$total = (int) $base->count('id');` so the returned `total` matches the rows
  that survive the filter.

- `app/Domains/Story/Public/Api/StoryPublicApi.php`, `searchStories()`
  (~line 187): signature stays
  `searchStories(string $query, ?int $viewerUserId = null, int $limit = 25)`
  (architecture §3.1). Inject `StoryPreferenceService` (deptrac already allows
  `StoryPublic → StoryPrivate`) and pass the resolved flag down:

  ```php
  $noTwOnly = $viewerUserId !== null
      && $this->preferences->hidesStoriesWithTriggerWarnings($viewerUserId);
  $result = $this->search->search($q, $cap, $noTwOnly);
  ```

- **No Search-domain change.** `SearchService::search()` already passes
  `$viewerId` into `StoryPublicApi::searchStories` (architecture §1.1).

**Tests.**

- `app/Domains/Story/Tests/Feature/Stories/Components/RandomStoriesComponentTest.php`
  (existing file — it renders `Blade::render('<x-story::random-stories-component />')`
  and flushes the cache in `beforeEach`), add:
  - `it('excludes trigger-warned stories from the discover carousel when the preference is on')`
  - `it('keeps them when the preference is off')`
- New `app/Domains/Story/Tests/Feature/Stories/StorySearchPreferenceFilterTest.php`:
  - `it('returns only no_tw stories to a viewer whose preference is on')` — via
    `StoryPublicApi::searchStories($q, $reader->id)`; assert on `items` **and**
    on `total` (the count must be filtered too)
  - `it('returns every matching story when the preference is off')`
  - `it('returns every matching story when no viewer id is given')` (guest path)
- `app/Domains/Search/Tests/Feature/SearchPartialTest.php` (existing file, already
  uses the Story test helpers `publicStory` etc.), add one route-level test:
  `it('hides trigger-warned stories from the search dropdown when the reader preference is on')`
  hitting `/search/partial?q=…`. Referencing `StoryServiceProvider::TAB_STORIES`
  from a Pest test file is fine — deptrac's collectors only see declared classes,
  and Pest files declare none (verified: `deptrac analyse` reports 0 violations
  today although `Story/Tests/.../ProfileCommentsPolicyTest.php` uses
  `SettingsPublicApi`, a pair the ruleset does not allow).

**Acceptance.**
- ✅ With the preference on, the discover carousel (`<x-story::random-stories-component />`) contains only `no_tw` stories; with it off, its current behaviour is unchanged (existing `RandomStoriesComponentTest` still green).
- ✅ With the preference on, `StoryPublicApi::searchStories($q, $viewerId)` returns only `no_tw` stories and a `total` consistent with them.
- ✅ `StoryPublicApi::searchStories($q, null)` (guest) is unfiltered.
- ✅ `GET /search/partial?q=…` hides trigger-warned stories for a reader whose preference is on and shows them for everyone else.
- ✅ `searchStories`' public signature is unchanged and the Search domain has no new dependency (`deptrac` clean, no ruleset edit).
- ✅ `pnpm run gate` green.

---

## Phase 4 — Hide-TW-display on the shared component and the story page

**Goal.** When the reader's « masquer les avertissements » preference is on, no
trigger-warning cue is rendered anywhere on reader surfaces — badges, the
`no_tw` marker and the `unspoiled` marker alike.

Architecture: §4 (Blade gate), decisions #3, #10, #12, assumption #5;
functional §4.2 and the a11y line in §6.

Phase 1 left behind `App\Domains\Story\Private\Services\StoryPreferenceService`,
a container singleton with `hidesTriggerWarnings(?int $userId = null): bool` —
memoized per user, `false` for guests, backed by the `stories` /
`hide-trigger-warnings` setting.

**Deliverables.**

- `app/Domains/Story/Private/Resources/views/components/trigger-warnings.blade.php`
  (anonymous component, 40 lines, three branches: `items` non-empty → badges,
  `disclosure === 'no_tw'`, `disclosure === 'unspoiled'`). After the `@props`
  block add

  ```blade
  @php($hideTw = app(\App\Domains\Story\Private\Services\StoryPreferenceService::class)->hidesTriggerWarnings())
  ```

  and wrap the whole `@if/@elseif/@elseif/@endif` chain in `@if(!$hideTw) … @endif`.
  Resolving a service with `app()` inside an anonymous component is the
  established pattern in this codebase (`media::image`, `statistics::stat-card`,
  `story::components.form`) — do not convert the component to a class.

  This one gate covers **every** surface that mounts it, with no prop-drill and
  no consumer wiring (decision #12): `story::components.card` — itself used by
  the library grid (`list-grid`), the discover carousel (`random-stories`),
  `keep-reading`, `keep-writing` and the profile stories tab — and ReadList's
  `read-list-card`. Both call sites sit in a `flex … justify-between` row, so
  rendering nothing simply collapses the right-hand side; no layout fix needed.
  **No ReadList file changes** (architecture §1.1).

- `app/Domains/Story/Private/Resources/views/show.blade.php` — the dedicated TW
  block (today ~lines 179–227: a `<div class="flex flex-col gap-2">` holding the
  « Avertissements » heading with its icon, then a `@switch($viewModel->twDisclosure)`
  producing the `no_tw` badge / the `unspoiled` badge / the `<x-story::ref-badge>`
  list). Resolve the same service once near the top of the file and wrap **the
  whole block, heading included**, in `@if(!$hideTw)`. Gating only the `@switch`
  would leave an « Avertissements » heading with nothing under it — the a11y
  rule in functional §6 ("removing TW UI must not leave empty labelled regions").
  The surrounding `flex flex-col justify-between` column keeps the status /
  feedback / copyright items below it and stays valid when empty.

- **Deliberately left ungated** (do not touch, and say so in the commit body):
  - the author create/edit TW picker in `components/form.blade.php` and the
    StoryRef admin catalog — configuration UIs, not reader spoilers (assumption #5);
  - the library filter panel in `index.blade.php` (the `exclude_tw` multi-select
    and the « Histoires sans avertissement » checkbox) — a filtering control that
    reveals no individual story's warnings (assumption #6, added by PLAN).

**Tests.** New `app/Domains/Story/Tests/Feature/Stories/HideTriggerWarningsDisplayTest.php`.
Fixture: a public story with `tw_disclosure = TW_LISTED` tagged with a reference
TW (`makeRefTriggerWarning('Violence')`, as in `ListStoriesTest`), plus one
`TW_NO_TW` and one `TW_UNSPOILED` story, each with a published chapter.

- `it('hides trigger warning names on story cards when the preference is on')` —
  `GET /stories` → `assertDontSee('Violence')` and
  `assertDontSee(__('story::shared.trigger_warnings.label'))`
- `it('hides the no_tw and unspoiled markers on story cards')` — the two tooltip
  strings `story::shared.trigger_warnings.tooltips.no_tw` / `.unspoiled` are absent
- `it('hides the trigger warnings block on the story page')` — for each of the
  three disclosures, `GET /stories/{slug}` →
  `assertDontSee(__('story::show.trigger_warnings.label'))` and no badge text
- `it('keeps trigger warnings everywhere when the preference is off')` — cards and show page
- `it('keeps trigger warnings for a guest')`
- `it('keeps the trigger warning picker on the author edit form')` — `GET /stories/{slug}/edit`
  as the author with the preference on still shows the TW section (assumption #5)
- `it('keeps the library trigger-warning filter panel')` — `GET /stories` with the
  preference on still shows `__('story::index.filters.no_tw_only.label')` (assumption #6)

And `app/Domains/ReadList/Tests/Feature/HideTriggerWarningsDisplayTest.php` (new,
next to the domain's existing feature tests):

- `it('hides trigger warning chrome on read-list cards when the preference is on')`
- `it('still lists trigger-warned stories on the read list')` — the pile is never
  filtered by the hide-from-lists preference (decision #13); set **both**
  preferences on and assert the story is still there, without its TW chrome.

Reference `StoryServiceProvider::TAB_STORIES` / `KEY_HIDE_TRIGGER_WARNINGS` from
that ReadList test rather than string literals; deptrac does not see Pest files
(verified — see phase 3's note).

**Acceptance.**
- ✅ With the preference on: `/stories`, the story page, and the read-list page contain no TW name, no `no_tw` marker and no `unspoiled` marker.
- ✅ With the preference on, the story page shows no orphan « Avertissements » heading.
- ✅ With the preference off, and for guests, every current TW rendering is unchanged (existing Story and ReadList suites green).
- ✅ The author edit form and the library TW filter panel still render with the preference on.
- ✅ No file under `app/Domains/ReadList/Private/` was modified.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Settings › Histoires (desktop) | The tab exists, sits after « Général », shows two toggles with readable French names and descriptions | |
| Settings › Histoires (mobile) | Tab reachable and toggles usable at 375 px | |
| Settings › Histoires | Toggling either preference saves without a page reload and survives a refresh | |
| Library `/stories` — hide-lists ON | Only « sans avertissement » stories listed; the « Histoires sans avertissement » checkbox is **unchecked**; filter panel intact | |
| Library `/stories` — hide-lists ON, no `no_tw` story matches | Empty state renders normally (no broken grid) | |
| Library `/stories` — hide-display ON | No warning icon anywhere in the card meta row; the chapters/words row keeps its alignment | |
| Library `/stories` — both OFF | Pixel-identical to production today | |
| Dashboard « à découvrir » — hide-lists ON | Carousel shows only `no_tw` stories, still 7 slots or fewer without layout break | |
| Story page (`listed`) — hide-display ON | No « Avertissements » heading, no badges; the right column (statut, retours, copyright) stays aligned | |
| Story page (`no_tw`) and (`unspoiled`) — hide-display ON | Same: no marker, no heading | |
| Story page — hide-lists ON, direct URL to a `listed` story | Page opens normally (functional §4.5) | |
| Read list (own pile) — both preferences ON | Trigger-warned stories still listed; no TW icons on the cards | |
| Read list — another user's pile | Same as above | |
| Profile › Histoires tab and « Continuer ma lecture » — hide-lists ON | Trigger-warned stories still listed (not discovery surfaces) | |
| Search dropdown (`/search/partial`) — hide-lists ON | Trigger-warned stories absent from story results; counts consistent | |
| Author story edit form — hide-display ON | The trigger-warning picker is still fully visible and usable | |
| Guest browsing library + a story page | Warnings visible, lists unfiltered — defaults, whatever any account has set | |
| Mobile (375 px) library + story page — hide-display ON | No leftover gap or stray separator where the TW icon used to be | |

## Open items

- **French copy for the two parameter descriptions** — assumption #3 allows
  tightening during BUILD; needed by **phase 1**. Not blocking: working titles
  are in functional §4.1.
- **Settings tab order `15`** — chosen so « Histoires » lands between « Général »
  (10) and « Pile à lire » (20). Cosmetic; **phase 1** may pick another value if
  the rendered order looks wrong, no other code depends on it.
- **Library TW filter panel stays ungated by hide-display** — arbitrated by PLAN
  and recorded as assumption #6 in `DECISIONS.md` (reversible). Affects
  **phase 4**; flag it in the WRAP summary.
- **Memoization vs. multi-request tests** — the container survives between two
  HTTP calls inside one test, so a test that flips a preference *between*
  requests must `app()->forgetInstance(StoryPreferenceService::class)` or split
  into two `it()` blocks. Affects **phases 2–4**; the caveat is written into the
  phase 1 test file.
- Everything else the plan asserts about existing code was read at PLAN time:
  `StoryRepository::searchStories` already honours `noTwOnly`;
  `StoryService::searchStories` is shared by the library controller,
  `ProfileStoriesComponent` and `StoryPublicApi::listStories`;
  `StoryService::getRandomStories` has a single caller;
  `<x-story::trigger-warnings>` is mounted from exactly two files
  (`story::components.card`, `read-list::read-list-card`); and
  `StoryPublic`/`StoryPrivate` → `SettingsPublic` are already allowed deptrac
  edges (`deptrac analyse`: 0 violations before any change).
