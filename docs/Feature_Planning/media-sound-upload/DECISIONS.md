# Gift sound on Media — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-03 | REFINE | Where do gift sounds live? | Media owns them: private non-image store with Range/seeking, cleaned by Media GC, served by SecretGift after `canViewSound`; existing files migrated | — |
| 2 | 2026-10-03 | REFINE | Replace/remove timing | Detached at once; file reclaimed by GC after grace period (image precedent), no inline delete | — |
| 3 | 2026-10-03 | REFINE | Replay of the full flow (01-functional §4–§5) | Confirmed as written | — |
| 4 | 2026-10-03 | DESIGN | How Media stores a non-image private file | New `MediaPublicApi::storePrivateFile()` raw store (no ImageService); GC treats every private file as an original | — |
| 5 | 2026-10-03 | DESIGN | Where HTTP Range lives | Inside `MediaPublicApi::stream()` — `BinaryFileResponse` for private paths; SecretGift's Range code deleted | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Upload UX (drag/drop, preview, remove) stays identical | REFINE | yes |
| 2 | Existing sounds moved by a DB migration, missing files reported not fatal (image precedent) | REFINE | yes |
| 3 | Widget is `<x-media::sound-field>` (sound-specific, not generic), form contract kept (`{name}` + `{name}_remove`) | DESIGN | yes |
