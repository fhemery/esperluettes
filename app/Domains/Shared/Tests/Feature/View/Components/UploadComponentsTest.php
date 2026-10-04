<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

uses(TestCase::class);

describe('Shared upload components', function () {
    it('no longer ships the image upload component', function () {
        expect(file_exists(base_path('app/Domains/Shared/Resources/views/components/image-upload.blade.php')))
            ->toBeFalse();

        expect(fn () => $this->withViewErrors([])->blade('<x-shared::image-upload name="cover" />'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('no longer ships the sound upload component', function () {
        expect(file_exists(base_path('app/Domains/Shared/Resources/views/components/sound-upload.blade.php')))
            ->toBeFalse();

        expect(fn () => $this->withViewErrors([])->blade('<x-shared::sound-upload name="gift_sound" />'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('no longer ships the sound upload lang file', function () {
        expect(file_exists(base_path('app/Domains/Shared/Resources/lang/fr/sound-upload.php')))
            ->toBeFalse();

        expect(Lang::has('shared::sound-upload.drop_or_click', 'fr'))->toBeFalse();
    });

    it('no longer ships the image upload lang file', function () {
        expect(file_exists(base_path('app/Domains/Shared/Resources/lang/fr/image-upload.php')))
            ->toBeFalse();

        foreach (['drop_or_click', 'max_size', 'size_error'] as $key) {
            expect(Lang::has('shared::image-upload.' . $key, 'fr'))->toBeFalse();
        }
    });

    it('leaves the story cover tab owning its own dropzone copy', function () {
        foreach (['custom_drop_or_click', 'custom_max_size', 'custom_size_error'] as $key) {
            expect(Lang::has('story::shared.cover.' . $key, 'fr'))->toBeTrue();
        }
    });
});
