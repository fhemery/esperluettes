# Upgrade pnpm to 12 — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Move the repo from pnpm 11.21 to the current pnpm 12 (12.6.0 when this was
filed), the next time dependencies are upgraded.

## Why

pnpm 12 is out and the repo pins 11.x through `packageManager`. Noticed during
the 2026-09-27 dependency upgrade (pnpm printed the update notice).

## Constraints or ideas I already have

- ADR `docs/adr/0001-use-pnpm.md` pins pnpm 11.x and relies on its behaviour:
  `minimumReleaseAge` (now explicit at 48 h in `pnpm-workspace.yaml`),
  `strictDepBuilds` with the `allowBuilds` allowlist, `auditConfig`. Check
  each still works the same in 12, and update the ADR.
- CI (`.github/workflows/pr-tests.yml`) and the `packageManager` field must
  move together; `pnpm install --frozen-lockfile` must pass.

## Explicitly out of scope

Upgrading other dependencies — that is the `upgrade-dependencies` skill's job.
