# Domain Structure

Each domain in the application follows basically this structure:

```
app/
  Domains/
    {DomainName}/                 # e.g., Auth, Admin, Shared, Story
      Database/
        Migrations/               # Database migrations
        Seeders/                  # Database seeders

      Private/
        Api/                        # Implementations of Contracts
        Controllers/                # HTTP controllers
        Listeners/                  # Event listeners
        Models/                     # Eloquent models
        Notifications/              # Email/notification classes
        Policies/                   # Authorization policies
        Providers/                  # Service providers
        Repositories/               # Data access layer
        Requests/                   # Form requests and validation
        Resources/                  # Frontend assets and Blade templates
          css/                      # Domain CSS/SCSS entrypoints and partials
          js/                       # Domain JS/TS modules
          lang/                     # Domain translation files (JSON/PHP)
          views/                    # Blade templates (Windows-safe lowercase)
            components/             # Anonymous components (Blade files)
            layouts/                # Layouts (Blade files)
            pages/                  # Page templates (Blade files)
        Services/                   # Business logic services
        Support/                    # Helper classes and utilities
        Views/                      # PHP view layer classes (no Blade files here)
          Components/               # Class-based Blade components (PHP classes)
        routes.php                  # use web.routes.php and api.routes.php if there are both

      Public/
        Contracts/                  # Interfaces or classes exposed to other domains
          Dto/                      # Data Transfer Objects (if any related to contracts)
        Events/                     # Domain events.

      Tests/                      # Domain-specific tests
        Unit/
        Feature/
```

To sum up: 
- Public: exposed to other domains
- Private: internal to the domain

Note that the folders might vary based on the domain needs. For example, Shared domains will have mostly public
elements (for example a Public `Support` folder), and that's ok.

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
