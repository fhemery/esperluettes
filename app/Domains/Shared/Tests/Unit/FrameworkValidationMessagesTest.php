<?php

use Illuminate\Support\Arr;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Guards the French defaults for Laravel's built-in validation rules.
 *
 * The English file shipped with the framework is the reference: a framework
 * upgrade that adds a rule fails here instead of printing a raw
 * `validation.<rule>` key to users.
 */
function frameworkValidationMessages(string $path): array
{
    $messages = require $path;
    unset($messages['custom'], $messages['attributes']);

    return Arr::dot($messages);
}

function englishValidationMessages(): array
{
    return frameworkValidationMessages(base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php'));
}

function frenchValidationMessages(): array
{
    return frameworkValidationMessages(app_path('Domains/Shared/Resources/lang-framework/fr/validation.php'));
}

it('covers every key of the framework\'s English validation file', function () {
    $missing = array_diff(array_keys(englishValidationMessages()), array_keys(frenchValidationMessages()));

    expect($missing)->toBe([]);
});

it('declares the custom and attributes arrays', function () {
    $messages = require app_path('Domains/Shared/Resources/lang-framework/fr/validation.php');

    expect($messages)->toHaveKey('custom')->toHaveKey('attributes');
});

it('never uses :attribute in a French message', function () {
    $withAttribute = array_filter(frenchValidationMessages(), fn (string $m) => str_contains($m, ':attribute'));

    expect($withAttribute)->toBe([]);
});

it('states file sizes in Ko', function () {
    $messages = frenchValidationMessages();

    foreach (['max.file', 'min.file', 'size.file', 'between.file'] as $key) {
        expect($messages[$key])->toContain('Ko')->not->toContain('Mo');
    }
});
