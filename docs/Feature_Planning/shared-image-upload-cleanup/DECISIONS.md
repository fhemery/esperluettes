# `<x-shared::image-upload>` cleanup — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-07-31 | REFINE | Accept leftover request as `00-request.md`? | Accepted draft as-is (constraints: keep Shared lang; do not invent Media semantics for private gifts). | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Collapse `<x-shared::image-upload>` into SecretGift (delete from Shared), rather than keep it as a deliberate Shared exception. | REFINE | Yes — keep-in-Shared is a one-line non-goal instead |
| A2 | Do **not** move gift images onto Media / `<x-media::image-field>` (private disk, auth-gated serve, no variants/GC). | REFINE | Expensive — would need Media private-disk design |
| A3 | Also collapse `<x-shared::sound-upload>` into SecretGift in the same pass (sole consumer, same preparation form). | REFINE | Yes — can leave sound-upload in Shared |
| A4 | No behaviour change to upload/serve/remove; existing SecretGift feature tests are the acceptance bar; no new E2E. | REFINE | Yes |
| A5 | Leave Shared `image-upload` lang in place; Story keeps borrowing three keys; do not duplicate/move those keys into Story in this task. | REFINE | Yes — Story could own its strings later |
| A6 | Register relocated widgets as anonymous Blade components under `secret-gift::` (mirror Shared), not class components. | DESIGN | Yes |
| A7 | Widgets keep resolving Shared lang keys; no Calendar lang copies in this task. | DESIGN | Yes |
| A8 | No schema/API/route/Media changes; existing SecretGift feature tests are the acceptance bar. | DESIGN | Yes |
