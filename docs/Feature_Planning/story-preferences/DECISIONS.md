# Story preferences — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-09 | REFINE | Meaning of “has TW” for hide-from-lists | Same as library `no_tw_only`: only stories with disclosure `no_tw` remain (hides `listed` and `unspoiled`) | — |
| 2 | 2026-08-09 | REFINE | Which surfaces apply hide-from-lists | Discovery lists only: library, dashboard discover, search, ReadList. Direct URL, keep-reading, own authored / author pickers, admin, others’ profile tabs stay unfiltered by this pref | — |
| 3 | 2026-08-09 | REFINE | Hide TW display scope | No TW presence anywhere (names + `no_tw` / unspoiled labels) on every current TW surface | — |
| 4 | 2026-08-09 | REFINE | Settings placement | New settings tab **Histoires** | — |
| 5 | 2026-08-09 | REFINE | Library “sans avertissement” checkbox | Remains independent one-shot filter; not synced to the preference; not removed | — |
| 6 | 2026-08-09 | REFINE | Who can change the prefs | Both `user` and `user-confirmed` | — |
| 7 | 2026-08-09 | REFINE | Defaults | Both preferences off | — |
| 8 | 2026-08-09 | REFINE | Notifications / events / stats / moderation | None | — |
| 9 | 2026-08-10 | DESIGN | Where hide-from-lists pref is applied | A — Story owns it on discovery query paths (library, getRandomStories, searchStories when viewer known); ReadList ORs via Story public helper; listStories does not auto-apply | — |
| 14 | 2026-08-10 | DESIGN | Where hide-from-lists pref is applied | Story owns it on library, getRandomStories, and searchStories (viewer known) only; **no** ReadList / listStories involvement | 9 |
| 10 | 2026-08-10 | DESIGN | How hide-TW-display is enforced | A — Blade gate on `x-story::trigger-warnings` + story-show TW block; DTOs still carry TW data | — |
| 11 | 2026-08-10 | DESIGN | How hide-TW-display is resolved per request | Resolve the bool **once** (controller / outermost view component for that surface) and pass it down as a prop; do **not** call Settings from inside `trigger-warnings` per card | — |
| 12 | 2026-08-10 | DESIGN | How hide-TW-display is resolved per request | `x-story::trigger-warnings` owns the gate and reads via `StoryPreferenceService` with **once-per-request memo**; story-show has its own gate the same way. No prop-drill / no ReadList wiring for display | 11 |
| 13 | 2026-08-10 | DESIGN | Which surfaces apply hide-from-lists | Library `/stories`, dashboard discover, and story search only. **Not** ReadList (own pile is intentional; filtering it empties others’ lists). Still not: direct URL, keep-reading, authored / pickers, admin, profile tabs | 2 |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | No author exemption on discovery lists (own TW stories hidden from library browse when the pref is on) | REFINE | Yes |
| 2 | Others’ profile story tabs are out of scope for the list filter (not listed in discovery surfaces) | REFINE | Yes |
| 3 | Working French labels: tab « Histoires », « Masquer les avertissements », « Masquer les histoires avec avertissement » — copy may be tightened in BUILD | REFINE | Yes |
| 4 | Prefs owned by Story domain (settings registration + list/display behaviour) | REFINE | Yes — DESIGN confirmed |
| 5 | Author create/edit TW form and StoryRef admin catalog are not gated by hide-display (reader surfaces only) | DESIGN | Yes |
