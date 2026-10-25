# Story — untranslated « Unknown » for a deactivated co-author — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Translated « Compte désactivé » fallback on the collaborators page | S | — | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Translated « Compte désactivé » fallback on the collaborators page

**Goal.** A collaborator whose profile cannot be resolved (deactivated account)
is listed on the collaborators page as « Compte désactivé » instead of the
English « Unknown » (architecture §3, §6).

**Context (verified during PLAN).** Deactivating a user soft-deletes their
profile (`Profile/Private/Listeners/SoftDeleteProfileOnUserDeactivated.php`), so
`ProfileApi::getPublicProfiles` (`app/Domains/Profile/Private/Api/ProfileApi.php`)
returns `null` for that user id, and
`CollaboratorService::getCollaboratorsWithProfiles` falls back on line 46. The
view `app/Domains/Story/Private/Resources/views/collaborators/index.blade.php`
already renders a plain `<span>` (no link) when `slug === ''`, and reuses
`display_name` for the avatar alt and the removal-confirmation body — so the one
string change covers all three uses. No view change.

**Deliverables.**
- `app/Domains/Story/Private/Services/CollaboratorService.php` — replace
  `?? 'Unknown'` with `?? __('story::collaborators.deactivated_account')`.
- `app/Domains/Story/Private/Resources/lang/fr/collaborators.php` — add
  `'deactivated_account' => 'Compte désactivé',`.

**Tests.** Strengthen the existing case in
`app/Domains/Story/Tests/Feature/Collaborators/CollaboratorPageTest.php`:
`it('renders the page when a co-author has been deactivated')`. After the
existing `assertOk()`, add:
- `$response->assertSee(__('story::collaborators.deactivated_account'));` —
  this string can only reach the page through the fallback branch, so it
  **proves the deactivated co-author actually reaches the fallback** (i.e. the
  profile lookup returns null after `deactivateUserById`; if the profile were
  still served, e.g. from a stale cache, the assertion fails).
- `$response->assertDontSee('Unknown');`
- `$response->assertDontSee(route('profile.show', ['profile' => <bob's profile slug>]))`
  is optional; if the slug is not cheaply available, skip it — the label
  assertion is the load-bearing one.

Write the assertions first, watch the test fail (the label key does not exist
yet, so `__()` returns the key and `assertSee` fails against « Unknown »), then
make the change.

Run: `./vendor/bin/sail artisan test --filter=CollaboratorPageTest`.

**Acceptance.**
- ✅ The strengthened test fails before the change and passes after.
- ✅ With a deactivated co-author, the collaborators page shows « Compte
  désactivé » and no « Unknown ».
- ✅ Other cases of `CollaboratorPageTest` still pass.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Collaborators page, co-author deactivated (author view, desktop) | Row shows « Compte désactivé » as plain text, no profile link, default avatar; other collaborators unchanged | |
| Collaborators page, remove confirmation for the deactivated co-author | Modal body names « Compte désactivé » | |
| Collaborators page, co-author reactivated | Real display name and profile link are back | |
| Collaborators page, mobile width | Label does not break the row layout | |

## Open items

- None blocking. Non-blocking (from `01-functional.md` §9): the label is
  slightly inaccurate when a profile is missing for another reason (hard-deleted
  user). Accepted as-is.
