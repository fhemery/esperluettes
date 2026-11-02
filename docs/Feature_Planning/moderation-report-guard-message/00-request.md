# Moderation — return the guard's refusal message to the report modal — request

*Leftover from `moderation-report-refusals` WRAP.*

## What I want

When a topic's `ReportGuardInterface` refuses a report, the report modal shows
the guard's specific refusal message, not the generic error.

## Why

`ReportController::store()` answers every authorization failure with the same
generic `moderation::report.error` text. The JS (`moderation.js`) already shows
`data.message` on a 403, but nothing sends a specific one, so the user cannot
tell why the report was refused.

## Constraints or ideas I already have

- Guards currently return a bool (`canReport`); the refusal reason has to come
  from somewhere (a message on the interface, or a result object).
- Keep topics that do not implement the guard unaffected.

## Explicitly out of scope

- Changing who may report what.
