# Profile Tab Registry

Status: **implemented** on `refactor/profile-tabs-registry`
Date: 2026-07-26, updated 2026-07-27

## 1. Problem

Every profile tab is hardcoded in the Profile domain, in three places at once:

| Concern | Location |
|---|---|
| Tab list, labels, visibility rules | `app/Domains/Profile/Private/Resources/views/pages/show.blade.php` (lines 118–167, a 50-line `@php` block) |
| Tab content dispatch | same file, `@if/@elseif` chain (lines 174–188) |
| One route + one controller method per tab | `Private/routes.php`, `ProfileController` |

Current tabs:

| Key | Owner domain | Label (own / other) | Visibility rule | Content |
|---|---|---|---|---|
| `about` | Profile | `profile::show.about` | authenticated | `x-profile::about-panel` |
| `stories` | Story | `profile::show.my-stories` / `.stories` | always (incl. guests) | `x-story::profile-stories-component` (self-hydrating class component, reads `?page`) |
| `comments` | Story | `story::profile.my-comments` / `.comments` | role `user-confirmed`, **plus** a second, distinct privacy check that hides the *content* but not the *tab* (`ProfilePrivacyService::canViewComments`) | `x-story::profile-comments-component` |
| `following` | Follow | `follow::follow.following_tab.label` (same both ways) | `FollowPublicApi::canViewFollowingTab($ownerId, $viewerId)` | `x-follow::following-tab` |
| `quotes` | Quote | `quote::ui.profile_tab.my_quotes` / `.quotes` | `QuotePublicApi::canViewQuoteBook($ownerId, $viewerId)` | `x-quote::profile-tab` — **needs data preloaded by the controller** (`getForProfile(...)` with `?page`) |

Consequences today:

- `ProfilePrivate` deptrac ruleset depends on `FollowPublic`, `QuotePublic`, `SettingsPublic`, `StoryPublic`(via blade) — it grows with every new tab.
- Visibility logic is duplicated: once for the tab strip, once for route protection (middleware for `about`/`comments`, controller `abort(403)` for `following`, nothing for `quotes`).
- Blade-level coupling (`x-story::…`) dodges deptrac but is still a real dependency, invisible to the tool.
- Statistics tab is coming; the `@php` block is already unreadable.

## 2. Proposed solution

A `ProfileTabRegistry` in `Profile/Public`, into which any domain registers a tab definition from its own service provider. Profile owns *rendering and routing*; consumer domains own *label, visibility and content*.

Dependency direction flips: `Story → ProfilePublic` instead of `ProfilePrivate → StoryPublic`.

### 2.1 The contract

`app/Domains/Profile/Public/Contracts/ProfileTabDefinition.php`

```php
final class ProfileTabDefinition
{
    public function __construct(
        public readonly string $key,            // url segment + tab key, e.g. 'quotes'
        public readonly int $order,             // sort order in the strip
        public readonly string $component,      // blade component name, e.g. 'quote::profile-tab'
        public readonly string $labelKey,       // translation key when viewing someone else
        public readonly ?string $ownLabelKey = null,   // translation key when viewing own profile
        public readonly ?string $icon = null,          // material symbol, optional
        public readonly string $visibility = AlwaysVisible::class, // FQCN implementing ProfileTabVisibility
        public readonly ?ProfileTabPrivacy $privacy = null,        // owner-facing indicator, §2.8
        public readonly bool $isDefault = false,       // landing tab, §2.6 — exactly one
    ) {}
}
```

`app/Domains/Profile/Public/Contracts/ProfileTabVisibility.php`

```php
interface ProfileTabVisibility
{
    /** @param int|null $viewerId null = guest */
    public function isVisible(int $ownerUserId, ?int $viewerId): bool;
}
```

Resolved from the container, so implementations inject their own services. **One single rule, no `requiredRoles` / `requiresAuth` shortcuts** — the viewer id is enough to derive roles, and two ways of expressing visibility would inevitably drift. To keep the trivial cases free of boilerplate, Profile ships three reusable implementations in `Profile/Public/Visibility/`:

| Class | Rule | Used by |
|---|---|---|
| `AlwaysVisible` | `true` | `stories` (default value) |
| `AuthenticatedOnly` | `$viewerId !== null` | `about` |

A `RoleBasedVisibility` base was drafted for the `comments` role check and then deleted unused: `ProfilePublicApi::canViewComments()` already encodes the confirmed-user requirement *and* the owner case, the moderator bypass and the hide-comments setting, so `CommentsTabVisibility` is a one-line delegation. Add the base back when a second tab genuinely needs a bare role check.

Domains with real logic (`following`, `quotes`) implement the interface themselves and delegate to their existing `canViewFollowingTab()` / `canViewQuoteBook()` API methods.

**Per your ruling: access is binary.** A tab the viewer cannot access (by setting or role) is *absent* from the strip and 404s on direct URL. A tab the viewer can access but that has nothing to show (all comments on private stories, empty quote book) is *present*, and its component renders its own empty state. Visibility never depends on content count — no counting queries on profile render.

### 2.2 The registry

`app/Domains/Profile/Public/Api/ProfileTabRegistry.php` — container singleton (not static state, unlike `SettingsRegistryService`; see Q9).

```php
public function register(ProfileTabDefinition $tab): void;   // throws on duplicate key
public function all(): array;                                 // sorted by order
public function get(string $key): ?ProfileTabDefinition;
public function visibleFor(int $ownerUserId, ?int $viewerId): array;  // sorted, filtered
public function isVisible(string $key, int $ownerUserId, ?int $viewerId): bool;
public function defaultFor(int $ownerUserId, ?int $viewerId): ?ProfileTabDefinition;
```

`visibleFor()` is the single source of truth used by both the tab strip and the route guard — the duplication in §1 disappears.

### 2.3 Registration example (Quote)

```php
// QuoteServiceProvider::boot()
$this->app->make(ProfileTabRegistry::class)->register(new ProfileTabDefinition(
    key:                'quotes',
    order:              40,
    component:          'quote::profile-tab',
    labelKey:           'quote::ui.profile_tab.quotes',
    ownLabelKey:        'quote::ui.profile_tab.my_quotes',
    visibility:         QuoteBookVisibility::class,
    privacy:            new ProfileTabPrivacy(
        settingsTabId: self::TAB_PROFILE,
        settingsKey:   self::KEY_HIDE_QUOTES_TAB,
    ),
));
```

and Story's, which needs neither:

```php
// StoryServiceProvider::boot()
$registry->register(new ProfileTabDefinition(
    key:         'stories',
    order:       20,
    component:   'story::profile-stories-component',
    labelKey:    'story::profile.stories',
    ownLabelKey: 'story::profile.my-stories',
    isDefault:   true,
));
```

(Note the `stories` labels move from `profile::show.*` to `story::profile.*` — the translation keys should live with the domain that owns the tab.)

### 2.4 Routing

Replace the five per-tab routes with one catch-all, declared last in the Profile prefix group:

```php
Route::get('/{profile:slug}', [ProfileController::class, 'show'])->name('profile.show');
Route::get('/{profile:slug}/{tab}', [ProfileController::class, 'showTab'])
     ->name('profile.show.tab')
     ->where('tab', '[a-z0-9-]+');
```

`showTab()` resolves in this order (D6 + D7):
1. tab key not registered → redirect to the default tab;
2. registered but `!$registry->isVisible(...)`:
   - **guest** → redirect to login (`route('login')` with the intended URL), preserving today's `auth` middleware behaviour;
   - **authenticated** → redirect to the default tab;
3. otherwise render.

The guest branch has to come first, otherwise a logged-out user asking for `/profile/x/about` would silently land on `stories` instead of being invited to sign in. Note the consequence: a guest is redirected to login for *any* tab they cannot see, which reveals nothing about the profile (all non-public tabs behave identically) but does mean a guest hitting a private quote book gets a login page rather than a redirect. Acceptable — and it matches the current `about` behaviour.

Since the route no longer carries per-tab middleware, `auth` and `compliant` can no longer be declared per tab.

- `auth` is replaced by the guest branch above — the controller decides, per tab, whether this viewer needs to log in.
- **`compliant` is applied to the whole tab route** (decided). `EnsureUserCompliance` is a no-op for guests, so public tabs stay reachable while logged out; for logged-in users it exists precisely to force acceptance of the conditions, so applying it uniformly is the intent, not a regression. No `middleware` field on the definition — that would let any domain inject middleware into Profile's route, a much wider door than we need.

Route placement matters: `/{profile:slug}/{tab}` must be declared **after** every other route in the `profile` prefix group (`/edit`, `/lookup`, `/search`, `/{profile:slug}/moderation/*`), so those keep matching first and only genuinely unclaimed segments reach `showTab()`.

Named routes: `route('profile.show.tab', [$profile, 'quotes'])`. Good news: the existing names (`profile.show.stories`, `profile.show.comments`, …) appear in only two non-test files, both inside Profile (`routes.php` and `show.blade.php`) — the remaining ~30 occurrences are in feature tests. So migrating is cheap; see Q8.

### 2.5 Content rendering

The `@if/@elseif` chain becomes:

```blade
<x-shared::scrollable-tabs :tabs="$tabs" :active-tab="$activeTab" mode="link" />

<div class="flex flex-col gap-4 p-4 surface-read text-on-surface">
    @if($activeTabDefinition)
        <x-dynamic-component :component="$activeTabDefinition->component"
                             :owner-user-id="$profile->user_id" />
    @endif
</div>
```

Every tab component gets exactly one prop: `ownerUserId`. Nothing else. Anything else it needs — pagination, the profile slug, whether the viewer owns the profile — it derives itself.

The first draft passed `isOwn` too. Dropped during implementation: it is `Auth::id() === $ownerUserId`, which three of the four components already computed internally, so passing it created a second source of truth for no gain. Quote needing the profile *slug* is resolved the same way, via `ProfilePublicApi::getPublicProfile()` from Shared — no `ProfilePublic` dependency required.

**This is the main refactoring cost**, because the tab components are inconsistent today:

- `story::profile-stories-component`, `story::profile-comments-component` — class components that hydrate themselves from `request()->query('page')`. ✅ Already fit the contract, only the prop name changes.
- `follow::following-tab` — same pattern. ✅
- `quote::profile-tab` — anonymous component that *receives* `$quoteList` built by `ProfileController::showQuotes()`. ❌ Must become a class component (`QuoteProfileTab`) that calls `QuotePublicApi::getForProfile()` itself. `profileSlug` it currently receives can be resolved from `ownerUserId` inside Quote, or Quote can keep taking the slug from the route.
- `profile::about-panel` — takes `$profile` (the Eloquent model). ❌ Must accept `ownerUserId` and load the profile itself, or Profile registers it with a special-case prop. Cheap either way since it is Profile's own component.

The "content hidden by privacy" case (comments) **disappears**: today the tab is shown to any confirmed user and the content is swapped for `profile::settings.privacy.comments-hidden` when `ProfilePrivacyService::canViewComments()` is false. Per the binary-access rule, that privacy setting now drives tab *visibility* instead — `CommentsTabVisibility` combines the `user-confirmed` role check with `canViewComments()`, and the "comments hidden" message and its translation key are deleted. This requires exposing `canViewComments(ownerId, viewerId)` on `ProfilePublicApi` for Story to call.

Behaviour change to confirm: a viewer whose access is blocked by the owner's privacy setting no longer sees an empty "comments are hidden" tab — the tab is simply gone.

### 2.6 Default tab

**Decided:** the landing tab is an explicit `isDefault: true` flag, carried by `stories` (Story's registration). "First visible tab in order" was rejected because it makes the landing page a side effect of ordering.

`ProfileController::show()` → `$registry->defaultFor($ownerId, $viewerId)`:
1. the tab flagged `isDefault` if it is visible to this viewer;
2. otherwise the first visible tab by order;
3. otherwise 404 (cannot happen today — `stories` is `AlwaysVisible`).

The registry throws at registration time if a second tab claims `isDefault`, so the conflict surfaces on boot, not in production.

| Viewer | Today | With the registry |
|---|---|---|
| guest | `stories` | `stories` ✅ |
| own profile | `stories` | `stories` ✅ |
| other authenticated | `about` | `stories` ⚠️ changed |

That last row is the one behaviour change — landing on someone's stories rather than their bio. It follows directly from "stories is *the* default"; say the word if you want the visitor case to keep landing on `about` and I'll add a separate `defaultForOthers` flag instead.

### 2.7 Ordering across domains

Orders are integers chosen by each registering domain, with a documented convention (10-point spacing, `about` 10, `stories` 20, `comments` 30, `following` 40, `quotes` 50, `statistics` 60) in `Profile/README.md`. Ties resolve by key alphabetically so the render stays deterministic. Alternative: a `ProfileTabOrder` constants class in `Profile/Public` — explicit, but every new tab then touches Profile again, which is exactly what we are trying to avoid.

### 2.8 Owner-facing visibility indicator

For tabs whose visibility is driven by a user setting, the owner should see — from the tab strip — whether that tab is exposed to others, with a shortcut to the setting.

This already exists, twice, implemented independently: Follow renders an eye icon inside `following-tab.blade.php` (`data-follow-visibility-indicator`) and Quote does the same in `profile-tab.blade.php`. Both build the same popover: an icon, a "visible/hidden" sentence, and a link to `route('settings.index', ['tab' => 'profile'])`. Moving it into the registry deletes both copies and puts the indicator where it belongs — on the tab itself, visible without opening it.

Declarative, and now down to two meaningful fields:

```php
final class ProfileTabPrivacy
{
    public function __construct(
        public readonly string $settingsTabId,  // Settings tab owning the parameter
        public readonly string $settingsKey,    // parameter key
    ) {}
}
```

Two simplifications since the first draft:

- **Wording is owned by Profile** (decided). The popover strings live in `profile::show.tab_visibility.{visible,hidden,preferences_link}` rather than per-domain. Follow's existing copy is already domain-neutral ("Cet onglet est visible des autres utilisateurices.") and moves across verbatim; Quote's duplicate is deleted.
- **No polarity flag needed any more.** The first draft carried `trueMeansHidden` because `follow.hide-following-tab` and `quote.book_public` disagreed. Flipping Quote to `hide-quotes-tab` (done, see §8) aligned all three profile privacy settings on *true = hidden*, so the flag has nothing left to express. If a future tab ever needs the opposite polarity, the right fix is to rename that setting, not to reintroduce the flag.

Profile resolves it through `SettingsPublicApi` (already a `ProfilePrivate` dependency) and renders, **only when `$isOwn`**, a small `visibility` / `visibility_off` icon next to the tab label, with a popover linking to `route('settings.index', ['tab' => $settingsTabId])`.

As built, it renders in **two places**, because the two serve different jobs:

- **In the tab strip**, an `visibility` / `visibility_off` icon on every setting-gated tab. This is the new capability: the owner sees at a glance which of their tabs are exposed, without opening each one. It is deliberately non-interactive — an `<a role="tab">` must not wrap a control — so it carries the explanation in `title` / `aria-label` only.
- **Above the active tab's content**, the popover with the same wording plus the link to the setting. This is the existing Follow/Quote indicator, relocated and generalised: one implementation in Profile instead of two hand-rolled copies, and now covering `comments` too.

`x-shared::scrollable-tabs` gained one optional per-tab key, `visibility` (`hidden` bool + `label`), and stays generic — the URL and copy are passed in, so Shared learns nothing about settings.

The strip data is assembled by `ProfileController::buildTabStrip()`, not in Blade, so the view holds no logic at all.

It stays purely informative: the actual decision remains the `ProfileTabVisibility` implementation. The two can drift — a tab could declare `privacy` pointing at a setting its visibility rule ignores — and per D16 nothing enforces the pairing.

## 3. Deptrac impact

Remove from `ProfilePrivate`: `FollowPublic`, `QuotePublic`. (Keep `SettingsPublic`, `AuthPublic`, `ModerationPublic` — used elsewhere in the domain.)

Add `ProfilePublic` to: `StoryPublic`, `QuotePublic`, `FollowPublic` (registration happens in `Public/Providers/*ServiceProvider`). `StoryPrivate` already has it. Note that Statistics has **no layer/ruleset entry in `deptrac.yaml` at all** — it should be added when its tab lands.

Net: the coupling stops being hidden in Blade and becomes a declared, correctly-directed dependency.

## 4. Migration plan — **done**

Shipped in five commits on `refactor/profile-tabs-registry`:

| # | Commit | What |
|---|---|---|
| 0 | `refactor(settings)` | Prerequisite: registry singleton instead of static state (§7). |
| 0b | `feat(quote)` | Prerequisite: quote book visible by default via `hide-quotes-tab` (§8). |
| 1 | `feat(profile): add the profile tab registry…` | Contracts, registry, visibility helpers, 15 unit tests. Purely additive. |
| 2 | `refactor(profile): give every profile tab…` | All four tab components take a single `ownerUserId` and self-hydrate; Quote and About become class components. |
| 3 | `feat(profile): drive the profile tabs from the registry` | Registration from each owning domain, catch-all route, `@php` block and `@if/@elseif` chain deleted, deptrac flipped. |
| 4 | `feat(profile): show tab visibility to the owner` | `ProfileTabPrivacy` rendered in the strip and above the content; the two hand-rolled indicators deleted. |

The original plan ordered routing before component normalisation. That was wrong: the catch-all route cannot render Quote's tab while Quote still depends on controller-supplied data, so steps 2 and 3 were swapped. Route aliases were never kept — D10 settled on migrating the call sites outright, and there turned out to be only two non-test files.

Success criteria, checked: full suite green (2326); deptrac clean; adding the Statistics tab now touches zero Profile files.

## 5. Decisions taken

| # | Question | Decision |
|---|---|---|
| D1 | Default tab | Explicit `isDefault` flag on `stories`, not "first visible" (§2.6). Visitors now land on `stories` instead of `about`. |
| D2 | Comments two-level privacy | Collapsed to one level: no access ⇒ no tab. Access but no content ⇒ tab shown with its own empty state. Visibility never counts rows (§2.5). |
| D3 | Data loading | Every tab component self-hydrates from the request. No `dataProvider` hook; `quote::profile-tab` becomes a class component (§2.5). |
| D4 | `requiredRoles` / `requiresAuth` | Dropped. One `ProfileTabVisibility` implementation per tab, with `AlwaysVisible` / `AuthenticatedOnly` shipped by Profile for the trivial cases (§2.1). |
| D5 | Owner visibility indicator | Declarative `ProfileTabPrivacy` on the definition, rendered by Profile in the tab strip; replaces the two hand-rolled indicators in Follow and Quote (§2.8). |
| D6 | Guest on a protected tab | Redirect to login (not 403), preserving today's `auth` middleware behaviour. The catch-all route accepts any unclaimed segment and the controller decides, per tab, whether this viewer must log in. |
| D7 | Unknown or invisible tab key | Redirect to the default tab, for both `/profile/x/banana` and a tab hidden from this viewer. No 403/404 branch to design. |
| D14 | `compliant` middleware | Applied to the whole tab route. It exists to force acceptance of the conditions for logged-in users and is a no-op for guests, so a uniform application is correct (§2.4). |
| D15 | Indicator wording | Owned by Profile (`profile::show.tab_visibility.*`), not per-domain. Follow's copy is already domain-neutral and moves across; Quote's duplicate is deleted. Removes both label keys — and, with the Quote flip, the polarity flag — from `ProfileTabPrivacy` (§2.8). |
| D16 | Indicator is optional | A tab may be gated by a setting without declaring `ProfileTabPrivacy`. The indicator is a nicety, not a guarantee; no test enforces it. |
| D8 | Badge / counts in the strip | Not needed. No count callback in the contract — keeps profile rendering free of counting queries. |
| D9 | Moderator-only tabs | Not a use case; such needs go to a dedicated admin screen. |
| D10 | Route names | Centralise on `profile.show.tab` in Profile; drop the per-tab aliases. |
| D11 | Registry state | Container singleton with instance state. **Done** — see §7. |
| D12 | Tab components | Class components only. |
| D13 | Scope | Profile page only; not generalised to Story pages or Settings. |

## 6. Open questions

None outstanding.

## 7. Prerequisite done: registry state (former Q9)

Investigated and fixed ahead of the rest, since the new registry should not copy the pattern.

**Why Settings went static:** `SettingsRegistryService` was never bound in the container, so `app(SettingsRegistryService::class)` built a *fresh instance* for each of its three consumers (`SettingsPublicApi`, `SettingsService`, `SettingsController`). The `static` properties were the only thing making the state shared at all.

**Fix applied:**
- `SettingsServiceProvider::register()` now binds `SettingsRegistryService` as a singleton;
- the three `private static` arrays became instance properties; `clearAll()` became `clear()`;
- `clearSettingsRegistry()` calls `->clear()` on the singleton, so tests needing an empty registry keep working.

**Impact:** two tests in `ThemePreferenceTest` failed — and they were *wrong before*. The `AppearanceService - user preference` block never registered the `appearance` parameter; it only passed because an earlier test in the same file registered it and the static state leaked forward. Added the missing `beforeEach(enableDarkThemeSettingForTesting(...))`, matching the sibling block. Full suite green (2304 passed), deptrac 0 violations. `Settings/AGENTS.md` and `README.md` updated — the AGENTS note previously told every domain to sprinkle `clearSettingsRegistry()` in `beforeEach`/`afterEach` to fight the contamination; that workaround is no longer needed.

`ProfileTabRegistry` follows the same pattern: singleton bound in `ProfileServiceProvider::register()`, instance state, no statics.
