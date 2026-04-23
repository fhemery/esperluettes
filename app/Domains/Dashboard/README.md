# Dashboard Domain

## Purpose and scope

The Dashboard domain owns the single authenticated user dashboard page (`/dashboard`). It aggregates data from multiple other domains and composes them into a personalised landing page shown immediately after login. It owns no database tables of its own and has no Public API — no other domain calls into it.

## Key concepts

### Widget composition

The dashboard view is assembled from Blade components contributed by other domains. The Dashboard domain owns only the page frame and two of its own components (`WelcomeComponent` and `PromotionStatusComponent`). The remaining widgets — news carousel, keep-writing, keep-reading, calendar activity list, and random stories — are rendered by their respective domains' components and simply included in the dashboard layout.

### Promotion request flow

Unconfirmed users (role `user`) see a `PromotionStatusComponent` that shows their eligibility to request confirmed-user status. Eligibility is computed from the number of root comments they have posted on chapters, fetched via `CommentPublicApi`. The promotion request itself is handled by `AuthPublicApi::requestPromotion`. Confirmed users (`user-confirmed`) see a keep-writing widget instead and cannot reach the promotion request route at all — role middleware blocks it.

### Self-contained Blade components

Both `WelcomeComponent` and `PromotionStatusComponent` load their own data in their constructors via a `loadData()` method. This means neither requires the controller to pass data into them; they call the relevant Public APIs directly and surface any failure through an `$error` property. The blade templates must always handle the case where `$error` is non-null and other properties are null.

### Calendar feature toggle

The calendar widget slot on the dashboard is conditional. The controller reads `ConfigPublicApi::isToggleEnabled('enabled', 'calendar')` and passes a `$calendarEnabled` boolean to the view. When the toggle is off, a placeholder image is shown instead.

## Architecture decisions

**No direct database access.** The domain owns no tables and makes no Eloquent calls. All data is fetched through Public API calls (`AuthPublicApi`, `ProfilePublicApi`, `StoryPublicApi`, `CommentPublicApi`, `ConfigPublicApi`).

**Controller stays thin.** The controller resolves only two values that must be known before the view is assembled: the calendar toggle and whether the current user is confirmed. All per-widget data loading is delegated to the widget components themselves.

**Components self-resolve their dependencies.** Injecting dependencies directly into Blade components (via the constructor) keeps the controller ignorant of widget internals. Adding a new widget does not require touching the controller.

## Cross-domain delegation map

| Concern | Delegated to | Why |
|---|---|---|
| User display name, join date, role | `ProfilePublicApi::getFullProfile` | Profile domain owns the canonical user profile |
| Story and chapter counts | `StoryPublicApi::countAuthoredStories` | Story domain owns story data |
| Comment counts | `CommentPublicApi::countRootCommentsByUser` | Comment domain owns comment data |
| Promotion eligibility and status | `AuthPublicApi::canRequestPromotion`, `AuthPublicApi::getPromotionStatus` | Auth domain owns user roles and promotion state |
| Submitting a promotion request | `AuthPublicApi::requestPromotion` | Auth domain owns the promotion workflow |
| Calendar feature toggle | `ConfigPublicApi::isToggleEnabled` | Config domain owns feature toggles |
| News carousel widget | `News` domain Blade component | News domain owns article rendering |
| Keep-writing widget | `Story` domain Blade component | Story domain owns story/chapter context |
| Keep-reading widget | `Story` domain Blade component | Story domain owns reading progress |
| Calendar activity list | `Calendar` domain Blade component | Calendar domain owns activity data |
| Random stories widget | `Story` domain Blade component | Story domain owns story discovery |
