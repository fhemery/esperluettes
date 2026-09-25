# Story preferences — trigger warnings

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-08-16 · **Domain(s):** `Story` (touches `ReadList`,
`Search`, `Settings` registrations)

## What it does

Adds a Settings tab « Histoires » with two reader booleans, both default `false`,
both available to `user` and `user-confirmed`:

- **`hide-trigger-warnings`** — no trigger-warning information is rendered on
  reader surfaces (names, the `no_tw` marker, the `unspoiled` marker).
- **`hide-stories-with-tw`** — discovery lists show only stories whose
  `tw_disclosure` is `no_tw` (so `listed` **and** `unspoiled` disappear).

Both are read through `StoryPreferenceService`, never through `SettingsPublicApi`
directly. Guests always get the defaults; there is no cookie-based preference.

## Key behaviour

- **Hide-from-lists applies to exactly three surfaces:** library `/stories`
  (`StoryController::index`), dashboard « à découvrir »
  (`StoryService::getRandomStories`), and story search
  (`StoryPublicApi::searchStories`, viewer id non-null — items *and* `total`).
- **Everything else is deliberately unfiltered:** read list, keep-reading,
  profile story tabs, `StoryPublicApi::listStories`, author pickers, admin,
  direct URL. A trigger-warned story opened by URL renders normally.
- **No author exemption.** With the preference on, a reader's own trigger-warned
  story is hidden from `/stories` too (assumption #1, reversible).
- **The library « Histoires sans avertissement » checkbox stays independent.**
  The preference ORs into the query (`$noTwOnly || $preference`) without ticking
  the checkbox back into the form or the pagination query string.
- **Hide-display is enforced in Blade, not in the DTOs.** DTOs still carry TW
  data; `<x-story::trigger-warnings>` gates itself, and `stories/show.blade.php`
  gates the whole TW block (heading included, so no empty labelled region stays
  behind). Covers the three existing TW reader surfaces: story card, read-list
  card, story page. No prop-drilling, no ReadList wiring.
- **Author-facing and filter-control surfaces are *not* gated:** the story
  create/edit TW picker, the StoryRef admin catalog, and the library
  « Exclure selon le contenu » filter panel (assumptions #5/#6).
- **`StoryPreferenceService` is a container singleton with a per-request memo**
  keyed `"{userId}:{key}"` — that is what makes a 12-card grid cost one Settings
  lookup, because Blade resolves it via `app(...)` per card.
  *Test consequence:* the container survives between two HTTP calls in one test,
  so flipping a preference **between** two requests requires
  `app()->forgetInstance(StoryPreferenceService::class)` or two `it()` blocks.

## Where the code lives

| Concern | Path |
|---------|------|
| Service (singleton + memo) | `app/Domains/Story/Private/Services/StoryPreferenceService.php` |
| Settings registration, constants (`TAB_STORIES`, `SECTION_READING`, `KEY_HIDE_TRIGGER_WARNINGS`, `KEY_HIDE_STORIES_WITH_TW`) | `app/Domains/Story/Public/Providers/StoryServiceProvider.php` (`register()` + `registerStoryPreferenceSettings()`) |
| Library filter | `app/Domains/Story/Private/Controllers/StoryController.php::index` |
| Discover filter | `StoryService::getRandomStories` → `StoryRepository::getRandomStories($noTwOnly)` |
| Search filter | `StoryPublicApi::searchStories` → `StorySearchService::search($q, $limit, $noTwOnly)` |
| Display gate | `Story/Private/Resources/views/components/trigger-warnings.blade.php`, `.../views/show.blade.php` |
| French copy | `app/Domains/Story/Private/Resources/lang/fr/settings.php` |
| Tests | `Story/Tests/Feature/Settings/StoryPreferencesSettingsTest.php`, `Story/Tests/Feature/Stories/{StoryListPreferenceFilterTest,StorySearchPreferenceFilterTest,HideTriggerWarningsDisplayTest}.php`, `Story/Tests/Feature/Stories/Components/RandomStoriesComponentTest.php`, `ReadList/Tests/Feature/HideTriggerWarningsDisplayTest.php`, `Search/Tests/Feature/SearchPartialTest.php` |
| Migrations | none — values live in the existing Settings tables |

## Extension points used

- **`SettingsPublicApi`** — new tab `stories` (order 15, icon `menu_book`),
  section `reading`, two `BOOL` parameters. Registered from a **separate**
  method inside the existing `app->booted()` closure: `registerSettings()`
  returns early once its own parameter exists, so appending to it would be
  silently skipped on a second boot (tests).
- No new deptrac edge — `StoryPublic`/`StoryPrivate` → `SettingsPublic` were
  already allowed. No events, notifications, statistics or moderation hooks.

## Decisions worth remembering

- « Has a TW » means *not* `no_tw`: `unspoiled` counts as warned and is hidden,
  matching the pre-existing library checkbox (decision #1).
- ReadList is intentionally never list-filtered — your own pile is a deliberate
  choice, and filtering it would empty it (decisions #13/#14, superseding #2/#9).
- The display gate lives in the shared component and reads the service itself
  (decision #12, superseding #11's prop-drill design). Adding a new TW surface
  gets the gate for free *only* if it mounts `<x-story::trigger-warnings>`;
  a bespoke TW rendering must gate itself, as `show.blade.php` does.

## Plan vs. code

The code matches `03-plan.md`'s four phases; all are `DONE` and no phase was cut.
The one design document that the code does **not** follow is decision #11
(resolve once in the controller and pass a prop) — superseded by #12 before BUILD.

## Not done

Deliberate non-goals: guest-persisted preferences, syncing/removing the library
checkbox, filtering ReadList / keep-reading / profile tabs / pickers / admin,
blocking direct URL access, author exemption, changing the TW disclosure model,
mature-audience gating, per-TW-id « exclude these warnings » preference, and any
notification / event / statistic / moderation flow.

Open, **not** pushed to `BACKLOG.md` — both need the user to arbitrate first:

- With hide-display ON, the library filter panel still renders the full TW
  catalog as readable labels (« Physical Violence », …). Accepted by assumption
  #6; reversing #6 fixes it.
- An empty library grid caused by the preference reuses the generic
  « Aucune histoire publique pour le moment. » copy — no hint that a preference
  caused it.

No e2e specs were written for this feature, so `e2e/tests/features/` has nothing
to retire.
