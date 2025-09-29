# Dashboard Feature Specification

## Overview
The Dashboard aggregates personalized widgets for a logged-in user. It reuses components from other domains and fetches user-centric data via public APIs. The goal is a performant, accessible, and mobile-friendly page composed of modular Blade components.

## Page Layout & Components

1. **Top: News Carousel (10:1 ratio)**
   - Reuse the existing News carousel component.
   - Requirement: support a compact mode with a fixed aspect ratio of **10:1** (width:height). Example: 800px wide → 80px high.
   - Implementation note: expose a prop `size="compact"` and apply the responsive aspect class.
   - Behavior: same as homepage carousel (auto-advance, controls), but visually shorter.

2. **Bienvenue Panel (cross-domain data)**
   - Copy: 
     - "Bienvenue <display_name>"
     - "Tu es dans le Jardin depuis le <date user joined> — Tu es actuellement une <role label>"
     - "Tu as posté <nb stories> stories et <nb comments>"
   - Data sources:
     - Profile: display name, join date, current role label (localized name, not slug).
     - Story: count of authored stories (all authored, regardless of visibility/draft state).
     - Comment: count of comments by user.
   - Implementation: Blade component that calls public APIs (Profile, Story, Comment) through a server-side service adapter. Use frontend `DateUtils` for localized date rendering. No direct DB in controller per architecture rules.

3. **Continuer à écrire (keep writing)**
   - Content: Show the `card` component from Story domain for the last story edited by the user.
   - Selection logic:
     - Prefer story with the most recently edited `Chapter` by the user, based on `chapters.last_edited_at` (see `app/Domains/Story/Private/Models/Chapter.php`).
     - If no chapter exists, pick the user-authored `Story` with the latest `stories.updated_at`.
   - Below the card (outside the card): Accent button "Ajouter un chapitre" linking to the new chapter page for that story (`/stories/<story-slug-with-id>/chapters/create`).
   - Chapter/comment cap: if the user's available chapter counter `<= 0`, disable the button.
   - Empty state: if the user hasn't started writing any story, show `public/images/errors/404.png` with label "Pas encore d'histoire" and a primary button linking to `route('story.create')`.

4. **Continuer à lire (keep reading)**
   - Content: Show the `card` of the story with the latest `reading_progress` record for the user.
   - Below the card (outside the card): Button "Lire le prochain chapitre" linking to the next chapter after the last read chapter.
    - Edge cases:
     - If the next chapter does not exist (already at last), disable the button and show the label "Vous avez tout lu".
     - Respect visibility/authorization.
   - Empty state: if the user has no current read, show `public/images/errors/404.png` with label "Aucune lecture en cours ? Découvrez d'autres histoires !" and a button to `route('stories.index')`.

5. **Side Image Placeholder**
   - Empty section hosting an image for now. Use `public/images/errors/404.png` as a placeholder.
   - Alt text: "Débrouissage en cours" and show the same text as a caption below the image.

   - Data: 8 random stories + one placeholder to see more (button redirecting to the library)
   - Layout: horizontally scrollable list that adapts visible slides by breakpoint: 1 / 2 / 4 visible cards on small / medium / large screens respectively. With 8 fetched stories, that yields 8 / 4 / 2 pages.
   - Component: generic card-list to be reused with other card sources (lives likely under `Shared`).
   - Cards rendered via `app/Domains/Story/Private/Resources/views/components/card.blade.php`.
    - Interactions:
      - Desktop: visible left/right sizable arrows around the list container (not on the cards) to scroll pages.
      - Mobile: slide/drag gesture support.
      - Visible slides: 1 / 2 / 3 / 4 depending on device ratio (9 / 5 / 3 / 3 pages respectively with additional placeholder).
      - Header action: "Tout voir" link that redirects to the library (route name: `stories.index`; see `app/Domains/Story/Private/Resources/lang/fr/index.php`).
      - Footer CTA: translated button below the list — "Want more ? Go explore library" → links to `route('stories.index')`.
   - Data constraints: exclude stories authored by the current user from the random set. If fewer than 8 remain, fill the remainder with "fake cards" that show a "Discover more" action linking to `route('stories.index')`.

## Component Contracts

- **Reusable Scrollable Card List** (Blade component, likely under `Shared`)
  - Props: `items` (array of story DTOs), `title`, `viewAllHref` (route to `stories.index`), `arrows=true`, `slidesOnMobile=true`, `visibleCounts={ sm:1, md:2, lg:4 }`, `footerCtaHref`.
  - Consistency: Reuse existing styles and Tailwind utility classes. Accent color for primary actions.
  - Empty States: When provided fewer than requested items, support injecting placeholder "fake cards" with a CTA to the library.
  - Accessibility: Inherit carousel A11y from News; ensure buttons have `aria-label`s; meaningful headings per section.
    - Discover scroller A11y: focusable Prev/Next with disabled states, keyboard L/R support, proper focus management, optional `aria-live="polite"` updates.
  - Tooltips:
    - Disabled "Ajouter un chapitre": "Plus de crédits, allez commenter !"
    - Disabled "Lire le prochain chapitre": "Histoire terminée ! Explorez la liste en-dessous pour trouver une nouvelle pépite !"

## Authorization & Visibility
- Respect visibility rules from Story domain (guest vs `user` vs `user-confirmed`).
- Dashboard requires authentication; Bienvenue panel and counts are for the logged user only.
- Reading progress and next chapter resolution must verify access to the next chapter.

## Routing & Views
- Route: `/dashboard` (authenticated middleware). Name `dashboard.show`.
- Domain: `app/Domains/Dashboard/` already exists.
- Controller: `DashboardController` under `App\Domains\Dashboard\...` delegating to services.
- Blade view host: `app/Domains/Dashboard/Private/Resources/views/index.blade.php`.
- Reuse `story::components.card` via Blade include/component. Action buttons live outside cards.

## i18n Strategy
- French-first copy per provided strings; keep strings translatable.
- Use PHP namespaced translations in each domain where strings belong. Shared dashboard strings can live under a `dashboard` namespace.

## Performance Considerations
- No caching initially. Later: cache small aggregates (counts; last-edited story id; latest reading-progress pointers).
- Use eager loading for cards (cover, author, counts displayed by card).
- Avoid N+1 by resolving all required relations in service layer.

## Component Contracts (Draft)

- **DashboardController** (`App\\Domains\\Dashboard\\...`)
  - Orchestrates data fetching via services/APIs:
    - Bienvenue: `ProfilePublicApi.getDisplayName`, `getJoinDate`, `getRoleLabel`; `StoryPublicApi.countAuthoredStories`; `CommentPublicApi.countCommentsByUser`.
    - Keep writing: `StoryPublicApi.getLastEditedStoryForUser`, `StoryPublicApi.getAvailableChapterCount`.
    - Keep reading: `StoryPublicApi.getNextChapterForReadingProgress` (or `getNextChapterAfter`).
    - Discover: `StoryPublicApi.getRandomStories(limit=8, userContext)`.
  - Passes hydrated DTOs to `index.blade.php` with eager-loaded relations for cards.

## Error Handling
- Each widget fetches data independently. On failures, render a compact error state within the widget (translated message, retry link if applicable). Do not fail the whole page.

## Open Questions
1. None — ready to split into user stories.

## Implementation Notes
- Follow Domain Oriented Architecture: no controllers calling DB/models directly; use services, and place migrations within proper domain folders.
- Use Blade includes/components to reuse `story::components.card` and News carousel.
- Enrich public APIs (Profile, Story, Comment) to expose the endpoints needed by the dashboard.

