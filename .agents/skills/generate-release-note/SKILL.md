---
name: generate-release-note
description: This skill should be used when the user asks to "generate a release note", "generate release notes", "write the release note between two versions", or "diff versions and generate the user release note". Accepts optional tag inputs like "from: 1.9.2 to: 1.9.3" and an optional explicit "À retester:" override.
---

# Generate a user release note

Create a French, user-facing release note by diffing two git tags (`X.Y.Z`) and
transforming the resulting commits into the bracketed category style used on
`jd-esperluettes.fr/versions`.

The release note must include only functional/user-visible changes.

## 1) Inputs and follow-up questions

### Supported inputs (from the user message)

The skill should read the user message for:

- `from:` / `to:` tag values in `X.Y.Z` format, or two raw tags (e.g. `1.9.2`
  and `1.9.3`)
- an optional explicit `À retester:` override (free text). When provided, the
  skill should use it verbatim instead of deducing retest items.

## 2) Resolve the tag range

### Case A — user provides `from` and `to`

Use the provided tags as `<fromTag>` and `<toTag>`.

Validate that both tags match `^\\d+\\.\\d+\\.\\d+$` (e.g. `1.9.3`).
If either tag does not exist, stop and ask the user to confirm the correct
tags.

### Case B — no versions provided

Select `<toTag>` as the latest tag by git *creation date*.
Select `<fromTag>` as the tag immediately before it by creation date.

In git, list tags sorted by creation date with:

```bash
git tag --sort=-creatordate
```

Take the first two entries as `<toTag>` and `<fromTag>`.

The skill should propose this range to the user (e.g. "Proposition : `<fromTag>` → `<toTag>`")
and proceed with it unless the user provides an override in the same message.

## 3) Collect commits between tags

Use the range `<fromTag>..<toTag>` so the note describes what changed in
`<toTag>` relative to `<fromTag>`.

Collect commits without merge commits and include both subject and body:

```bash
git log --no-merges --format=%s%x1f%b%x1e <fromTag>..<toTag>
```

The skill should parse each record as:

- subject line (`%s`)
- body (`%b`)

If git output is empty for the range, generate a short note with only the
header and an empty bullets section and ask the user whether they meant a
different tag range.

## 4) Filter to functional/user-visible changes only

Each commit subject in this repo typically follows the conventional commit
format:

`<type>(<scope>): <subject>`

Where:

- `<type>` is one of `feat`, `fix`, `docs`, `chore`, `refactor`, `test`, …
- `<scope>` is usually a domain name (e.g. `profile`, `discord`, `settings`,
  `quote`, `quotes`, `news`, …) or `dev`

### Primary rule (based on your convention)

Keep commits where `<type>` is:

- `feat`
- `fix`

Drop commits where `<type>` is:

- `docs`
- `chore`
- `refactor`
- `test`

Rationale: the versions page is for users, and these types usually indicate
maintenance rather than user-visible shipped work.

### Secondary exclusion rules (technical keywords)

Even if a commit is `feat`/`fix`, drop it if the subject/body strongly
signals purely technical work. Use these heuristics:

- drop if `<scope>` is `dev`
- drop if subject/body contains any of:
  - `deptrac`
  - `ci`
  - `lint`
  - `build` (as a technical build step)
  - `browser driver`
  - `playwright` (tests/automation)
  - `migration` (when clearly about DB/schema migration mechanics rather
    than user-facing functionality)

### If unsure: prefer dropping

When a commit is borderline and would require code inspection to decide user
visibility, prefer dropping it rather than including technical noise. The
generator should only include borderline items if their subject/body contains
clear user language (features, UI changes, behavior changes, new screens,
notifications, etc.).

## 5) Map commit scope to bracketed French categories

Convert each kept commit to a French category label in the bracket style:

- `[Profil]` for scope `profile`
- `[Préférences]` for scope `settings`
- `[Citations]` for scope `quote` or `quotes`
- `[Histoires]` for scope `story` (and also `stories` if it appears)
- `[Discord]` for scope `discord`
- `[Notifications]` for scope `notification` or `notifications`
- `[Actualités]` for scope `news` or `actualites`
- `[Médias]` for scope `media` or `medias`
- `[Modération]` for scope `moderation`
- `[Admin]` for scope `admin` or `administration`
- `[Recherche]` for scope `search`
- `[Technique]` for any other scope not in the list above

Only `[Technique]` items may still be dropped later by the functional-only
filter. In other words, `[Technique]` is a fallback category label, not a
permission to include technical-only changes.

## 6) Translate the change into functional French bullets

For each kept commit:

1. Read the English subject (and body if needed).
2. Write a single bullet in French, user-facing, short, starting with an
   uppercase phrase.
3. The bullet should describe the behavior change, not the code refactor.
4. The bullet should not mention internal class names, files, or technical
   implementation details.

Then format it as:

`* [Catégorie] <French user-visible sentence>`

### De-duplication

If multiple commits describe the same user-visible change in small increments,
group them under one bullet. Otherwise produce one bullet per commit.

## 7) Header: match `jd-esperluettes.fr/versions`

### Version line

Generate:

`Version X.Y.Z`

### Date line

Compute the tag’s creation date (or tagger date) and format it in French as:

`_(DD mois YYYY)_`

French month names:

- janvier, février, mars, avril, mai, juin,
- juillet, août, septembre, octobre, novembre, décembre

If tag date parsing fails, fall back to “today” (current date) but ask a
follow-up question after generating to confirm the correct date.

## 8) Add the retest scope section (deduced from commits)

Insert after the header and before the bullets:

`À retester :`

If the user provided an explicit `À retester:` override, keep it verbatim.
Otherwise deduce retest items from the kept commits:

1. Use the same per-commit filtering and category mapping as for the bullets.
2. Convert each kept commit into a short "vérifier ..." retest instruction.
3. Group retest items by category and keep the list to 3–8 bullets total.

Retest bullets should remain functional and user-facing (UI behavior,
visibility rules, notifications sent/shown, settings toggles, etc.). Avoid
technical implementation language.

Example formatting:

`À retester :
* [Profil] la visibilité des indicateurs d’onglet pour le propriétaire
* [Discord] le lien compte Discord non lié (notifications)
`

## 9) Final output shape

The skill should output exactly this structure:

```text
Version X.Y.Z

_(DD mois YYYY)_

À retester :
<retest details deduced from commits, or kept verbatim from the user override>

* [Catégorie] <bullet 1>
* [Catégorie] <bullet 2>
...
```

If no commits survive the functional-only filter, output the header and an
empty bullets section and set `À retester :` to a short line such as "aucune
vérification spécifique déduite", then ask the user whether those commits
were intentionally technical.

