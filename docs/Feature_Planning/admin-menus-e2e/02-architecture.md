# Admin menus E2E — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

No new product domain. Ownership splits by concern:

| Concern | Owner |
|---------|-------|
| Stable sidebar selectors (`data-nav-key`) | **Administration** (sidebar / navigation-item markup) |
| `tech-admin` E2E account | **Auth** (`E2eAccountsSeeder` + role slug already in `Roles::TECH_ADMIN`) |
| Role→menu mapping, page object, core spec, Playwright fixtures | **`e2e/`** (outside domains — existing suite layout) |

### 1.1 Changes in other domains

**Administration** — expose a stable per-link key on every admin sidebar `<a>`
(registry pages and the two hardcoded links) so the suite asserts semantic
identity, not French copy or path strings. No registry API change.

**Auth** — seed one additional E2E user with role `tech-admin` (+ `user-confirmed`
like the other staff accounts). Password and CGU behaviour match existing E2E
accounts. No production Auth API change.

## 2. Data model

### 2.1 Tables

None.

### 2.2 Model

None.

### 2.3 Lifecycle rules

N/A.

## 3. PHP architecture

### 3.1 Public API

None. `AdminNavigationRegistry` stays as-is; the E2E mapping mirrors its
effective `permissions` sets, it does not call PHP.

### 3.2 Services

None.

### 3.3 Policy / authorization

Unchanged. Layout already gates moderator / admin / tech-admin.

### 3.4 Events and listeners

None.

### 3.5 Routes, controllers, form requests

None.

## 4. Frontend architecture

**Markup contract**

- Keep existing `data-test-id="admin-sidebar-link"` (PHP tests rely on it).
- Add `data-nav-key="<page-key>"` on each registry-driven link (the registry
  page key, e.g. `news.management`).
- Hardcoded links get fixed keys: `dashboard`, `back-to-site` (or equivalent
  stable strings documented in the mapping module).

**Playwright**

- `AdminSidebar` page object: goto `/administration`, collect unique
  `data-nav-key` values from `[data-test-id="admin-sidebar-link"]` (dedupe
  mobile/desktop duplicates).
- Mapping module: `Record<StaffRole, readonly string[]>` (or set) of expected
  keys for `moderator` | `admin` | `tech_admin`.
- Core spec: for each staff role fixture, assert set equality against the map.
- Extend `RoleName` / `ACCOUNTS` / `test.ts` fixtures / auth storage with
  `tech_admin` (email pattern `tech-admin@e2e.test`, mirror other accounts).

No Alpine/JS product changes.

## 5. Deptrac

No new edges. E2E and seeders do not create domain→domain PHP dependencies.

## 6. Testing strategy

| Layer | Role |
|-------|------|
| Playwright (`e2e/tests/core/`) | **Primary** — exact sidebar key inventory per staff role |
| PHP feature tests | Unchanged; existing nav sorting / fake-role tests stay; **do not** add a second frozen full inventory in PHP (would duplicate the mapping) |
| Vitest | N/A |
| VERIFY | Thin visual smoke optional: open admin as each role once; checklist is “sidebar matches map”, largely covered by the core spec itself |

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | How does the suite identify a sidebar link? | A) `data-nav-key` = registry page key · B) href paths · C) French labels | **A** | Keys match `registerPage` vocabulary; survive i18n and path tweaks; small Administration markup change |
| 2 | Where does the mapping live? | A) TS module under `e2e/` · B) PHP export consumed by E2E · C) PHP + E2E dual inventories | **A** | Request is an E2E / core-suite chore; dual sources drift; no PHP↔Playwright bridge exists today |
| 3 | Also freeze inventory in PHP? | A) E2E only · B) PHP feature test + E2E | **A** | e2e README: don't duplicate what another layer already owns; here E2E *is* the inventory lock |
| 4 | Depth of browser assertions | A) Key set equality · B) Click every link | **A** | Matches functional A1 / suite philosophy |

## 8. File layout

New / primarily new artefacts (conceptual tree):

```
e2e/
  pages/AdminSidebar.ts          # selectors + collectKeys()
  support/admin-nav-map.ts       # expected keys per staff role
  tests/core/admin-menus.spec.ts
app/Domains/Auth/Database/Seeders/E2eAccountsSeeder.php  # + tech-admin row
# Administration sidebar / navigation-item: data-nav-key only
```

## 9. Risks acknowledged

| Risk | Trigger to revisit |
|------|--------------------|
| Mapping drifts silently if someone adds `registerPage` without updating the TS map | Core suite fails on next `e2e:core` — that is the intended signal; document in e2e README one-liner when touching admin nav |
| `data-nav-key` on hardcoded links uses invented keys not in the registry | Document those two keys in the mapping module header |
| Duplicate mobile/desktop links | Dedupe by key in the page object; if markup stops duplicating, tests still pass |
| Role slug is `tech-admin` (hyphen) vs fixture name `tech_admin` | Mirror Auth PHP helper naming; email uses hyphen domain style like other e2e accounts |
