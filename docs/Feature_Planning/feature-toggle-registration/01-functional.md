# Feature toggles declared in service providers — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements. Written in `auto` mode: the judgement
> calls are listed in `DECISIONS.md` → "Assumptions made without asking".

## 1. Overview

Today a feature toggle is only a row created by hand in the admin UI; the code
reads it by name and silently gets "off" when the row is missing or the name is
mistyped. After this feature, a toggle exists because a domain **declares it in
its service provider**, the same way config parameters are declared. The code
becomes the list of toggles; production rows only store their state. A
read-only artisan command reports that state, so the `cleanup-feature-flags`
skill can compare code and production exactly.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Toggle declaration | A domain stating, at boot, that toggle `<domain>/<name>` exists, with its admin visibility. |
| Declared toggle | A toggle some domain declares. Only these can be checked by code. |
| Toggle state | The production row of a declared toggle: access (`on` / `off` / `role_based`), roles, last change date. A declared toggle without a row is `off`. |
| Orphan row | A production row whose toggle no domain declares any more. Admin UI label: « Non déclaré dans le code ». |

## 3. Roles & visibility

Only staff are involved; readers never see toggles.

| Role | Can see | Can do |
|------|---------|--------|
| Guest / `user` / `user-confirmed` | nothing | nothing (they only experience what toggles gate) |
| Moderator | nothing | nothing |
| Admin | declared toggles whose visibility is "all admins" | change their access and roles |
| Tech admin | every declared toggle, and orphan rows | change access and roles of any declared toggle; delete an orphan row |
| Developer (code) | — | declare a toggle; check a declared toggle |
| Deployer (shell on the VPS) | the command's output | run the read-only command |

## 4. Functional requirements

### 4.1 Declaring a toggle

1. A domain declares each of its toggles in its service provider: domain,
   name, admin visibility ("tech admins only" or "all admins").
2. A declared toggle with no production row is `off`.
3. Declaring the same toggle twice keeps the last declaration (same rule as
   config parameters).

### 4.2 Checking a toggle

1. Checking a declared toggle behaves as today: `on` → enabled, `off` →
   disabled, `role_based` → enabled only for users holding one of its roles.
   Domain and name stay case-insensitive.
2. Checking a toggle **no domain declares** is a programming error and fails
   loudly (an exception), in every environment. It no longer reads as "off".
   Tests therefore catch a mistyped or undeclared name.

### 4.3 Admin page

1. The page lists declared toggles, grouped by domain, whether or not they have
   a production row. A toggle without a row shows as `OFF`.
2. Admins see only "all admins" toggles; tech admins see all of them. The
   visibility comes from the declaration and is not editable in the UI.
3. Changing access (and roles, for `role_based`) works as today and records the
   change date. The first change of a toggle without a row creates its row.
4. There is no longer a "create toggle" form: a toggle cannot be created by hand.
5. Tech admins also see the orphan rows, labelled « Non déclaré dans le code »,
   with a single action: delete. Deleting is how production rows are cleaned up
   after a toggle leaves the code (manual step after deploy).
6. Toggles carry no description text: the optional
   `<domain>::config.feature_toggles.<name>` lookup and its column go away.

### 4.4 Read-only command

1. `artisan config:toggles --json` prints every declared toggle and every
   orphan row, each with: domain, name, declared (yes/no), access, roles, last
   change date (empty when the toggle has no row).
2. Without `--json` it prints the same as a table.
3. It changes nothing and needs no user session.

### 4.5 Migrating the existing toggle

1. `shared/dark_theme` becomes a declared toggle (admin visibility: tech admins
   only). Its production row, and so its current state, is kept.
2. No other toggle remains in the code (first cleanup already done).

### 4.6 The `cleanup-feature-flags` skill

1. Its inventory starts from the declarations instead of grepping calls; it
   still greps each declared toggle's uses to tell used from unused.
2. Its production state comes from the command's JSON instead of a paste of
   the admin page. Orphan rows come straight from the command.

## 5. Lifecycle

- A toggle removed from the code: its row becomes an orphan row, shown to tech
  admins until deleted by hand. Nothing is deleted automatically.
- A toggle declared again later with the same domain and name picks up its
  existing row, if still there.
- User deactivation or deletion: N/A — toggles belong to no user. The "last
  changed by" value on a row may point at a deleted user; nothing shows it.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Admin / tech admin split unchanged (§3); declaration adds nothing for readers. |
| Visibility / privacy | Admin visibility moves from the row to the declaration. |
| Settings | N/A — no user preference. |
| Notifications | N/A — none. |
| Domain events | Access changes and deletions stay recorded in the event log as today; no consumer exists. There is no "toggle added" moment any more. |
| Statistics | N/A. |
| Moderation | N/A. |
| Lifecycle / cascade | §5 — orphan rows kept until deleted by hand. |
| Media | N/A. |
| Search | N/A. |
| i18n | Admin strings in French (« Non déclaré dans le code »); toggles themselves carry no text. |
| Mobile | Admin page keeps its current responsive behaviour. |
| Accessibility | N/A beyond the existing admin page. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Where does the production state come from for the cleanup skill? | A read-only artisan command whose output the user pastes (`--json`). |
| 2 | Row deletion after a toggle leaves the code? | Manual, in the admin UI, after the deploy — no migration. |
| 3 | Config parameters in scope? | No — only toggles; parameters stay as they are. |
| 4 | Order of work? | Skill first, first cleanup, then this feature, which also readjusts the skill. |
| 5 | French strings on a toggle? | None. |

## 8. Out of scope

- Config parameters (including the Story cover flags).
- Automatic deletion of orphan rows.
- Changing what `shared/dark_theme` gates, or its boot-time evaluation.
- Any change to how readers experience a toggled feature.
- The release note / rollout plan mechanism (`release-notes` task).

## 9. Open questions

- **Non-blocking** — `shared/dark_theme` is checked once at boot, before any
  user is known, so `role_based` behaves as `off` for it. Pre-existing; noted,
  not fixed here.
