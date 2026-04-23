# Search Domain

Global search across stories and profiles, rendered as a live inline dropdown below the top bar. No database tables — all data is fetched via the public APIs of other domains.

## Responsibilities

- Expose a header search component usable on any page (desktop inline input + mobile popup panel)
- Accept a query via `GET /search/partial` and return a rendered Blade partial
- Delegate story lookup to `StoryPublicApi::searchStories()` and profile lookup to `ProfilePublicApi::searchPublicProfiles()`
- Apply query-term highlighting (accent-insensitive, case-insensitive) on story titles and profile display names
- Enforce story visibility rules transparently (guests see public only; confirmed users see public + community; collaborators see private)

## Domain Structure

```
app/Domains/Search/
  Private/
    Controllers/
      SearchController.php          # Single action: partial()
    Services/
      SearchService.php             # Orchestrates API calls, builds view model, highlights matches
    Resources/
      lang/fr/
        header.php                  # "Rechercher" label
        results.php                 # Tab labels, empty-state messages, pagination hint
      views/
        components/
          header-search.blade.php   # Anonymous Blade component: Alpine.js-driven search bar
        partials/
          search-results.blade.php  # Rendered HTML fragment returned by /search/partial
    routes.php
  Public/
    Providers/
      SearchServiceProvider.php     # Registers routes, translations, view namespace
  Tests/
    Feature/
      SearchPartialTest.php
```

## Key Files

- `Private/Services/SearchService.php` — core logic: calls both APIs, applies client-side-paging slicing, runs the `highlight()` method
- `Private/Controllers/SearchController.php` — thin controller, delegates entirely to `SearchService`
- `Private/Resources/views/components/header-search.blade.php` — Alpine.js `globalSearch()` component with debounced input, mobile/desktop dual layout, keyboard navigation (arrow keys + enter), and loading spinner
- `Private/Resources/views/partials/search-results.blade.php` — tabbed results (Histoires / Profils) with Alpine-driven client-side pagination (up to 25 results, 5 per page, max 5 pages)

## HTTP Endpoints

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/search/partial` | `search.partial` | Returns a rendered HTML fragment; consumed by the Alpine component via `fetch()` |

### Query Parameters

| Parameter | Default | Notes |
|-----------|---------|-------|
| `q` | — | Search query; ignored if fewer than 2 characters |
| `stories_page` | `1` | Current page for the stories tab (1-based) |
| `profiles_page` | `1` | Current page for the profiles tab (1-based) |
| `per_page` | `5` | Results per page; clamped to 1–5 |

## Dependencies (consumed Public APIs)

| Domain | Contract | Usage |
|--------|----------|-------|
| Story | `StoryPublicApi::searchStories(string $query, ?int $viewerUserId, int $limit)` | Fetches up to 25 matching stories; respects visibility for the authenticated viewer |
| Profile (via Shared) | `ProfilePublicApi::searchPublicProfiles(string $query, int $limit)` | Fetches up to 25 matching public profiles |

`SearchService` is injected with `StoryPublicApi` and `ProfilePublicApi` (the latter via the `Shared` contract interface).

## Search Behaviour

- Queries shorter than 2 characters return empty results without hitting the APIs.
- Story description matching is handled inside `StoryPublicApi` (activated at 4+ characters per the API implementation).
- Up to 25 items are fetched per category; the results partial paginates them client-side at 5 per page (up to 5 pages, i.e. 25 max displayed).
- Highlighting wraps matched substrings in `<mark>` tags. The algorithm normalises both text and query to ASCII (via `Str::ascii`) to match across accented characters, then applies the highlight to the original string. Output is HTML-escaped outside the `<mark>` tags.

## Frontend (Alpine.js Component)

The `header-search` anonymous component implements `globalSearch()`:

- **Desktop**: input visible inline in the header bar; dropdown appears below on focus/result.
- **Mobile**: only the search icon is shown; clicking it opens a popup panel with a dedicated input.
- Queries are debounced 300 ms (`x-model.debounce.300ms`).
- Results are fetched via `fetch('/search/partial?q=…')` with `X-Requested-With: XMLHttpRequest`.
- Fetched HTML is injected into the dropdown via `x-html`; Alpine re-initialises the injected tree with `Alpine.initTree`.
- Keyboard navigation: arrow-down / arrow-up move highlight across `li[role="option"]` items; Enter navigates to the highlighted result's URL.
- Escape closes the dropdown.

## Translations

All translation keys are under the `search::` namespace (French only, `lang/fr/`).

| Key | Value |
|-----|-------|
| `search::header.label` | Rechercher |
| `search::results.stories.label` | Histoires |
| `search::results.profiles.label` | Profils |
| `search::results.stories.empty` | Aucune histoire trouvée |
| `search::results.profiles.empty` | Aucun profil trouvé |
| `search::results.empty.label` | Aucun résultat |
| `search::results.empty.help` | Essayez une autre recherche, ou tapez 5 caractères pour chercher dans les descriptions des histoires |
| `search::results.page.label` | (25 résultats maximum) |

## Tests

`Tests/Feature/SearchPartialTest.php` covers:

- Empty result for queries under 2 characters
- Story visibility per user role (guest, confirmed non-collaborator, confirmed collaborator/author)
- Description search activated at 4+ characters
- Term highlighting with `<mark>` for both stories and profiles
- Profile search by display name
