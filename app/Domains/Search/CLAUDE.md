# Search Domain — Agent Instructions

- README: [app/Domains/Search/README.md](README.md)

## Public API

This domain has no Public API class. It is a consumer-only domain; no other domain calls into it.

## Events emitted

This domain emits no events.

## Listens to

This domain registers no event listeners. It reads data synchronously through Public API calls at request time.

## Non-obvious invariants

- **Minimum query length is 2 characters.** `SearchService::buildViewModel()` returns empty arrays immediately for queries shorter than 2 characters, without calling either API. The Alpine component also skips `fetchResults()` when the trimmed query length is below 2.

- **The highlight algorithm works on ASCII-normalised positions, not byte offsets.** `Str::ascii()` is applied to both the text and the query to locate match offsets; the `<mark>` tags are then applied to slices of the *original* (non-normalised) string. Do not replace this with a pure regex on the original text — it would fail on accented characters.

- **Client-side pagination is done server-side in the view model.** `SearchService` fetches all results (up to 25) in a single API call, then slices them per page. The Blade partial receives the full `items` array and uses `x-show="Math.floor(index / perPage) + 1 === page"` for display. There is no second HTTP request when the user pages through results.

- **Story visibility is enforced by `StoryPublicApi`, not by Search.** `SearchService` passes `Auth::id()` (or `null` for guests) to `StoryPublicApi::searchStories()`. The API applies visibility rules; Search must not re-filter the returned results.

- **View namespace is `search::`.** Routes, view references, and translations all use the `search::` namespace registered in `SearchServiceProvider`. The anonymous component path is also registered under `search`, making the header component available as `<x-search::components.header-search />` (or `<x-search::header-search />`).

- **The `per_page` parameter is clamped to 1–5 in the controller.** The client sends `per_page=5` by default. Do not allow values above 5; results beyond 25 are never fetched from the APIs.

- **Alpine re-initialises injected HTML.** After `x-html` injects the fetched partial, `Alpine.initTree(this.$refs.dropdown)` is called to activate any Alpine directives in the new HTML. If the partial ever uses Alpine components, this call is required and must not be removed.
