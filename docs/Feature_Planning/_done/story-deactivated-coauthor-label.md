# Story — untranslated « Unknown » for a deactivated co-author

**Status:** DONE — 2026-10-25 · **Domain(s):** `Story`

## What it does

On the collaborators page, a collaborator whose profile cannot be resolved
(deactivated account — the profile is soft-deleted, so
`ProfileApi::getPublicProfiles` returns `null`) is now listed as
« Compte désactivé » instead of the hard-coded English `'Unknown'`. One string
change in `CollaboratorService::getCollaboratorsWithProfiles`; no view change.

## Key behaviour

- The fallback `display_name` feeds three uses in the view: the row label, the
  avatar alt, and the removal-confirmation modal body — all now show the label.
- The row stays plain text (no profile link) because the view already renders a
  `<span>` when `slug === ''`; avatar falls back to `''` (default avatar).
- Reactivating the user restores the real name and link (nothing stored).

## Where the code lives

| Concern | Path |
|---------|------|
| Service (fallback) | `app/Domains/Story/Private/Services/CollaboratorService.php` |
| Lang key `story::collaborators.deactivated_account` | `app/Domains/Story/Private/Resources/lang/fr/collaborators.php` |
| View (unchanged) | `app/Domains/Story/Private/Resources/views/collaborators/index.blade.php` |
| Test | `app/Domains/Story/Tests/Feature/Collaborators/CollaboratorPageTest.php` — case « renders the page when a co-author has been deactivated » asserts the label and no `Unknown` |

## Decisions worth remembering

- Label wording « Compte désactivé » taken from the request (assumption, not asked).
- Scope limited to the collaborators page: it was the only place with an English
  author fallback; bylines and cards have no such fallback (checked at REFINE).
- The label is slightly inaccurate when a profile is missing for another reason
  (e.g. hard-deleted user). Accepted as-is.
- VERIFY was intentionally skipped: a single lang-string swap, covered by the
  Pest view test. The plan's visual QA checklist was never filled.

## Not done

- The 500 on a deactivated user's profile page is out of scope here and stays
  with the active backlog task `fix-404-page-theme-500/`.
- Optional test assertion that no profile link is rendered for the deactivated
  co-author was skipped (the label assertion is the load-bearing one).
- No e2e spec was written; nothing to retire. No new backlog rows.
