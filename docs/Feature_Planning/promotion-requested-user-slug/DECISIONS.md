# Add requester slug to auth.promotion.requested notification — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-25 | REFINE | Mode | auto (user ran the six tasks sequentially, available for questions) | — |
| 2 | 2026-10-25 | REFINE | Backfill existing notifications? | No (from request) | — |

## Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Expose the slug as `user_slug` in the data payload; the bot builds the URL. No URL field added | REFINE | yes |
| 2 | In-app display text is unchanged (no profile link) | REFINE | yes |
| 3 | Branch: stay on `fix/misc-small-fixes`, commit there (user instruction) | REFINE | yes |
| 4 | Defaulted trailing constructor arg `userSlug = ''` | DESIGN | yes |
| 5 | 2026-10-25 | BUILD | No-profile test: Notification rejects a source user without a profile (invalid_source_user), so the end-to-end case cannot notify; test asserts the dispatched content via mocks instead | mocked service-level test | — |
