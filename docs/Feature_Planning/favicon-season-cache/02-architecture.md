# Favicon — stays the same when season changes — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Shared** owns seasonal themes (`Theme` enum, `ThemeService`, layout head).
Favicon `<link>`s already live in `Shared/Resources/views/layouts/partials/head.blade.php`
and resolve via `$theme->asset(...)`. No other domain is involved.

### 1.1 Changes in other domains

None.

## 2. Data model

### 2.1 Tables

None.

### 2.2 Model

None.

### 2.3 Lifecycle rules

N/A — render-time URLs only.

## 3. PHP architecture

### 3.1 Public API

No new public API. Existing `Theme::asset(string $path): string` keeps
returning a clean asset URL for a path **without** a query string. Callers
that need cache-busting append the query themselves.

### 3.2 Services

No service changes. Theme resolution (`ThemeService` / middleware) unchanged.

### 3.3 Policy / authorization

N/A.

### 3.4 Events and listeners

N/A.

### 3.5 Routes, controllers, form requests

N/A.

## 4. Frontend architecture

Favicon `<link>` `href`s in the shared head partial: each uses
`$theme->asset('favicons/…')` for the file path, then appends
`?theme={{ $theme->value }}` so the full URL differs per resolved season.

Replace the hardcoded `?v=20260425` (today embedded inside the string passed
to `asset()`) — do not keep both params. Prefer appending the query **outside**
`asset()` so Laravel's `asset()` helper never receives a query fragment in the
path argument.

No Alpine/JS. No Vite change — favicons remain static files under
`public/images/themes/{season}/favicons/`.

## 5. Deptrac

No new edges.

## 6. Testing strategy

| Level | What |
|-------|------|
| Feature (Shared) | Authenticated (or guest) response HTML contains favicon `href`s ending with `?theme=<resolved>` for each linked size; with a fixed theme preference (or `Carbon::setTestNow` for seasonal), assert the season value matches. |
| Unit | Not needed — no new PHP logic. |
| Vitest | N/A. |
| VERIFY | Optional visual check that switching theme in settings and reloading shows a different tab icon; browsers cache aggressively so the HTML assertion is the reliable gate. |

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where to put the season query? | A) Append in Blade outside `asset()` · B) Extend `Theme::asset()` to always add `?theme=` · C) Keep query inside the path string passed to `asset()` (current `?v=` style) | **A** | Minimum surface; keeps `asset()` a pure path→URL helper; avoids embedding queries in the path argument |
| 2 | Keep `?v=20260425`? | A) Replace with `?theme=` · B) Keep both | **A** | Season value already changes whenever cache must bust for this bug; static date adds nothing |
| 3 | Touch logos / other theme images? | A) Favicons only · B) All theme assets | **A** | Spec scope; logos already change path by season |

## 8. File layout

No new classes. Edits stay in Shared layout + a Shared feature test under the
existing `Tests/Feature` tree.

## 9. Risks acknowledged

| Risk | When to revisit |
|------|-----------------|
| Browsers may still request bare `/favicon.ico` (root copy = autumn) ignoring HTML links | Users still report wrong icon after HTML is correct → update or redirect root copies |
| Some browsers cache favicons harder than query params alone can bust | If `?theme=` proves insufficient in the wild → rename files or serve with stronger `Cache-Control` |
| Theme preference change without full navigation may not refresh the tab icon mid-SPA | N/A today (full page loads); revisit if the app goes SPA |
