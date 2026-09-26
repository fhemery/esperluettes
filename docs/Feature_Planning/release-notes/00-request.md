# Release mechanism with release note and rollout plan — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

A release mechanism that produces a release note and a rollout plan: the
manual steps I have to do on production around a deploy.

## Why

Some changes need manual action in production. For example, when the
`cleanup-feature-flags` skill removes a toggle from the code, its
`config_feature_toggles` row has to be deleted by hand in the admin UI, after
the deploy. I need something that tells me to do so.

## Constraints or ideas I already have

- Row deletion stays manual (admin UI), not a data migration.
- A `generate-release-note` skill already exists
  (`.agents/skills/generate-release-note/`): French user-facing note plus an
  "À retester" section, from the commits between two tags. The rollout plan
  probably extends it rather than starting from scratch.

## Explicitly out of scope

