# Seed the « Autre » reason for the annotation report topic in production

**Status:** DONE — 2026-11-01 · **Domain(s):** `Moderation`

## What it does

A data migration gives the `annotation` moderation topic an active « Autre »
reason on deploy. Before, the reason existed only in the dev `ModerationSeeder`,
so in production the report modal opened from « Signaler » on an annotation had
nothing to select. No schema, model or UI change.

## Key behaviour

- `up()` inserts one `moderation_reasons` row (`topic_key=annotation`,
  `label=Autre`, `sort_order=0`, `is_active=true`) only if the topic has no
  reason at all (not merely no « Autre »): respects a moderator's own set.
- Idempotent: a second run inserts nothing.
- `down()` is a deliberate no-op; reports may reference the row.
- After insertion the row is normal admin-managed data; the migration runs once
  and never re-creates a reason a moderator renamed, deactivated or deleted.
- The migration also runs in every test DB, so test DBs have an `annotation`
  « Autre » row. Moderation and Comment annotation suites pass unaffected.

## Where the code lives

| Concern | Path |
|---------|------|
| Migration | `app/Domains/Moderation/Database/Migrations/2026_11_01_000001_seed_annotation_other_reason.php` |
| Tests | `app/Domains/Moderation/Tests/Feature/SeedAnnotationOtherReasonMigrationTest.php` |

The test `require`s the migration file and runs `up()`: seeds on empty topic,
leaves existing reasons untouched, idempotent.

## Extension points used

None; plain `DB::table` (not the model, so the migration does not depend on a
model that may change).

## Decisions worth remembering

- Data migration rather than a release-note/ops step: release notes are
  generated from commits and have no ops-action slot, so a manual step is what
  gets forgotten (that was the bug).
- Guard is "topic has any reason", not "« Autre » exists".
- Label is French « Autre »; the dev seeder's `Other` label is unchanged.

## Not done

- Non-goals: any reason beyond « Autre »; changing the seeder label.
- Cut mid-build: nothing. Plan and code agree.
- VERIFY: ran `migrate` on the dev DB, `annotation` got one active « Autre »;
  modal not driven in the browser (rendering covered by
  `core/chapter-annotation-report.spec.ts`).
- Pushed back to the backlog: `fanart-report-prod-reason/` — the `fanart` topic
  has the same dev-seeder-only gap.
