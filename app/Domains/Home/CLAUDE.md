# Home Domain — Agent Instructions

- README: [app/Domains/Home/README.md](README.md)

## Public API

This domain has no Public API. It exposes nothing to other domains and no other domain should depend on it.

## Events emitted

None. The Home domain emits no domain events.

## Listens to

None. The Home domain has no cross-domain event listeners.

## Non-obvious invariants

- The home page route (`/`) is open to all (no auth middleware), but authenticated users are redirected to `dashboard` inside the controller. Do not add `auth` or `guest` middleware to this route — the controller handles the branching intentionally to keep the route publicly accessible.
- The welcome message (`home::index.welcome-message`) contains raw HTML and is rendered with `{!! !!}`. Any new language keys added to the home page view must be audited for XSS if they ever accept dynamic content.
- There is no `HomePublicApi` class and none should be created unless another domain genuinely needs to depend on this domain, which is architecturally discouraged.
