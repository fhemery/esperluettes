# Story — untranslated « Unknown » for a deactivated co-author — architecture

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Story domain: `CollaboratorService::getCollaboratorsWithProfiles` fallback and `story::collaborators` lang file.

## 2. Data model

None.

## 3. PHP architecture

The English literal is replaced by `__('story::collaborators.deactivated_account')` (« Compte désactivé »). The view already renders a plain span when the slug is empty.

## 4. Frontend architecture

N/A.

## 5. Deptrac

No new edge.

## 6. Testing strategy

Strengthen the existing `CollaboratorPageTest` case "renders the page when a co-author has been deactivated" to assert the French label and the absence of « Unknown ».

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Label | « Compte désactivé »; « Compte supprimé »; « Utilisateur inconnu » | « Compte désactivé » | wording from the request |
