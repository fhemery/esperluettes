# Testing

## Strategy

We prioritize **feature tests** over unit tests. Feature tests give us high-level coverage of user-facing flows without coupling too tightly to internal implementation details, keeping the test suite maintainable as the architecture evolves.

Unit tests are reserved for isolated logic (utilities, formatters, validators) that benefits from fine-grained assertions.

## Framework

We use [Pest](https://pestphp.com/) with `it()` / `describe()` syntax. All feature tests automatically use `RefreshDatabase` (configured in `tests/Pest.php`).

## Test Location

Tests live inside each domain:

```
app/Domains/{Domain}/Tests/
├── Feature/
│   └── *Test.php
├── Unit/
│   └── *Test.php
└── helpers.php        # Domain-scoped test helpers (loaded globally)
```

PHPUnit discovers them via `phpunit.xml`:
```xml
<testsuite name="Feature">
    <directory>app/Domains/*/Tests/Feature</directory>
</testsuite>
<testsuite name="Unit">
    <directory>app/Domains/*/Tests/Unit</directory>
</testsuite>
```

## Helpers

Each domain can define a `helpers.php` file with factory-like functions for creating test data. These are loaded globally in `tests/Pest.php` so any test can use them.

Common helpers include:
- `alice()`, `bob()`, `carol()` — create users with different roles
- `admin()`, `moderator()`, `techAdmin()` — create privileged users
- `publicStory()`, `communityStory()`, `privateStory()` — create stories with specific visibility
- `createPublishedChapter()`, `createComment()` — create related entities

## Good Practices

1. When you can check the source of truth without checking the HTML, do it.

Example with Filament:
```php
$homeUrl = Filament::getCurrentPanel()?->getHomeUrl();
expect($homeUrl)->toBe('/');
```

2. When you cannot, use simple assertions to check presence of certain strings:

```php
$response = $this->get('/profile');

$response->assertOk();
$response->assertSee('John Doe');
```

3. If you need precision, there is a certain number of HTML helpers defined and located in [../tests/ide/TestResponseMacros.php](../tests/ide/TestResponseMacros.php).

Example usage:
```php
$response = $this->get('/admin');
$response->assertHasAttribute('header a', 'href', '/');
```

Available macros: `getAttribute()`, `assertElementExists()`, `assertHasAttribute()`, `assertAttributeContains()`, `assertTextContains()`, `getElements()`.

## Running Tests

```bash
# Run all tests
./vendor/bin/sail artisan test

# Run a specific test file
./vendor/bin/sail artisan test --filter=StoryShowTest

# Run a specific domain's tests
./vendor/bin/sail artisan test app/Domains/Story/Tests/
```
