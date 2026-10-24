# Chapter annotations — seed the « Autre » reason for the annotation report topic in production — request

*Leftover from `annotations-v4` WRAP (plan open item, phase 2).*

## What I want

The `annotation` moderation topic (shipped by `annotations-v4`) must have at
least one reason in production, « Autre », as its spec promised ("the topic
ships with a single seeded reason").

## Why

The reason was added to `ModerationSeeder`
(`app/Domains/Moderation/Database/Seeders/ModerationSeeder.php`), which is a
dev seeder: reasons are admin-created in production. Unless the seeder runs on
deploy, the topic has **no reason** there and the report modal opened from
« Signaler » on an annotation has nothing to select, so no report can be filed.

## Constraints or ideas I already have

- Options: a data migration inserting « Autre » for `annotation` if absent;
  or a release-note action for a moderator to create it in the reasons admin.
  Pick one at REFINE; possibly just an ops step, no code.
- Check the reason model's columns (`topic_key`, label, sort order, active)
  in `app/Domains/Moderation`.

## Explicitly out of scope

- Any reason set beyond « Autre ».
