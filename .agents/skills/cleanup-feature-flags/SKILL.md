---
name: cleanup-feature-flags
description: List the feature toggles the code still checks, compare them with their production state, and remove the ones the user approves. Use when the user asks to "clean up feature flags", "remove old toggles", "which feature toggles can go", or "audit feature toggles".
---

# Clean up feature toggles

Two steps, with a hard stop between them: **report**, then **remove only the
toggles the user approves**. Never remove a toggle the user did not name.

## What counts as a feature toggle

A toggle is **declared in code** with
`ConfigPublicApi::registerFeatureToggle(new FeatureToggleDefinition(domain, name, ...))`
in the owning domain's service provider, and read through
`ConfigPublicApi::isToggleEnabled(name, domain)`. Checking an undeclared toggle
throws. A row in `config_feature_toggles` only stores the state (access, roles);
a declared toggle with no row reads as `false`. A row no declaration matches is
an **orphan**: it has no effect, and only a tech admin deletes it, by hand, in
the admin page's « Non déclarés dans le code » section.

**Config parameters are not toggles and are never touched**, even when they are
booleans and live in a class called `FeatureToggles` (e.g. Story's cover flags,
registered with `registerParameter` and read with `getParameterValue`). They
are here to stay.

## 1. Inventory the code

The declarations are the inventory. List them, outside `Tests/`:

```bash
grep -rn -A4 "registerFeatureToggle\|new FeatureToggleDefinition" app --include=*.php | grep -v "/Tests/"
```

Skip the declaration machinery itself in `app/Domains/Config`. For each
declaration, resolve domain and name to strings:

- a literal (`'enabled'`) is the value;
- a constant (`FeatureToggles::DARK_THEME`) is read from its class, imported
  by the service provider (e.g. `app/Domains/Shared/Support/FeatureToggles.php`).

Then, per declared toggle, grep its `isToggleEnabled(` uses (by literal and by
constant) outside `Tests/` and `app/Domains/Config`. A missing domain argument
means `config`. Check each use is reachable: if it sits inside a helper method,
grep that the helper is called from non-test code. A declared toggle with no
reachable use counts as **unused**.

Also note, per toggle, what the cleanup will have to touch:

- the `registerFeatureToggle(...)` declaration in its service provider;
- test setups declaring or creating it:
  `grep -rn "['\"]<name>['\"]\|<Class>::<CONST>" app/Domains/*/Tests`
  (tests use `createFeatureToggle($this, new FeatureToggle(...))` or
  `declareFeatureToggle(...)`).

## 2. Get the production state

Ask the user to run, on production, and paste the output:

```bash
php artisan config:toggles --json
```

It is read-only and needs no login. Each entry has `domain`, `name`,
`declared`, `access` (`on` / `off` / `role_based`), `roles`, `updated_at`
(`null` when the declared toggle has no row yet, i.e. `off`). Entries with
`"declared": false` are **orphan rows**.

Production runs the deployed code, so its declarations may differ from the
branch's. Matching is case-insensitive on both domain and name, like
`FeatureToggleService`.

## 3. Report

One table, sorted by bucket, then domain:

| Domain | Name | Prod | Roles | Used in code | Where | Bucket |
|--------|------|------|-------|--------------|-------|--------|

Buckets:

| Situation | Bucket | What removal means |
|-----------|--------|--------------------|
| `on` in prod, used | **Shipped — candidate** | keep the enabled path, drop the check |
| `role_based` in prod | **Not live yet — keep** | nothing; not a candidate |
| `off` in prod (with or without a row) | **Off** — say whether it is used | if removed: drop the enabled path and the declaration |
| declared, unused | **Declared but unused** | remove the declaration and any test setup; delete the row after deploy if one exists |
| `"declared": false` in prod | **Orphan row** | no code change; delete the row in the admin UI |

Do **not** judge whether an `on` toggle has been on long enough — that is the
user's call from production history. Do not guess at abandoned features either:
list `off` toggles as off, with their usage, and stop.

End the report by asking which toggles to remove. **Wait for the answer.**

## 4. Remove the approved toggles

All approved toggles go on **one branch** (`chore/feature-toggle-cleanup` or
the current chore branch if already on one).

Per toggle, depending on the path kept (`on` → enabled path; `off` → disabled
path):

1. Replace each `isToggleEnabled(...)` call with the kept path. Remove the
   condition, not just its value: no `if (true)`, no dead `else`. When the
   disabled path goes, remove the code only it used (views, routes, services,
   translations) — but only once grep shows nothing else uses it.
2. Delete its `registerFeatureToggle(...)` declaration from the service
   provider, and the imports it leaves unused. Do this **after** step 1: any
   check left behind throws once the declaration is gone, which the gate will
   catch.
3. Tests: delete the tests of the removed path; in the remaining tests, drop the
   `createFeatureToggle(...)` / `declareFeatureToggle(...)` setup for this toggle.
4. Delete the toggle constant. Delete the `*FeatureToggles` class once it holds
   only its domain constant, and its now-unused imports.
5. Leave Config parameters and anything in `app/Domains/Config` alone.

Then run `pnpm run gate` until green, and commit with the `commit` skill
(`chore(<domain>): remove the <name> feature toggle` per toggle, or one
`chore: remove shipped feature toggles` listing them in the body).

## 5. Hand back the production steps

Rows are **not** deleted by a migration. Once the deploy removes a
declaration, its row becomes an orphan, and the admin page lists it in the
« Non déclarés dans le code » section, where a tech admin deletes it (the
section only offers deletion; declared toggles cannot be deleted). End with the
manual list, to run **after** the deploy:

```text
After deploying, delete these rows in Administration → Feature toggles,
section « Non déclarés dans le code » (tech admin):
- <domain> / <name>   (orphan row | removed from code)
```

Rows that were already orphans can be deleted right away; they need no deploy.
`php artisan config:toggles` confirms what is left.
