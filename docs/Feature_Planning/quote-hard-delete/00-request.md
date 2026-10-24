# Quote — reader quote deletion must be a hard delete — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

When a reader deletes one of their own quotes, it must be a hard delete.
Today `QuoteService::delete` soft-deletes it.

## Why

`RestoreOnUserReactivated` restores soft-deleted quotes when a user is
reactivated, so it also brings back quotes the reader deleted themselves. That
is clearly a bug. Found during the `admin-statistics-quotes` PLAN.

## Constraints or ideas I already have

- Reader deletion = hard delete.
- Deactivation / reactivation behaviour must keep working: quotes hidden on
  deactivation are restored on reactivation, but only those, never ones the
  reader deleted.

## Explicitly out of scope

- Moderator access to the admin statistics page.
