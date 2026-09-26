---
name: cleanup-feature-flags
description: List the feature toggles the code still checks, compare them with their production state, and remove the ones the user approves. Use when the user asks to "clean up feature flags", "remove old toggles", "which feature toggles can go", or "audit feature toggles".
---

# Clean up feature toggles

Two steps, with a hard stop between them: **report**, then **remove only the
toggles the user approves**. Never remove a toggle the user did not name.

## What counts as a feature toggle

A toggle is a row in `config_feature_toggles`, read through
`ConfigPublicApi::isToggleEnabled(name, domain)`. A name with no row reads as
`false`. Rows are created by hand in the admin UI; the code only reads them.

**Config parameters are not toggles and are never touched**, even when they are
booleans and live in a class called `FeatureToggles` (e.g. Story's cover flags,
registered with `registerParameter` and read with `getParameterValue`). They
are here to stay.

## 1. Inventory the code

Every call site of `isToggleEnabled(`, outside `app/Domains/Config` and outside
`Tests/`:

```bash
grep -rn -A3 "isToggleEnabled\s*(" app --include=*.php | grep -v "/Tests/" | grep -v "app/Domains/Config/"
```

For each call, resolve the two arguments to strings:

- a literal (`'enabled'`) is the value;
- a constant (`DiscordFeatureToggles::NOTIFICATIONS`) is read from its class,
  usually `app/Domains/<Domain>/Private/Support/*FeatureToggles.php`;
- a missing domain argument means `config`.

Then check the call is reachable: if it sits inside a helper method, grep that
the helper is called from non-test code. A toggle whose only reachable uses are
in `Tests/` counts as **unused**.

Also note, per toggle, what the cleanup will have to touch:

- test setups creating it: `grep -rn "name: *['\"]<name>['\"]\|<Class>::<CONST>" app/Domains/*/Tests`
  (tests build toggles with `createFeatureToggle($this, new FeatureToggle(...))`);
- a description string at `<domain>::config.feature_toggles.<name>`
  (`app/Domains/<Domain>/Private/Resources/lang/fr/config.php`).

## 2. Get the production state

Ask the user for the production toggle list. Until an artisan command exists,
they paste the admin page (*Administration → Feature toggles*): domain, name,
access (`ON` / `OFF` / `PAR RÔLE`), roles. Normalise it to lowercase
`domain` / `name` and `on` / `off` / `role_based`.

Matching is case-insensitive on both domain and name, like
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
| `off` in prod | **Off** — say whether it is used | if removed: drop the enabled path |
| not in prod, used | **Missing row** (always false) | if removed: drop the enabled path |
| in prod, unused | **Orphan row** | no code change; delete the row |

Do **not** judge whether an `on` toggle has been on long enough — that is the
user's call from production history. Do not guess at abandoned features either:
list `off` toggles as off, with their usage, and stop.

End the report by asking which toggles to remove. **Wait for the answer.**

## 4. Remove the approved toggles

All approved toggles go on **one branch** (`chore/feature-toggle-cleanup` or
the current chore branch if already on one).

Per toggle, depending on the path kept (`on` → enabled path; `off` / missing
row → disabled path):

1. Replace each `isToggleEnabled(...)` call with the kept path. Remove the
   condition, not just its value: no `if (true)`, no dead `else`. When the
   disabled path goes, remove the code only it used (views, routes, services,
   translations) — but only once grep shows nothing else uses it.
2. Tests: delete the tests of the removed path; in the remaining tests, drop the
   `createFeatureToggle(...)` setup for this toggle.
3. Delete the toggle constant. Delete the `*FeatureToggles` class once it holds
   only its domain constant, and its now-unused imports.
4. Delete the `feature_toggles.<name>` description string; delete the lang file
   if it is left empty.
5. Leave Config parameters and anything in `app/Domains/Config` alone.

Then run `pnpm run gate` until green, and commit with the `commit` skill
(`chore(<domain>): remove the <name> feature toggle` per toggle, or one
`chore: remove shipped feature toggles` listing them in the body).

## 5. Hand back the production steps

Rows are **not** deleted by a migration: code still reading a row must be gone
first. End with the manual list, to run in the admin UI **after** the deploy:

```text
After deploying, delete these feature toggles in Administration → Feature toggles:
- <domain> / <name>   (orphan row | removed from code)
```

Orphan rows can be deleted right away; they need no deploy.
