# Testing

**Warning**: The test strategy is currently in progress. We are currently not familiar enough with the Laravel capabilities to determine the best strategy. This will therefore probably evolve with time.

## Feature VS Unit

Because our Architecture is evolving, we don't want a huge unit test base that would prevent us from doing fast changed. Instead, we want a, maybe shallow, but high level coverage of our features.

This is why **we will mostly be using Feature testing**.

## Good practices

1. When you can check the source of truth without checking the HTML, do it.

Example with Filament:
```php
$homeUrl = Filament::getCurrentPanel()?->getHomeUrl();
expect($homeUrl)->toBe('/');
```

2. When you cannot, use simple assertions to check presence of certain strings :

```php
$response = $this->get('/profile');

$response->assertOk();
$response->assertSee('John Doe');
```

3. If you need precision, there is a certain number of HTML helpers defined and located in [../tests/ide/TestResponseMacros.php](../tests/ide/TestResponseMacros.php).

Example usage :
```php
$response = $this->get('/admin');
$response->assertHasAttribute('header a', 'href', '/');
```