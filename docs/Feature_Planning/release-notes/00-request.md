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

## Pending production steps (must appear in the next rollout plan)

From the `chore/feature-flag-cleanup` branch, to do after its deploy:

- Delete the feature toggles `calendar / enabled`,
  `discord / discord_notifications`, `message / active` and
  `moderation / reporting` in the admin UI.
- Drop the `messages` and `message_deliveries` tables by hand: the Message
  domain was deleted, and no migration drops them.

## Explicitly out of scope

