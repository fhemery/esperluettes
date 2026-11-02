# Fanart — seed a report reason for the fanart moderation topic in production — request

*Leftover from `annotation-report-prod-reason` WRAP.*

## What I want

The `fanart` moderation topic must have at least one active reason in
production, so the report modal opened from a fanart can be submitted.

## Why

The reason exists only in `ModerationSeeder`
(`app/Domains/Moderation/Database/Seeders/ModerationSeeder.php`), a dev seeder;
production reasons are admin-created. Same gap as the `annotation` topic had.

## Constraints or ideas I already have

- Same fix as `annotation-report-prod-reason`: a data migration in
  `app/Domains/Moderation/Database/Migrations/` that inserts « Autre » only when
  the topic has no reason, no-op `down()`. Model it on
  `2026_11_01_000001_seed_annotation_other_reason.php` and its test.
- Check which other topics (`profile`, `story`, `chapter`, `comment`) are in the
  same situation before deciding the scope.

## Explicitly out of scope

- Any reason set beyond « Autre ».
