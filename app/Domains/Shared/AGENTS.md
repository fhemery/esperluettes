# Shared Domain — Agent Instructions

- README: [app/Domains/Shared/README.md](README.md)

## Public contracts

| Contract | Type | Description |
|----------|------|-------------|
| `Contracts/ProfilePublicApi` | interface | Read profile data cross-domain; implemented and bound by Profile domain |
| `Contracts/Sortable` | interface | Implement on models to enable `SparseReorder` |
| `Contracts/ParameterType` | enum | Shared type system for Config and Settings parameters |
| `Contracts/Theme` | enum | Seasonal theme (WINTER/SPRING/SUMMER/AUTUMN) with asset path helpers |

## Key files

| File | Purpose |
|------|---------|
| `Views/Layouts/AppLayout.php` | Main layout component; switches between `layouts.app` (auth) and `layouts.guest` |
| `ViewModels/PageViewModel.php` | Immutable fluent builder for page-level metadata (title, SEO, breadcrumbs, seasonal flags) |
| `ViewModels/BreadcrumbViewModel.php` | Breadcrumb trail builder; start with `BreadcrumbViewModel::FromHome(Auth::check())` |
| `Support/WordCounter.php` | Unicode-aware word count on HTML or plain text |
| `Support/CharacterCounter.php` | Unicode-aware character count on HTML or plain text |
| `Support/NumberFormatter.php` | Compact locale-aware number notation (1151 → 1,1k) |
| `Support/SparseReorder.php` | Minimal-write sort order computation for drag-and-drop reordering |
| `Support/SlugWithId.php` | Canonical `{base}-{id}` slug format used by Story and Chapter routes |
| `Support/SimpleSlug.php` | Profile slug normalisation (lowercase, dashes) |
| `Support/Seo.php` | Strip-and-truncate for meta description excerpts |
| `Services/ImageService.php` | Responsive image processing (variants at multiple widths; square crop for avatars) |
| `Validation/CustomValidators.php` | Registers `maxstripped`, `minstripped`, `required_trimmed` rules |
| `Validation/Rules/UniqueProfileDisplayName.php` | Cross-domain display name uniqueness rule |
| `Http/BackToCommentsRedirector.php` | Reconstruct `#comments` redirect after comment post |
| `Helpers/VersionHelper.php` | Read and cache `version.json` for display in footer |
| `Resources/js/editor-bundle.js` | Quill editor factory (`window.initQuillEditor`) |
| `Resources/js/tooltip.js` | Alpine `popover` component with viewport-aware positioning |

## Non-obvious rules

**Slug format is domain-specific.** `SlugWithId` is for Story/Chapter (format `{base}-{id}`). `SimpleSlug` is for Profile display names. Do not mix them.

**`SparseReorder` requires the `Sortable` contract.** Models must implement `getId()`, `getSortOrder()`, and `setSortOrder()`. Pass all items and all IDs — partial arrays throw `InvalidArgumentException`.

**`ParameterType` is the single source of truth for cast/serialize.** Both Config and Settings use it; do not add casting logic elsewhere.

**`Theme::seasonal()` uses the system clock.** It can be frozen in tests via `Carbon::setTestNow()`.

**The layout applies two extra Blade attributes.** `AppLayout` passes `seasonal-background` and `display-ribbon` as boolean attributes to `layouts.app`; they must be passed via `PageViewModel` flags (`withSeasonalBackground`, `withSeasonalRibbon`).

**`initQuillEditor` is idempotent.** It checks `container.dataset.quillInited` and skips if already initialised. Always call it by the container's `id`.

**Quill images are always blocked.** The editor-bundle drops pasted and dropped images at the Quill level. Do not attempt to add image upload support through the Quill toolbar.

**Custom validator registration.** `CustomValidators::register()` must be called from a service provider `boot()` method. It is not auto-registered.

**`BackToCommentsRedirector` only uses the path and query string.** Browsers never send the fragment in the `Referer` header; the `#comments` anchor is always appended by the helper, not read from the request.

**`ImageService` generates both JPG and WebP for each requested width.** Variant filenames follow the pattern `{original_name}-{width}w.jpg` / `.webp`. `deleteWithVariants` uses a regex to match and delete all of them.
