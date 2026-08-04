# Shared Domain

Cross-cutting infrastructure used by every other domain: layouts, Blade components, JavaScript modules, translations, PHP utilities, and shared contracts. It has no database tables of its own (the migrations it owns are for Laravel framework infrastructure: cache, jobs).

---

## Directory structure

```
Shared/
  Contracts/          # Public interfaces and enums consumed cross-domain
  Controllers/        # Base controller
  Database/
    Migrations/       # Framework tables (cache, jobs) — not domain business tables
  Dto/                # Shared DTOs (profile representations)
  Helpers/            # Miscellaneous helpers (VersionHelper)
  Http/               # HTTP utility classes (BackToCommentsRedirector)
  Resources/
    css/              # app.css — site-wide styles, incl. read-side rich-content rules
    js/               # JS entrypoints and modules
      anchoring/      # Read-side quote anchoring (canonical text, extract, re-anchor)
    lang/fr/          # French translations shared across domains
    views/
      components/     # Anonymous Blade components (UI primitives)
      errors/         # Custom error pages (404, 419, 500)
      layouts/        # Application layouts (app.blade.php, guest.blade.php)
        partials/     # Layout partials (head, navigation)
  Services/           # Shared services (appearance, theme, fonts, interline)
  Support/            # Pure PHP utility classes (text, slugs, sorting, SEO)
  Validation/         # Custom validators and validation rules
  ViewModels/         # View model classes (breadcrumbs, page, SEO, ref)
  Views/
    Components/       # Class-based Blade components (BreadcrumbsComponent)
    Layouts/          # PHP layout component classes (AppLayout)
    vendor/           # Overridden vendor views (notifications email)
  Tests/
    Feature/          # Feature tests (header, footer, navigation, breadcrumbs, 404)
    Unit/             # Unit tests (WordCounter, NumberFormatter, SparseReorder, Theme)
```

---

## Layouts

### `AppLayout` (class-based component)

`App\Domains\Shared\Views\Layouts\AppLayout` switches between two Blade layouts depending on authentication:

- **Authenticated** — `shared::layouts.app` — full application chrome with navigation bar, breadcrumbs, footer, session heartbeat, and CSRF refresh logic.
- **Guest** — `shared::layouts.guest` — minimal layout without navigation.

Props:
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `page` | `?PageViewModel` | `null` | Page-level metadata (title, SEO, breadcrumbs, seasonal flags) |
| `size` | `string` | `'lg'` | Content max-width: `'sm'` (max-w-2xl), `'md'` (max-w-4xl), `'lg'` (max-w-7xl) |

Usage in a Blade view:
```blade
<x-shared::app-layout :page="$page">
    ...page content...
</x-shared::app-layout>
```

The authenticated layout injects a heartbeat every 5 minutes and refreshes the CSRF token on tab focus, window focus, and network reconnection.

---

## View models

### `PageViewModel`

Immutable, fluent builder passed to `AppLayout`. Chains with `with*` methods:

```php
$page = PageViewModel::make()
    ->withTitle('Mon titre')
    ->withSeo(new SeoViewModel('Mon titre', '/images/cover.jpg'))
    ->withBreadcrumbs($breadcrumbs)
    ->withSeasonalBackground(true)
    ->withSeasonalRibbon(true);
```

### `BreadcrumbViewModel`

Fluent trail builder. Start from the home link, then push additional steps:

```php
$breadcrumbs = BreadcrumbViewModel::FromHome(Auth::check())
    ->push('Histoires', route('story.index'))
    ->push($story->title, null, true); // active (no link)
```

### `SeoViewModel`

Minimal SEO data: `title` and `coverImage`. Feed to `PageViewModel::withSeo()`.

### `RefViewModel`

Generic name/description pair used by reference-data domains to pass labelled options to Blade without coupling to their own models.

---

## Contracts (public interfaces and enums)

### `ProfilePublicApi` interface

Cross-domain interface for reading profile data without coupling to the Profile domain model. Implemented by `Profile` domain, bound in its service provider.

Key methods:
| Method | Returns | Description |
|--------|---------|-------------|
| `getPublicProfile(int $userId)` | `?ProfileDto` | Minimal profile for display |
| `getPublicProfileBySlug(string $slug)` | `?ProfileDto` | Lookup by profile slug |
| `getFullProfile(int $userId)` | `?FullProfileDto` | Full profile including roles and join date |
| `getPublicProfiles(array $userIds)` | `array` | Batch fetch keyed by user ID |
| `searchDisplayNames(string $query, ...)` | `array` | `[user_id => display_name]` |
| `searchPublicProfiles(string $query, ...)` | `array` | Search results with total count |
| `canViewComments(int $profileUserId, ...)` | `bool` | Comment-section visibility check |

### `Sortable` interface

Implemented by any Eloquent model that needs `SparseReorder`. Three required methods: `getId()`, `getSortOrder()`, `setSortOrder(int)`.

### `ParameterType` enum

Backed string enum shared by `Config` (admin toggles) and `Settings` (user preferences). Values: `INT`, `STRING`, `BOOL`, `TIME`, `ENUM`, `RANGE`, `MULTI_SELECT`. Provides `cast()` and `serialize()` for storage round-trips.

### `Theme` enum

Backed string enum: `WINTER`, `SPRING`, `SUMMER`, `AUTUMN`. `Theme::seasonal()` computes the current season from the system clock (astronomical season boundaries). Provides asset path helpers: `logo()`, `logoFull()`, `ribbon()`, `asset($path)`.

---

## DTOs

| DTO | Fields | Typical use |
|-----|--------|-------------|
| `ProfileDto` | `user_id`, `display_name`, `slug`, `avatar_url` | Compact profile for cards and author lines |
| `FullProfileDto` | `userId`, `displayName`, `slug`, `avatarUrl`, `joinDateIso`, `roles[]` | Profile page header |
| `ProfileSearchResultDto` | `user_id`, `display_name`, `slug`, `avatar_url`, `url` | Global search results |

---

## Support utilities (pure PHP)

| Class | Key method(s) | Purpose |
|-------|---------------|---------|
| `WordCounter` | `count(string $htmlOrText): int` | Strips HTML, decodes entities, splits on Unicode non-letter/digit boundaries. Hyphens and apostrophes are separators. |
| `CharacterCounter` | `count(string $htmlOrText): int` | Strips HTML, decodes entities, returns `mb_strlen`. |
| `NumberFormatter` | `compact(int\|float, ?string $locale): string` | Floor-rounded compact notation: `1151 → 1,1k` (fr) / `1.1k` (en), `1_000_001 → 1M`. |
| `SparseReorder` | `computeChanges(Sortable[], int[], int $step): array` | Reorders sortable items using midpoint strategy; falls back to full rebalance with $step when no room. Returns only the changed `[id => newOrder]` pairs. |
| `SlugWithId` | `build(string, int): string`, `extractId(string): ?int`, `isCanonical(string, string): bool` | Canonical slug format `{base}-{id}` used by Story and Chapter routes. |
| `SimpleSlug` | `normalize(string): string` | Profile slug: lowercased, non-alnum replaced with dashes. |
| `Seo` | `excerpt(?string $html, int $max = 160): string` | Strips HTML, collapses whitespace, truncates at word boundary for meta descriptions. |

---

## Validation

### `CustomValidators`

Registers three custom Laravel validation rules (called once from a service provider):

| Rule | Parameters | Behavior |
|------|------------|---------|
| `maxstripped:<max>[,<profile>]` | max character count; optional HTMLPurifier profile | Fails if stripped plain text exceeds max. Uses HTMLPurifier. Replacer exposes `:max`. |
| `minstripped:<min>[,<profile>]` | min character count | Fails if stripped plain text is below min. Newlines excluded from count. |
| `required_trimmed` | — | Fails if value is null or whitespace-only after trim. |

### `UniqueProfileDisplayName` (ValidationRule)

Checks that a display name produces a unique profile slug. Accepts an optional `$ignoreUserId` for update scenarios. Resolves `ProfilePublicApi` from the container.

---

## JavaScript modules

| File | Exported / Global | Purpose |
|------|-------------------|---------|
| `app.js` | Alpine, window globals | Main entrypoint. Boots Alpine, registers plugins (`intersect`), mounts `popover` store, spoiler reveal delegate. |
| `tooltip.js` | `registerTooltip(Alpine)` | Alpine `popover` data component: hover + click-to-pin, viewport-aware positioning (right/left/top/bottom with fallback), exclusive single-open via Alpine store. |
| `countdown-timer.js` | `window.countdownTimer` | Alpine-compatible countdown timer. Reads `data-end-time`, `data-show-seconds`, and translation keys from element dataset. |
| `badge-overflow.js` | `window.BadgeOverflow` | Detects overflowing badge lists and shows a `+N` overflow indicator. |
| `date-utils.js` | `window.DateUtils` | Date formatting utilities. |
| `bootstrap.js` | — | Axios setup, CSRF header. |
| `anchoring/canonical-text.js` | `buildCanonicalText(rootEl)` | Normalised text extraction from rendered content. |
| `anchoring/extract-anchor.js` | `extractAnchor(range, rootEl, canonicalText)` | Builds a quote anchor (prefix / highlighted / suffix) from a selection. |
| `anchoring/reanchor.js` | `findAnchor(canonicalText, anchor)` | Re-locates a stored anchor in edited text. |
| `anchoring/block-elements.js` | `isBlockElement(node)`, `closestBlock(node)` | Tells whether a selection crosses two blocks, so the caller can refuse it. Deliberately a *narrower* block definition than `canonical-text.js` — see note below. |

The rich-text editor bundle lives in the **Editor** domain
([app/Domains/Editor/README.md](../Editor/README.md)), not here.

`Resources/js/anchoring/` **stays in Shared** and is not editor code: canonical
text, anchor extraction and re-anchoring are *read-side* concerns, consumed by
Quote (and later annotations) on rendered pages that load no editor at all.
Different consumers, different lifecycle — do not move it into `Editor`.

**Two definitions of "block" live side by side in this folder, on purpose.**
`canonical-text.js`'s `BLOCK_TAGS` includes `DIV`, because any block-level
wrapper should contribute a synthetic space to the extracted text. `block-elements.js`'s
`isBlockElement()` is narrower — it excludes bare `DIV`s and only treats
`div.ce-block` (the block-editor wrapper) as a block — because its job is
different: deciding whether a text selection spans two blocks, so the caller
(currently Quote's mini quote form) can refuse it. A generic decorative `DIV`
should not split a selection into two blocks; the block-editor's own block
wrapper should. Keep the two sets aligned in intent, not in content.

---

## Blade components (anonymous)

Located in `Resources/views/components/`. Referenced as `<x-shared::component-name>`.

**Layout / chrome**
- `flash-block` — session flash messages
- `footer` — site footer
- `nav-link`, `responsive-nav-link`, `dropdown`, `dropdown-link` — navigation primitives
- `breadcrumbs`, `breadcrumbs-empty` — breadcrumb trail (uses `BreadcrumbsComponent` class for wiring)
- `title` — page `<h1>` title block

**Form inputs**
- `text-input`, `input-label`, `input-error` — standard text field, label, error display
- `select`, `select-with-tooltips` — select boxes
- `searchable-multi-select` — Alpine-powered multi-select with search
- `toggle` — boolean toggle switch
- `button`, `secondary-button`, `danger-button` — button variants

**Fields (Settings/Config plug-in system)**
- `fields/bool-field`, `fields/int-field`, `fields/string-field`
- `fields/time-field`, `fields/range-field`, `fields/multi-select-field`

**Uploads**
- `sound-upload` — the only upload widget left here. Images are handled by the Media domain's `<x-media::image-field>`, which owns paths, variants and garbage collection; there is no `image-upload` component or lang file anymore.

**UI primitives**
- `badge`, `badge-overflow`, `metric-badge` — badge display
- `avatar` — user avatar
- `modal`, `confirm-modal`, `drawer` — overlay/dialog patterns. `confirm-modal` always forwards `focusable` to `modal`, so opening a confirmation moves keyboard focus into the dialog (same as Auth / Quote Contest direct modals).
- `tabs` — tab strip with `role="tablist"` / `role="tab"`, arrow-key navigation, optional `id` prop (default `tabs`) used as the prefix for `id="{id}-tab-{key}"` and `aria-controls="{id}-panel-{key}"` on each tab button. Panels stay in the consumer slot: each panel root must set `role="tabpanel"`, `id="{id}-panel-{key}"`, and `aria-labelledby="{id}-tab-{key}"` (keep Alpine `x-show`, do not switch to `x-if`).
- `popover`, `tooltip` — popover/tooltip (backed by `tooltip.js` Alpine component). The trigger is keyboard-reachable (`tabindex="0"`), opens/closes on Enter and Space exactly like a click, closes on Escape, and exposes `aria-expanded` for assistive tech.
- `progress` — progress bar
- `pagination` — paginator
- `read-toggle`, `read-toggle-script` — reading progress toggle
- `default-cover` — placeholder cover art
- `design-icon` — SVG icon wrapper
- `themed-logo` — seasonal-aware logo image
- `auth-session-status` — login flash status

---

## Translations (French)

Located in `Resources/lang/fr/`. Referenced with the `shared::` namespace.

| File | Keys |
|------|------|
| `actions.php` | `back`, `cancel`, `save` |
| `breadcrumbs.php` | `breadcrumb` (aria label) |
| `errors.php` | Error page messages |
| `fields.php` | Generic field labels for Settings/Config fields |
| `footer.php` | Footer text |
| `validation.php` | Custom validator messages (`maxstripped`, `minstripped`, `unique_profile_display_name`, etc.) |

---

## HTTP utilities

### `BackToCommentsRedirector`

Reconstructs a `#comments`-anchored relative URL from the previous page. Used by comment-posting controllers to redirect back to the comments section after form submission (browsers do not transmit the fragment in `Referer`).

```php
return redirect(BackToCommentsRedirector::build());
```

### `VersionHelper`

Reads `version.json` from the project root and caches the version string for 1 hour. Returns `null` if the file is absent or the version is `"unknown"`. Displayed in the site footer.

---

## Technical notes

### Theme system

`Theme::seasonal()` determines the active visual theme (winter/spring/summer/autumn) by astronomical season. The current theme is injected into `<html data-season="...">` by the layout, and Tailwind classes prefixed `bg-seasonal`, `bg-theme-ribbon`, `text-fg`, `bg-bg` etc. are resolved by a CSS theming layer that reads the data attribute.

### Session and CSRF management

The authenticated layout includes inline JS that:
- Sends a heartbeat `GET` to `session.heartbeat` every 5 minutes to keep the session alive.
- Refreshes the CSRF token (via `session.csrf`) on tab visibility change, window focus, and network reconnect.
- Updates all `<input name="_token">` elements and the Axios default header on refresh.

### `SparseReorder` algorithm

Attempts to minimise DB writes when reordering. For each item in the new order, it checks whether the existing `sort_order` already fits strictly between its new neighbours. Only items that must move are included in the returned change map. If any slot has no integer room (left >= right - 1), the algorithm falls back to a full sequential rebalance using `$step` (default 100).

### Read-side styling of stored rich content

`Resources/css/app.css` keeps the rules that style **stored** content on pages that never load an editor: `.rich-content` typography, the `.ql-align-*` classes, the `.ql-custom-emoji*` family and the read-only spoiler variants. The editing chrome (toolbar, tooltip, `.ql-editor` surface) lives in the Editor domain's own stylesheet — see [app/Domains/Editor/README.md](../Editor/README.md). When adding a Quill-related rule, say in a comment which of the two sides it belongs to.

### Quill spoiler format

Spoilers are stored in the database as `<span class="ql-spoiler">`. The `app.js` global click delegate reveals them on click (adds `ql-spoiler--revealed` class). HTMLPurifier must be configured to allow `span.ql-spoiler` in its whitelist.
