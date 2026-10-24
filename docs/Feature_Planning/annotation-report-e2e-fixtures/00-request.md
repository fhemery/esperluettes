# Chapter annotations — e2e fixtures for reaction and co-author reply reports — request

*Leftover from `annotations-v4` VERIFY. Low priority.*

## What I want

E2E seed data so a browser test can see « Signaler » on:

- a **reaction** (an annotation whose body is a single emoji) in the
  « N annotations » pop-up, author view;
- a **co-author's reply**, seen by another author of the chapter.

## Why

VERIFY could not exercise either surface: story 6 (`ANNOTATED`, seeded by
`E2eStorySeeder`) has no reaction row on the reported chapter and no
co-author. The rules are pinned by PHP tests
(`AnnotationCanReportFlagTest`, `ReportAnnotationTest`), so the gap is
rendering only.

## Constraints or ideas I already have

- Overlaps loop-improvements item 6 (E2E seed gaps); can be done together.
- Only worth it if a spec in `e2e/tests/core/` would use it, e.g. extend
  `chapter-annotation-report.spec.ts`.

## Explicitly out of scope

- Re-testing the report rules in the browser.
