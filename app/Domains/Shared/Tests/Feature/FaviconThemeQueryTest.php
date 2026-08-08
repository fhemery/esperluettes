<?php

use App\Domains\Shared\Providers\SharedServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Favicon theme query cache-bust', function () {
    it('includes ?theme=spring on favicon links when user prefers spring', function () {
        $user = alice($this);

        setSettingsValue(
            $user->id,
            SharedServiceProvider::TAB_GENERAL,
            SharedServiceProvider::KEY_THEME,
            'spring'
        );

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $html = $response->getContent();

        expect($html)->not->toContain('?v=20260425');

        foreach ([
            'favicons/favicon.ico',
            'favicons/favicon-48x48.png',
            'favicons/favicon-32x32.png',
            'favicons/favicon-16x16.png',
            'favicons/favicon-180x180.png',
        ] as $file) {
            expect($html)->toContain('/images/themes/spring/'.$file.'?theme=spring');
        }
    });

    it('includes ?theme=winter on favicon links when user prefers winter', function () {
        $user = alice($this);

        setSettingsValue(
            $user->id,
            SharedServiceProvider::TAB_GENERAL,
            SharedServiceProvider::KEY_THEME,
            'winter'
        );

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $html = $response->getContent();

        expect($html)->not->toContain('?v=20260425');

        foreach ([
            'favicons/favicon.ico',
            'favicons/favicon-48x48.png',
            'favicons/favicon-32x32.png',
            'favicons/favicon-16x16.png',
            'favicons/favicon-180x180.png',
        ] as $file) {
            expect($html)->toContain('/images/themes/winter/'.$file.'?theme=winter');
        }
    });
});
