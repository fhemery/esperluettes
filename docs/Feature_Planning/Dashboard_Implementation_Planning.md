# Dashboard Implementation Planning

## Scope & Order of Delivery
Implement widgets top-to-bottom so the page grows incrementally:
1) News carousel (compact)
2) Bienvenue panel
3) Continuer à écrire (keep writing)
4) Continuer à lire (keep reading)
5) Side placeholder image
6) Histoires à découvrir (discover list)

---

## [Done] 1) News Carousel (compact 10:1)

- **User Story**: As a user, I see the homepage News carousel at the top of my dashboard in a compact height so I can notice new announcements without it taking too much space.
- **Acceptance Criteria**:
  - Carousel renders in dashboard with `size="compact"` enforcing 10:1 aspect ratio.
  - Same behavior as homepage: auto-advance, controls, A11y.
  - Responsive and accessible.
- **Tech Notes**:
  - Add a `size` prop to the carousel partial/component; apply a 10:1 ratio class.
  - Respect reduced motion.

## 2) Bienvenue Panel

- **User Story**: As a logged user, I want a personalized greeting that summarizes my status and activity.
- **Acceptance Criteria**:
  - Shows: "Bienvenue <display_name>".
  - Shows: "Tu es dans le Jardin depuis le <date join> — Tu es actuellement une <role label>".
  - Shows: "Tu as posté <nb stories> stories et <nb comments>".
  - Date formatted with frontend `DateUtils`.
  - Role label comes from `ProfilePublicApi` (localized label).
  - Translations via namespaced PHP files.
  - Widget shows an inline error state if any API call fails.
- **API/Service**:
  - `ProfilePublicApi::getDisplayName($userId)`, `getJoinDate($userId)`, `getRoleLabel($userId)`.
  - `StoryPublicApi::countAuthoredStories($userId)`.
  - `CommentPublicApi::countCommentsByUser($userId)`.

## 3) Continuer à écrire (Keep Writing)

- **User Story**: As an author, I want to quickly continue editing my most recent story.
- **Acceptance Criteria**:
  - Displays story `card` for story with latest `Chapter.last_edited_at` by user; fallback to latest `Story.updated_at` if no chapters.
  - Below-card button: "Ajouter un chapitre" linking to `/stories/<slug-with-id>/chapters/create`.
  - If available chapter count `<= 0`, button disabled with tooltip "Plus de crédits, allez commenter !".
  - If the user has never created a story: show `public/images/errors/404.png` with label "Pas encore d'histoire" and a button linking to `route('story.create')`.
  - Widget degrades gracefully with an inline error state on failure.
- **API/Service**:
  - `StoryPublicApi::getLastEditedStoryForUser($userId)`.
  - `StoryPublicApi::getAvailableChapterCount($userId)`.

## 4) Continuer à lire (Keep Reading)

- **User Story**: As a reader, I want to resume my reading from where I left off.
- **Acceptance Criteria**:
  - Displays story `card` for latest `reading_progress` record.
  - Button below the card: "Lire le prochain chapitre" linking to next chapter.
  - If already at last chapter: button disabled with text "Vous avez tout lu" and tooltip "Histoire terminée ! Explorez la liste en-dessous pour trouver une nouvelle pépite !".
  - If no current read: show `public/images/errors/404.png` with label "Aucune lecture en cours ? Découvrez d'autres histoires !" and a button to `route('stories.index')`.
  - Widget degrades gracefully with an inline error state on failure.
- **API/Service**:
  - `StoryPublicApi::getNextChapterForReadingProgress($userId)` (or `getNextChapterAfter`).

## 5) Side Placeholder Image

- **User Story**: As a user, I want the right-side area to hold a placeholder while we design the community widget.
- **Acceptance Criteria**:
  - Renders `public/images/errors/404.png` with alt and caption: "Débrouissage en cours".
  - Responsive and accessible.

## 6) Histoires à découvrir (Discover list)

- **User Story**: As a user, I want a scrollable set of recommendations to discover new stories.
- **Acceptance Criteria**:
  - Fetch 8 random stories, excluding stories authored by the current user.
  - Horizontally scrollable list using the reusable component.
  - Visible slides follow breakpoints: 1 / 2 / 4 with 8 / 4 / 2 pages.
  - Desktop arrows around the list (not on cards); Mobile slide/drag.
  - Header action: "Tout voir" -> `route('stories.index')`.
  - Footer CTA (translated): "Want more ? Go explore library" linking to `route('stories.index')`.
  - If fewer than 8 stories returned, fill with "fake cards" that link to the library.
  - Widget degrades gracefully with an inline error state on failure.
- **API/Service**:
  - `StoryPublicApi::getRandomStories($limit=8, $userContext)`.

---

## Cross-cutting

- **Routing**: `/dashboard` named `dashboard.show` under `App\Domains\Dashboard`.
- **View**: `app/Domains/Dashboard/Private/Resources/views/index.blade.php`.
- **Components**: Reuse `app/Domains/Story/Private/Resources/views/components/card.blade.php`.
- **A11y**: Focusable controls, `aria-label`s, keyboard L/R for discover, respect reduced-motion.
- **i18n**: PHP namespaced translations per domain; titles/buttons translatable.
- **Errors**: Per-widget error handling, no global failure.
- **Testing Notes**: Feature tests per widget (rendering, empty states, disabled buttons, routing). Service tests for API methods and selection logic.
