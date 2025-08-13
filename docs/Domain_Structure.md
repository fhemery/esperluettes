# Domain Structure

Each domain in the application follows this structure:

```
app/
  Domains/
    {DomainName}/           # e.g., Auth, Admin, Shared
      Controllers/          # HTTP controllers
      Requests/             # Form requests and validation
      Models/               # Eloquent models
      Services/             # Business logic services
      Repositories/         # Data access layer
      Events/               # Domain events
      Listeners/            # Event listeners
      Notifications/        # Email/notification classes
      Policies/             # Authorization policies
      Resources/            # API resources
      View/                 # Blade views and components
        Components/         # Blade components
        Layouts/            # Layout files
      Providers/            # Service providers
      Tests/                # Domain-specific tests
        Unit/
        Feature/
      Support/              # Helper classes and utilities
      routes.php            # use web.routes.php and api.routes.php if there are both
```

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
5. Place domain-specific views in their respective domain directory
6. Use the `Shared` domain for cross-cutting concerns only

## Adding a New Domain

1. Create a new directory under `app/Domains/`
2. Follow the directory structure outlined above
3. Create a service provider in the domain's `Providers/` directory
4. Register the service provider in `config/app.php`
5. Update `composer.json` to autoload the new domain
6. Run `composer dump-autoload`
