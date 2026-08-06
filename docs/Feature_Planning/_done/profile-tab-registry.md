# Profile tab registry

**Status:** DONE — 2026-07-27 · **Domain(s):** `Profile`, plus every domain that
owns a tab

Read this before adding a tab to the profile page, or before touching profile
visibility rules.

## What it does

Profile no longer knows which tabs exist. Each domain registers its own tab into
`Profile\Public\Api\ProfileTabRegistry` from its service provider, supplying a
component, a visibility rule and an optional privacy indicator. A single
catch-all route renders whichever tab the URL names. Adding a tab now touches
zero Profile files.

Before this, `profile/show.blade.php` held an `@if/@elseif` chain over hardcoded
tab keys, and other domains' data was fetched by Profile's controller.

## Key behaviour

- **One tab = one `ProfileTabDefinition`**, registered from the owning domain's
  provider. It carries the key, label, component, order, `isDefault` flag,
  a `ProfileTabVisibility` and an optional `ProfileTabPrivacy`.
- **Every tab component self-hydrates** from the request. There is no
  `dataProvider` hook and Profile passes nothing but `ownerUserId`.
- **Visibility is one interface, one implementation per tab.** `AlwaysVisible`
  and `AuthenticatedOnly` cover the trivial cases. Visibility **never counts
  rows** — access decides the tab, content decides the empty state.
- **Unknown or invisible tab → redirect to the default tab.** No 403, no 404.
  A guest hitting a protected tab is redirected to login, not 403'd.
- **The default tab is an explicit `isDefault` flag** on `stories`, not "first
  visible". Visitors land on `stories`.
- **The owner-facing privacy indicator is declarative** (`ProfileTabPrivacy` on
  the definition) and rendered by Profile, with wording owned by Profile
  (`profile::show.tab_visibility.*`). It is optional — a tab may be gated by a
  setting without declaring one.
- **A tab's setting belongs to the domain that owns the tab**, not to Profile.
  Profile registers only the settings *tab* and *privacy section*.

## Where the code lives

| Concern | Path |
|---------|------|
| Registry | `app/Domains/Profile/Public/Api/ProfileTabRegistry.php` |
| Contracts | `app/Domains/Profile/Public/Contracts/ProfileTab{Definition,Visibility,Privacy}.php` |
| Trivial visibilities | `app/Domains/Profile/Public/Visibility/{AlwaysVisible,AuthenticatedOnly}.php` |
| Catch-all route | `app/Domains/Profile/Private/routes.php` → `profile.show.tab` |
| Registration examples | `Follow`, `Quote`, `Story` service providers |

## Decisions worth remembering

- **D2** — comments privacy collapsed to one level: no access ⇒ no tab; access
  but no content ⇒ tab with an empty state. Visibility never queries counts.
- **D3** — no `dataProvider`; components self-hydrate. `quote::profile-tab`
  became a class component for this.
- **D4** — `requiredRoles` / `requiresAuth` were dropped in favour of one
  visibility implementation per tab.
- **D8** — no count callback in the contract, deliberately: it would put a
  counting query in every profile render.
- **D9** — no moderator-only tabs; such needs go to a dedicated admin screen.
- **D13** — scope is the profile page only. Not generalised to Story pages or
  Settings.

The full D1–D16 table is in the pre-loop document, recoverable from git history
(`git show f1d50704:docs/Feature_Planning/Profile_Tab_Registry.md`).

## Notable correction made during the migration

The original plan ordered routing before component normalisation. That was
wrong — the catch-all route cannot render Quote's tab while Quote still depends
on controller-supplied data. The two steps were swapped. Route aliases were
never kept: the call sites were migrated outright, and there were only two
non-test files.

## Follow-up already done

The `comments` tab's setting, translations and privacy rule moved from Profile
to Story (`ProfileCommentsPolicy`), and `ProfilePublicApi::canViewComments()`
was removed from the app-wide Shared contract. Profile no longer contains the
word "comments" outside its own tests. No data migration was needed — the value
is still stored under `domain = 'profile'`, `key = 'hide-comments-section'`.

## Not done

- Nothing outstanding. §6 of the original document recorded no open questions.
