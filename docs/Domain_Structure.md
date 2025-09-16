# Domain Structure

Each domain in the application follows this structure:

```
app/
  Domains/
    {DomainName}/                 # e.g., Auth, Admin, Shared, Story
      Contracts/                  # [Public] Interfaces exposed to other domains
      Controllers/                # [Private] HTTP controllers
      Dto/                        # [Public] Data Transfer Objects 
      Events/                     # [Public] Domain events
      Listeners/                  # [Private] Event listeners
      Models/                     # [Private] Eloquent models
      Notifications/              # [Private] Email/notification classes
      Policies/                   # [Private] Authorization policies
      PublicApi/                  # [Private] Implementations of Contracts (facades)
      Providers/                  # [Private] Service providers
      Views/                      # [Private] PHP view layer classes (no Blade files here)
        Components/               # [Private] Class-based Blade components (PHP classes)
      Repositories/               # [Private] Data access layer
      Requests/                   # [Private] Form requests and validation
      Resources/                  # [Private] Frontend assets and Blade templates
        css/                      # [Private] Domain CSS/SCSS entrypoints and partials
        js/                       # [Private] Domain JS/TS modules
        lang/                     # [Private] Domain translation files (JSON/PHP)
        views/                    # [Private] Blade templates (Windows-safe lowercase)
          components/             # [Private] Anonymous components (Blade files)
          layouts/                # [Private] Layouts (Blade files)
          pages/                  # [Private] Page templates (Blade files)
      Services/                   # [Private] Business logic services
      Tests/                      # [Private] Domain-specific tests
        Unit/
        Feature/
      Support/                    # [Private] Helper classes and utilities
      routes.php                  # use web.routes.php and api.routes.php if there are both
```

## Public VS Private APIs

Public APIs are exposed to other domains via `Contracts` and `DTOs`.
`Events` are also considered public.
Private APIs are internal to the domain.

## Shared Domain

The Shared domain contains cross-cutting concerns used by other domains:

```
Shared/
  Controllers/     # Base controllers
  Traits/          # Reusable traits
  Interfaces/      # Common interfaces
  Helpers/         # Global helper functions/classes
  Exceptions/      # Custom exceptions
  Support/         # Other supporting classes
```

## Naming Conventions

- **Controllers**: Use singular form (e.g., `UserController`)
- **Models**: Use singular, PascalCase (e.g., `User`)
- **Services**: Suffix with "Service" (e.g., `UserService`)
- **Repositories**: Suffix with "Repository" (e.g., `UserRepository`)
- **Events**: Use past tense (e.g., `UserCreated`)
- **Listeners**: Describe the action (e.g., `SendWelcomeEmail`)

## Best Practices

1. Keep domain logic in the appropriate domain directory
2. Use dependency injection for services and repositories
3. Keep controllers thin, delegating business logic to services
4. Use form requests for validation
5. Place Blade templates under `Resources/views/` (lowercase) to avoid case collisions on Windows.
6. Keep PHP class-based components under `Views/Components/` (uppercase `Components`) with namespaces like `App\\Domains\\{Domain}\\Views\\Components`.
7. Use the `Shared` domain for cross-cutting components, layouts, and assets needed across multiple domains.

## Views, CSS, and JS: Where do they live?

- Views (Blade files): `app/Domains/{Domain}/Resources/views/`
  - Components (anonymous): `.../views/components/`
  - Layouts: `.../views/layouts/`
  - Pages: `.../views/pages/`

- Class-based Blade components (PHP): `app/Domains/{Domain}/Views/Components/`

- Stylesheets: `app/Domains/{Domain}/Resources/css/`
  - Example: `app.scss` and partials imported from domain scopes

- JavaScript/TypeScript: `app/Domains/{Domain}/Resources/js/`
  - Example: domain modules imported into a central entrypoint

This separation prevents `Components` (PHP) vs `components` (Blade) folder name collisions on case-insensitive filesystems (like Windows).

## Adding a New Domain

1. Create a new directory under `app/Domains/`
2. Follow the directory structure outlined above
3. Create a service provider in the domain's `Providers/` directory
4. Register the service provider in `config/app.php`
5. Update `composer.json` to autoload the new domain
6. Run `composer dump-autoload`
