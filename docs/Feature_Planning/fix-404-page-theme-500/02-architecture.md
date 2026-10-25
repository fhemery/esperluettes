# Fix 500 on 404 page when a model-bound route misses — architecture

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Shared domain (theme resolution, layouts, error views).

## 2. Data model

None.

## 3. PHP architecture

Root cause: `ResolveThemeMiddleware` is appended at the end of the `web` group, so it runs after `SubstituteBindings`. A model-binding miss (or a CSRF failure) throws before the middleware, `View::share('theme', …)` never happens, and the error view's layout reads an undefined `$theme`.

Fix: a view composer registered by `SharedServiceProvider` on the shared layouts and error views that supplies `theme` from `ThemeService::current()` **only when the view has no `theme` yet**. The middleware stays as is (it also sets the request attribute and the user preference path). The composer covers every error path including 419, 500 and requests that never reach the web group.

## 4. Frontend architecture

No Blade change expected; the layouts already read `$theme`.

## 5. Deptrac

None (inside Shared).

## 6. Testing strategy

Pest feature tests in `Error404Test`: a model-bound miss (`/fanarts/9999`, guest and signed in) answers 404 with the page content; first reproduce the 500 to see the test fail. A test for the 419 page render if it can be triggered cheaply. Check the signed-in user's theme preference is used.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where to fix | reorder the middleware before `SubstituteBindings`; `$theme ?? default` in six Blade files; view composer fallback | composer fallback | covers all error paths; one place; reordering still misses requests that skip the web group and risks session ordering |
