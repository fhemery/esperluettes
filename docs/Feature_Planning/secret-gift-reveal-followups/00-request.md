# Secret Gift — reveal follow-ups — request

*Leftover pushed back by `media-sound-upload/` WRAP (VERIFY findings).*

## What I want

1. **Write the three feature tests VERIFY pushed back to BUILD and that were
   never written** (VERIFY 2026-10-03 checked them by hand only):
   - guest on `secret-gift.sound` (and `download-sound`) → redirect to login;
   - recipient of an **ARCHIVED** activity → 200 on sound / download (the
     ARCHIVED branch of `SecretGiftService::canViewSound()` /
     `canViewImage()` has no test);
   - the reveal of a gift **without a sound** renders no `<audio>` and no sound
     link (Blade test).
2. **Decide what a recipient sees once the activity is ARCHIVED.** The spec says
   the recipient can view the gift once ENDED *or* ARCHIVED, and the serve routes
   honour that, but the activity page itself 404s once ARCHIVED
   (`ActivityService::findVisibleBySlugOrFail()` hides DRAFT and ARCHIVED for
   every type). So archiving makes the reveal unreachable from the UI while the
   file URLs keep answering. Either is defensible; pick one and align.
3. **Open the reveal tab once the activity is ENDED.** The Secret Gift page
   always opens on "Mon cadeau à préparer" (`initial="prepare"` is
   unconditional), so a recipient has to click "Mon cadeau reçu" to see the gift.

## Why

(1) closes a test gap on visibility rules; (2) and (3) are UX inconsistencies
found while verifying, both pre-existing.

## Constraints or ideas I already have

- Visibility logic stays in `SecretGiftService::canView*`.
- (2) touches the base Calendar visibility rule, shared by every activity type —
  a Secret-Gift-only exception may be preferable.

## Explicitly out of scope

- Storage of gift files (done in `media-sound-upload`).
