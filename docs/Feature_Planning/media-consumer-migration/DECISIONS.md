# MultiEdit — migrate the remaining ImageService consumers — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-07-29 | REFINE | Do the Calendar and StaticPage admin forms get the Media reuse picker? | Yes, both — full `<x-media::image-field>` adoption. Entails dropping synchronous file deletion in favour of the 7-day `media:gc` sweep. | — |
| 2 | 2026-07-29 | REFINE | Does Profile join the Media GC loop (register a `MediaUsageProvider`)? | No. Expose `saveSquareJpg` through `MediaPublicApi` and nothing else; Profile keeps managing its own files. Remove the bogus `profile` scope rather than fixing it to `profile_pictures`. | — |
| 3 | 2026-07-29 | REFINE | Add alt / caption inputs to the two admin forms? | No — off for both (`showAlt=false`, `showCaption=false`). No schema change; keep today's alt behaviour (activity name / empty). | — |
| 4 | 2026-07-29 | REFINE | Show the "used N times" usage count in those forms? | No. | — |
| 5 | 2026-07-29 | REFINE | `GET /media/library` is `auth`-only — tighten it, or raise a backlog row? | Neither. No privacy required on media listing for now and possibly ever. Leave as-is, no backlog row. Supersedes assumption A5. | A5 |
| 6 | 2026-07-29 | REFINE | Drop the unused `string` branch of `StaticPageService::processHeaderImage()`? | Yes — clean up what is unused. Confirms assumption A3. | A3 |
| 7 | 2026-07-29 | DESIGN | How far does the display migration go — component, API URLs, or storage only? | `<x-media::image>` in all three public views (StaticPage show, Calendar activity show, Calendar activity card). Accepts a layout risk on the card's fixed 230×220 crop, covered at VERIFY. | — |
| 8 | 2026-07-29 | DESIGN | How does `saveSquareJpg` reach Profile, given it stays out of GC? | Raw target-path passthrough: `MediaPublicApi::saveSquareJpg($targetPath, $file, $size, $quality)`. Media owns the disk, caller owns the path — the one deliberately non-scoped method. Rejected: a `profile-pictures` scope (would enrol avatars in the sweep) and leaving it in Shared (blocks the relocation). | — |
| 9 | 2026-07-29 | DESIGN | Is `<x-shared::image-upload>` cleaned up or moved to Media in this task? | Neither — keep it in Shared. This task strips it to a single consumer (SecretGift, private `local` disk, no Media semantics), so moving it to Media is wrong and deleting it means reworking the gift flow. WRAP proposes a follow-up backlog row. Its lang file stays regardless: Story's cover tab uses the strings without the component. | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Existing dated files (`activities/YYYY/MM/`, `static-pages/YYYY/MM/`) stay where they are: displayed forever, absent from the reuse picker, never swept. Same grandfathering News already received. | REFINE | Yes — a later backfill could flatten them. |
| A2 | The `activities` scope is added and both wrong entries (`calendar`, `profile`) are removed from `FLAT_SCOPES`. Neither wrong scope has files on disk under its declared name. | REFINE | Yes, cheap. |
| A3 | `StaticPageService::processHeaderImage()`'s `string` branch is dead and can be dropped (no caller found). | REFINE | Yes — DESIGN re-greps to confirm. |
| A4 | SecretGift's `gift_image_path` (private `local` disk) is out of scope; Calendar's usage provider claims only `activities.image_path`. | REFINE | Yes. |
| A5 | The `auth`-only `GET /media/library` authorisation is left as-is; a backlog row is proposed at WRAP instead. | REFINE | Yes. |
