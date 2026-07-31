<?php

use App\Domains\Media\Public\Api\MediaPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('private');
});

describe('storePrivate', function () {
    it('stores a private image on the private disk and returns a scoped path', function () {
        $path = app(MediaPublicApi::class)->storePrivate(
            'secret-gift/7',
            UploadedFile::fake()->image('gift.jpg', 800, 600),
        );

        expect($path)->toStartWith('secret-gift/7/');
        Storage::disk('private')->assertExists($path);
        expect(Storage::disk('public')->allFiles())->toBe([]);
    });

    it('does not generate variants for a private image', function () {
        $path = app(MediaPublicApi::class)->storePrivate(
            'secret-gift/7',
            UploadedFile::fake()->image('gift.jpg', 1200, 800),
        );

        expect(Storage::disk('private')->files('secret-gift/7'))->toBe([$path]);
    });

    it('rejects a public scope passed to storePrivate', function () {
        expect(fn () => app(MediaPublicApi::class)->storePrivate('news', UploadedFile::fake()->image('a.jpg')))
            ->toThrow(InvalidArgumentException::class);
    });

    it('rejects a private scope passed to store', function () {
        expect(fn () => app(MediaPublicApi::class)->store('secret-gift/7', UploadedFile::fake()->image('a.jpg')))
            ->toThrow(InvalidArgumentException::class);
        expect(Storage::disk('public')->allFiles())->toBe([]);
    });
});

describe('stream', function () {
    it('streams a private image back with its mime type', function () {
        $api = app(MediaPublicApi::class);
        $path = $api->storePrivate('secret-gift/7', UploadedFile::fake()->image('gift.jpg', 40, 40));

        $response = $api->stream($path);

        expect($response)->toBeInstanceOf(StreamedResponse::class);
        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('Content-Type'))->toBe('image/jpeg');

        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        expect($body)->toBe(Storage::disk('private')->get($path));
    });

    it('merges caller supplied headers into the stream response', function () {
        $api = app(MediaPublicApi::class);
        $path = $api->storePrivate('secret-gift/7', UploadedFile::fake()->image('gift.jpg', 40, 40));

        $response = $api->stream($path, [
            'Cache-Control' => 'private, max-age=3600',
            'Content-Disposition' => 'attachment; filename="gift.jpg"',
        ]);

        expect($response->headers->get('Cache-Control'))->toContain('max-age=3600');
        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="gift.jpg"');
        expect($response->headers->get('Content-Type'))->toBe('image/jpeg');
    });
});

describe('exists', function () {
    it('reports existence on the right disk', function () {
        $api = app(MediaPublicApi::class);
        $path = $api->storePrivate('secret-gift/7', UploadedFile::fake()->image('gift.jpg'));

        expect($api->exists($path))->toBeTrue();

        // A private-shaped path that only exists on the public disk is not there.
        Storage::disk('public')->put('secret-gift/7/ghost.jpg', 'x');
        expect($api->exists('secret-gift/7/ghost.jpg'))->toBeFalse();
    });
});

describe('private paths have no public URL', function () {
    it('refuses to build a public URL for a private path', function () {
        $api = app(MediaPublicApi::class);

        expect(fn () => $api->originalUrl('secret-gift/7/abc.jpg'))
            ->toThrow(InvalidArgumentException::class);
        expect(fn () => $api->variantUrl('secret-gift/7/abc.jpg', 400))
            ->toThrow(InvalidArgumentException::class);
    });

    it('refuses to list a private scope in the reuse picker', function () {
        expect(fn () => app(MediaPublicApi::class)->listByScope('secret-gift/7'))
            ->toThrow(InvalidArgumentException::class);
    });
});
