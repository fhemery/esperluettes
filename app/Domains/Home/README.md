# Home Domain

## Purpose and scope

The Home domain owns the public-facing landing page of the site (`/`). Its sole responsibility is to serve a welcoming page to unauthenticated (guest) visitors and redirect authenticated users away to their dashboard. It has no database tables of its own and holds no persistent state.

This domain is intentionally thin. Any data aggregation from other domains (news carousel, featured stories, etc.) that may be added to the home page in the future should be sourced via those domains' Public APIs rather than implemented here.

## Key concepts

**Guest-only page** — The home page is only rendered for unauthenticated visitors. Authenticated users are immediately redirected to the `dashboard` route. This distinction is enforced in the controller, not via middleware, so the route itself is accessible to all.

**Seasonal background** — The view uses the `seasonal-background="true"` attribute on the app layout, which triggers a themed background managed by the Shared domain. This is a presentational concern only; the Home domain does not own or control seasonal theming.

**Welcome copy** — The welcome message is stored in a namespaced language file (`home::index.welcome-message`) and contains raw HTML rendered via `{!! !!}`. This is intentional for typographic flexibility but means changes to the welcome text must be made in the translation file, not the view.

## Architecture decisions

**No Public API class** — The Home domain exposes nothing to other domains. It is a pure consumer. No other domain should depend on the Home domain.

**No cross-domain data aggregation yet** — The current home page shows only static welcome copy. The original README noted that "the page will go fetch some info from several other modules" — this is planned but not yet implemented. When aggregation is added, it must go through the relevant domain's Public API (e.g. `NewsPublicApi::getPinnedForCarousel()`).

**Redirect logic in controller, not middleware** — The authenticated redirect happens inside `HomeController::index()` rather than as route middleware. This keeps the route publicly reachable for SEO and avoids a dedicated guest-only middleware group for a single route.

## Cross-domain delegation map

| Concern | Delegated to | How |
|---------|-------------|-----|
| App layout / seasonal background | Shared | `<x-app-layout seasonal-background="true">` |
| Button component | Shared | `<x-shared::button>` |
| Authenticated redirect destination | Dashboard | `route('dashboard')` |
| (Planned) news carousel data | News | `NewsPublicApi` (not yet implemented) |
| (Planned) featured story data | Story | `StoryPublicApi` (not yet implemented) |
