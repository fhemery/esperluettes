<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

/**
 * The upload widgets belong to SecretGift, their sole consumer. Shared must not
 * expose them again: a copy under Shared's anonymous component path silently
 * revives `<x-shared::image-upload>` for every domain.
 */

const SHARED_COMPONENTS = 'Domains/Shared/Resources/views/components';
const SECRET_GIFT_COMPONENTS = 'Domains/Calendar/Private/Activities/SecretGift/Resources/views/components';

it('no longer ships the upload widgets in Shared', function (string $component) {
    expect(app_path(SHARED_COMPONENTS . '/' . $component . '.blade.php'))->not->toBeFile();
})->with(['image-upload', 'sound-upload']);

it('ships the upload widgets under SecretGift', function (string $component) {
    expect(app_path(SECRET_GIFT_COMPONENTS . '/' . $component . '.blade.php'))->toBeFile();
})->with(['image-upload', 'sound-upload']);
