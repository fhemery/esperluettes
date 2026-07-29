---
name: generate-release-note
description: This skill should be used when the user asks to "generate a release note", "generate release notes", "write the release note between two versions", or "diff versions and generate the user release note". Accepts optional tag inputs like "from: 1.9.2 to: 1.9.3". Produces a French user-facing note plus a separate "À retester" section deduced from all commits that touch product features (including refactors).
---

# Generate a user release note

Diff two git tags (`X.Y.Z`) and produce **two distinct sections** in the chat:

1. **Release note** — French, user-facing, same style as
   `jd-esperluettes.fr/versions`. Functional content only.
2. **À retester** — for the deployer. Skim **all** commits in the range
   (including `refactor`, and other technical work that still touches a product
   domain) and list which features / domains need a smoke retest. This section
   exists precisely because refactors do **not** appear in the release note.

Do not conflate the two filters.

## 1) Inputs

Read the user message for:

- `from:` / `to:` tag values in `X.Y.Z` format, or two raw tags
  (e.g. `1.9.2` and `1.9.3`)
- an optional explicit `À retester:` override (free text). When provided, use
  it verbatim instead of deducing retest items.

## 2) Resolve the tag range

### Case A — user provides `from` and `to`

Use them as `<fromTag>` and `<toTag>`.

Validate both match `^\d+\.\d+\.\d+$`. If either tag is missing, stop and ask.

### Case B — no versions provided

Select `<toTag>` as the latest tag by git *creation date*, and `<fromTag>` as
the tag immediately before it:

```bash
git tag --sort=-creatordate
```

Propose the range (e.g. `Proposition : 1.9.2 → 1.9.3`) and proceed unless the
user overrides in the same message.

## 3) Collect commits between tags

```bash
git log --no-merges --name-only --format=%H%x1f%s%x1f%b%x1e <fromTag>..<toTag>
```

Parse each record as: hash, subject, body, and changed file paths.

Changed paths help when a `refactor(media)` subject is opaque — the files under
`app/Domains/<Domain>/` reveal which product surface to retest.

If the range is empty, say so and ask whether a different range was intended.

## 4) Release note filter (functional only)

Conventional commit form: `<type>(<scope>): <subject>`.

### Keep for the release note

- `feat` and `fix` that change user-visible behaviour

### Drop from the release note

- `docs`, `chore`, `refactor`, `test`
- scope `dev`
- anything whose subject/body is clearly technical only (`deptrac`, `ci`,
  `lint`, browser driver, playwright harness, pure schema migration mechanics,
  …)

When unsure whether a `feat`/`fix` is user-visible, prefer dropping it from the
**release note**. Do **not** apply this preference to the retest section.

## 5) Map scopes to French bracket categories

Use for **both** sections:

| Scope | Category |
|-------|----------|
| `profile` | `[Profil]` |
| `settings` | `[Préférences]` |
| `quote`, `quotes` | `[Citations]` |
| `story`, `stories` | `[Histoires]` |
| `discord` | `[Discord]` |
| `notification`, `notifications` | `[Notifications]` |
| `news`, `actualites` | `[Actualités]` |
| `media`, `medias` | `[Médias]` |
| `moderation` | `[Modération]` |
| `admin`, `administration` | `[Admin]` |
| `search` | `[Recherche]` |
| `readlist`, `pal` | `[Pile à lire]` |
| `comment`, `comments` | `[Commentaires]` |
| `calendar`, `jardino` | `[Calendrier]` |
| `editor` | `[Éditeur]` |
| `auth` | `[Auth]` |
| `dashboard` | `[Tableau de bord]` |
| `faq` | `[FAQ]` |
| `staticpage` | `[Pages statiques]` |
| `statistics`, `stats` | `[Statistiques]` |
| `shared` (user-visible UI only) | `[Divers]` |
| `dev` or unknown tooling | skip for retest unless files touch a domain |

Multi-scope subjects like `feat(settings, notifications, discord): …` produce
one category per product scope (or one bullet that names the main user-facing
surface).

## 6) Write the release-note bullets

For each commit kept by §4:

1. Translate into one short French, user-facing sentence.
2. Describe behaviour, not implementation.
3. No class names, files, or internal wording.

Format:

`* [Catégorie] <phrase>`

Deduplicate incremental commits that are the same user-visible change into one
bullet.

## 7) Header (versions-page style)

```text
Version X.Y.Z

_(DD mois YYYY)_
```

Date = tag creation / tagger date. French months: janvier, février, mars,
avril, mai, juin, juillet, août, septembre, octobre, novembre, décembre.

If the date cannot be parsed, use today and ask the user to confirm.

## 8) À retester — separate, broader filter

This section is **not** the release note. Its job is: after a deploy, what
product surfaces should be smoke-tested?

### Source set

Skim **every** commit in the range, including types dropped from the release
note:

- `refactor` — **include** (this is the main reason the section exists)
- `feat`, `fix` — include
- `chore` — include only if the scope or changed files touch a product domain
  (e.g. `chore(profile): …`), not pure `dev` / CI / lint tooling
- `docs`, `test` — usually skip unless the commit clearly renames or moves a
  user-facing surface

### How to deduce items

1. Read subject + body for each candidate commit.
2. If the subject is vague, glance at changed paths under `app/Domains/` (and
   relevant public routes/views) to identify the product surface.
3. Map to a bracket category (§5).
4. Write a short retest instruction: what to open / click / verify, not how the
   code was restructured.
5. **Group and dedupe by feature/domain** — several `refactor(profile)` commits
   become one `[Profil]` retest line covering the affected surface (e.g. onglets
   de profil, avatar, …), not one line per commit.
6. Aim for 3–8 bullets. Prefer breadth of surfaces over commit-by-commit
   noise.

### Example

Commits in range:

- `feat(quote): show the quote book by default…` → release note **and** retest
- `refactor(profile): give every profile tab the same component contract` →
  **retest only** (`[Profil] onglets de profil`)
- `chore(dev): add a browser driver skill…` → neither

### Override

If the user supplied an explicit `À retester:` block, keep it verbatim.

## 9) Final output shape

```text
Version X.Y.Z

_(DD mois YYYY)_

* [Catégorie] <bullet fonctionnel 1>
* [Catégorie] <bullet fonctionnel 2>
…

À retester :
* [Catégorie] <surface / parcours à vérifier>
* [Catégorie] <…>
```

Order:

1. Header (versions-page style)
2. Functional release-note bullets (may be empty)
3. `À retester :` (almost always non-empty when refactors touched domains)

If the release-note filter keeps nothing, still emit the header and an empty
bullet list, then fill `À retester` from the broader set. If even the retest
set is empty, write `aucune vérification spécifique déduite`.
