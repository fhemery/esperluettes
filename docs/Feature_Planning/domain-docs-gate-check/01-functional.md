# Domain docs gate check — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

The gate's documentation step must fail when any domain under `app/Domains/` is
missing its documentation trio (`README.md`, `AGENTS.md`, `CLAUDE.md`) or is
absent from the root `AGENTS.md` Domain Registry. This closes the drift hole
left by `domain-claude-md-shims`: a new domain can currently ship without docs
or a registry row and stay green. Developers and agents are the audience — there
is no end-user UI.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Domain | Immediate child directory of `app/Domains/` (e.g. `Calendar`, `Follow`) |
| Documentation trio | The three files `document-domain` produces: `README.md`, `AGENTS.md`, `CLAUDE.md` |
| Domain Registry | The table under `## Domain Registry` in the repository root `AGENTS.md` |
| Docs step | The documentation-consistency step of `pnpm run gate` |

## 3. Roles & visibility

N/A — developer tooling only. No guest/user/moderator surfaces.

| Role | Can see | Can do |
|------|---------|--------|
| Anyone running the gate | Failures reported on stderr / gate summary | — |

## 4. Functional requirements

### 4.1 Domain discovery

1. The docs step considers every immediate subdirectory of `app/Domains/` to be
   a domain.
2. Non-directory entries under `app/Domains/` are ignored.

### 4.2 Documentation trio presence

1. For each domain, the docs step requires all three files at the domain root:
   `README.md`, `AGENTS.md`, `CLAUDE.md`.
2. Missing any of the three is a failure. The report names the domain and which
   file(s) are missing.
3. `CLAUDE.md` must contain exactly the shim contract from
   `domain-claude-md-shims`: one line `@AGENTS.md` plus a trailing newline, and
   nothing else. Wrong content is a failure (not only missing file).

### 4.3 Domain Registry membership

1. For each domain on disk, the docs step requires a corresponding row in the
   root `AGENTS.md` Domain Registry whose **Path** column is
   `` `app/Domains/<Name>` `` matching that directory name.
2. Absence is a failure. The report names the domain.
3. Conversely, every Domain Registry row whose Path is under `app/Domains/`
   must point at a directory that exists on disk. An orphan registry row is a
   failure (catches typos and renamed domains).

### 4.4 Gate behaviour

1. These checks run as part of the existing docs step of `pnpm run gate` (same
   place as the Feature_Planning link ban and relative-link resolution).
2. Any failure fails the docs step (and therefore the gate), with a clear
   message listing every violation found in one run (not fail-fast on the first).
3. When the tree is already compliant, the docs step stays green with no new
   noise.

### 4.5 Making today's tree green

1. `app/Domains/Follow/` is today the only domain missing the trio and a
   registry row. Closing this task includes documenting Follow (via the
   `document-domain` contract) and adding its Domain Registry row so the new
   checks pass on a clean tree — not leaving Follow as a permanent exception.

## 5. Lifecycle

N/A — no persisted user data. When a domain directory is added, renamed, or
removed, the next docs-step run must reflect that state (fail until docs +
registry match).

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — tooling |
| Visibility / privacy | N/A |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A — filesystem + registry consistency only |
| Media | N/A |
| Search | N/A |
| i18n | N/A — error messages may stay English like the rest of `check-docs.js` |
| Mobile | N/A |
| Accessibility | N/A |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | What must every domain have? | `README.md`, `AGENTS.md`, `CLAUDE.md` at domain root (request) |
| 2 | Registry requirement? | Every domain must appear in root `AGENTS.md` Domain Registry (request) |
| 3 | Where does the check run? | Inside the gate docs step (`pnpm run gate` → docs) (request) |
| 4 | How are domains discovered? | Immediate subdirs of `app/Domains/` (assumption A1) |
| 5 | Enforce CLAUDE shim content? | Yes — exactly `@AGENTS.md` + trailing newline (assumption A2; prior WRAP) |
| 6 | Bidirectional registry check? | Yes — disk→registry and registry→disk (assumption A3) |
| 7 | Follow exception? | No — document Follow and register it so the gate is green (assumption A4; prior WRAP filed this task to catch Follow) |
| 8 | PHPUnit assertion? | No — mechanical gate check only (assumption A5; prior WRAP rejected PHPUnit for this) |

## 8. Out of scope

- Rewriting prose of existing domain `README.md` / `AGENTS.md` (except Follow,
  which has none yet and must be created).
- Running the docs check from husky pre-commit or adding a dedicated CI job
  outside `pnpm run gate`.
- Checking content quality of `README.md` / `AGENTS.md` (only presence +
  CLAUDE exact shim).
- Validating Domain Registry columns beyond Path (Responsibilities / Tables
  wording is out of scope).
- Scoping the docs step to changed domains only (docs stay always-on).
- End-user UI, routes, permissions, or database changes unrelated to Follow's
  documentation.

## 9. Open questions

None blocking.
