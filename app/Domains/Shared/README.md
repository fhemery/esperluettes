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
    js/               # JS entrypoints and modules
    lang/fr/          # French translations shared across domains
    views/
      components/     # Anonymous Blade components (UI primitives)
      errors/         # Custom error pages (404, 419, 500)
      layouts/        # Application layouts (app.blade.php, guest.blade.php)
        partials/     # Layout partials (head, navigation)
  Services/           # Shared services (ImageService)
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

## Services

### `ImageService`

Processes uploaded images and generates responsive variants.

| Method | Description |
|--------|-------------|
| `process(string $disk, string $folder, UploadedFile\|string, int[] $widths, ?string $ext): string` | Save original + generate JPG and WebP variants at each requested width (e.g. `400w.jpg`, `400w.webp`). Returns relative path of original. |
| `deleteWithVariants(string $disk, ?string $originalPath): void` | Deletes original and all `{name}-{width}w.{ext}` variants from storage. |
| `saveSquareJpg(string $disk, string $targetPath, ..., int $size, int $quality): string` | Cover-crops to a square JPEG. Used for avatars. |

Depends on `Intervention\Image` (via `Image` facade).

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
| `editor-bundle.js` | `window.initQuillEditor`, `window.Quill`, `window.Delta` | Quill rich-text editor factory. Configurable per-instance: headings, links, spoiler format, min/max character counting, image paste blocking, custom emoji blots. |
| `tooltip.js` | `registerTooltip(Alpine)` | Alpine `popover` data component: hover + click-to-pin, viewport-aware positioning (right/left/top/bottom with fallback), exclusive single-open via Alpine store. |
| `countdown-timer.js` | `window.countdownTimer` | Alpine-compatible countdown timer. Reads `data-end-time`, `data-show-seconds`, and translation keys from element dataset. |
| `badge-overflow.js` | `window.BadgeOverflow` | Detects overflowing badge lists and shows a `+N` overflow indicator. |
| `date-utils.js` | `window.DateUtils` | Date formatting utilities. |
| `bootstrap.js` | — | Axios setup, CSRF header. |

### Quill editor

The rich-text editor is Quill (installed as an npm package, not CDN). The `editor-bundle.js` entry exposes `window.initQuillEditor(id, options)`. Features enabled per-instance via `data-*` attributes or the `options` object:

- `data-with-headings="true"` — adds H2/H3 toolbar buttons
- `data-with-links="true"` — adds link toolbar button
- `data-with-spoiler="true"` — adds spoiler format (custom inline blot, renders as `<span class="ql-spoiler">`)
- `data-max` / `data-min` — character limits, wired to a counter display and `editor-valid` custom event
- `data-nb-lines` — sets editor height in lines
- `data-resizable="true"` — makes the editor vertically resizable

Images are blocked on paste and drop.

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

**UI primitives**
- `badge`, `badge-overflow`, `metric-badge` — badge display
- `avatar` — user avatar
- `modal`, `confirm-modal`, `drawer` — overlay/dialog patterns
- `popover`, `tooltip` — popover/tooltip (backed by `tooltip.js` Alpine component)
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

### Quill spoiler format

Spoilers are stored in the database as `<span class="ql-spoiler">`. The `app.js` global click delegate reveals them on click (adds `ql-spoiler--revealed` class). HTMLPurifier must be configured to allow `span.ql-spoiler` in its whitelist.
