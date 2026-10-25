# Story — untranslated « Unknown » for a deactivated co-author — functional specification

## 1. Overview

On the story's collaborators page, a co-author whose profile can no longer be resolved (deactivated account) is shown with the English word « Unknown ». It becomes a French label, « Compte désactivé ». A scan of the other places that show story authors found no other English fallback.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Compte désactivé | label shown in place of the name of a co-author with no resolvable profile |

## 3. Roles & visibility

Unchanged: the collaborators page is visible to the same people as today.

## 4. Functional requirements

### 4.1 Collaborators page
1. A collaborator without a resolvable profile is listed with the name « Compte désactivé », as plain text (no profile link, as today with an empty slug).
2. The same label is used wherever the page uses the name (avatar alt text, removal confirmation).
3. Other collaborators are unaffected.

### 4.2 Other author displays
Byline, cards and read-list cards already drop or blank an unresolvable author and contain no English text: unchanged.

## 5. Lifecycle

When the account is reactivated the real name shows again (read at display time).

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| i18n | French string in the Story lang file |
| All others | N/A |

## 7. Decisions confirmed

None beyond the request (label wording suggested there: « Compte désactivé »).

## 8. Out of scope

- The 500 on a deactivated user's profile page (`fix-404-page-theme-500`).
- Adding a fallback label to byline / cards, where there is none today.

## 9. Open questions

- The fallback also fires when a profile row is missing for another reason (hard-deleted user); the label is slightly inaccurate there. Non-blocking.
