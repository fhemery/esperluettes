# Story — untranslated « Unknown » for a deactivated co-author — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Leftover from `fanart-artwork-module` (checkpoint 3bv). Since a co-authored
story now stays visible while only some of its authors are deactivated, the
collaborators page renders a deactivated co-author. `CollaboratorService::getCollaboratorsWithProfiles`
falls back to the hard-coded English `'Unknown'`
(`app/Domains/Story/Private/Services/CollaboratorService.php`). Replace it with a
translated French label (e.g. « Compte désactivé ») and check other places that
show a story's authors (byline, cards) for the same fallback.

## Why

English placeholder in a French UI.

## Constraints or ideas I already have

The 500 on a deactivated user's profile page is tracked separately by
`fix-404-page-theme-500/`.

## Explicitly out of scope

None.
