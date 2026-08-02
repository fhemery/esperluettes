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
| `Validation/CustomValidators.php` | Registers `maxstripped`, `minstripped`, `required_trimmed` rules |
| `Validation/Rules/UniqueProfileDisplayName.php` | Cross-domain display name uniqueness rule |
| `Http/BackToCommentsRedirector.php` | Reconstruct `#comments` redirect after comment post |
| `Helpers/VersionHelper.php` | Read and cache `version.json` for display in footer |
| `Resources/js/tooltip.js` | Alpine `popover` component with viewport-aware positioning and keyboard activation |
| `Resources/js/anchoring/` | Read-side quote anchoring: `buildCanonicalText`, `extractAnchor`, `findAnchor`, `isBlockElement`/`closestBlock` |
| `Resources/css/app.css` | Site-wide styles, including the read-side rules for stored rich content |

## Non-obvious rules

**Slug format is domain-specific.** `SlugWithId` is for Story/Chapter (format `{base}-{id}`). `SimpleSlug` is for Profile display names. Do not mix them.

**`SparseReorder` requires the `Sortable` contract.** Models must implement `getId()`, `getSortOrder()`, and `setSortOrder()`. Pass all items and all IDs — partial arrays throw `InvalidArgumentException`.

**`ParameterType` is the single source of truth for cast/serialize.** Both Config and Settings use it; do not add casting logic elsewhere.

**`Theme::seasonal()` uses the system clock.** It can be frozen in tests via `Carbon::setTestNow()`.

**The layout applies two extra Blade attributes.** `AppLayout` passes `seasonal-background` and `display-ribbon` as boolean attributes to `layouts.app`; they must be passed via `PageViewModel` flags (`withSeasonalBackground`, `withSeasonalRibbon`).

**Custom validator registration.** `CustomValidators::register()` must be called from a service provider `boot()` method. It is not auto-registered.

**The editor is not here — but the read side is.** The Quill bundle, the editor chrome CSS and the `<x-editor::…>` components belong to the `Editor` domain. What stays in Shared is everything a page needs to *display* stored content without an editor: the `.rich-content` / `.ql-align-*` / `.ql-custom-emoji*` / read-only spoiler rules in `Resources/css/app.css`, and the `app.js` spoiler-reveal delegate. Moving one of those to Editor silently breaks read-only pages, since they load no editor asset.

**`Resources/js/anchoring/` stays in Shared.** Canonical text, anchor extraction and re-anchoring run on *rendered* pages, for Quote (and later annotations) — they are a read-side concern with different consumers and a different lifecycle from authoring. This is deliberate, not an unfinished extraction: do not move it into `Editor`.

**`anchoring/block-elements.js` and `anchoring/canonical-text.js` define "block" differently — on purpose.** `canonical-text.js`'s `BLOCK_TAGS` includes bare `DIV` (any block-level wrapper should insert a space in the extracted text). `block-elements.js`'s `isBlockElement()` excludes bare `DIV` and only recognises `div.ce-block`, because it answers a different question (does a selection cross two blocks, so the caller can refuse it). Do not merge these two sets or reuse one for the other's purpose.

**The popover trigger must stay keyboard-operable.** `popover.blade.php`'s trigger is `tabindex="0"` with `onTriggerKeydown` handling Enter/Space and an `@keydown.escape.window` closing it; `aria-expanded` is bound to the open state. Any change to the trigger markup or `tooltip.js` must keep all three (Tab reachability, Enter/Space activation, Escape close) — this is covered by `Resources/js/tooltip.test.js`.

**No image upload lives here.** The `image-upload` component and its lang file are both gone — Media's `<x-media::image-field>` covers every case, and Story's cover tab owns its own dropzone copy under `story::shared.cover.custom_*`. Do not reintroduce an image upload widget here — image handling belongs to Media. `sound-upload` is still Shared's, until sound gets the same treatment.

**`BackToCommentsRedirector` only uses the path and query string.** Browsers never send the fragment in the `Referer` header; the `#comments` anchor is always appended by the helper, not read from the request.
