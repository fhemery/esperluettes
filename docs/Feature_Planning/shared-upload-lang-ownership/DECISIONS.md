# Shared `image-upload` lang ownership — decisions log

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-07-31 | REFINE | Destination for the three borrowed keys? | Move into Story's cover lang namespace; delete Shared `image-upload.php`. Do not redesign cover onto `<x-media::image-field>` (out of scope). | — |

## Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Only the three Story-used keys move; unused Shared keys (`preview_alt`, `recommended_*`, `delete`, `cancel`) are deleted with the file. | REFINE | Yes |
| A2 | Mode stays `auto`; VERIFY skipped unless user asks (copy-only change). | REFINE | Yes |
