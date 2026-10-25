# Profile — deactivated user's profile page

**Status:** DONE — 2026-10-25 · **Domain(s):** `Profile`

## What it does

Re-check of a reported 500 on `/profile/<slug>` for a deactivated user. The 500 no longer occurs: the page answers the standard 404. No production code changed; a regression test locks the behaviour in.

## Key behaviour

- Deactivation soft-deletes the profile, so route-model binding on `Profile` finds nothing and raises a 404, for every role (guest, `user`, `user-confirmed`, moderator, admin), on the default page and on a tab (`/profile/<slug>/stories`).
- Reactivation restores the profile and the page works again (`UserReactivatedRestoreTest`).
- Consistent with `ProfilePublicApi::getPublicProfile` returning null for a deactivated user.
- The old 500 was not in the profile code: the 404 render crashed on an undefined `$theme`, fixed by `fix-404-page-theme-500`.

## Where the code lives

| Concern | Path |
|---------|------|
| Tests | `app/Domains/Profile/Tests/Feature/UserDeactivatedSoftDeleteTest.php` |

## Decisions worth remembering

- A deactivated profile shows the standard 404, not a "compte désactivé" page (existing behaviour; reversible).
- Moderators and admins get the 404 too.

## Not done

- Non-goals: dedicated "compte désactivé" page; moderator/admin access to deactivated profiles.
- BUILD was folded into REFINE (the test was written there); VERIFY skipped — standard 404 page, covered by the feature test.
- No leftovers pushed to the backlog.
