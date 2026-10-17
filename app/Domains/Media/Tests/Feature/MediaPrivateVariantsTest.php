<?php

use App\Domains\Media\Public\Api\MediaPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('private');
});

function storePrivateWithVariants(): string
{
    return app(MediaPublicApi::class)->storePrivate(
        'fanart/12',
        UploadedFile::fake()->image('art.jpg', 1200, 800),
        [400, 800],
    );
}

it('writes 400w and 800w jpg/webp variants on the private disk when widths are given', function () {
    $path = storePrivateWithVariants();
    $name = pathinfo($path, PATHINFO_FILENAME);

    expect($path)->toStartWith('fanart/12/');
    $files = Storage::disk('private')->files('fanart/12');
    sort($files);
    $expected = [
        $path,
        "fanart/12/{$name}-400w.jpg",
        "fanart/12/{$name}-400w.webp",
        "fanart/12/{$name}-800w.jpg",
        "fanart/12/{$name}-800w.webp",
    ];
    sort($expected);
    expect($files)->toBe($expected);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('reports hasVariants true for a private original stored with widths, false without', function () {
    $api = app(MediaPublicApi::class);
    $withVariants = storePrivateWithVariants();
    $without = $api->storePrivate('fanart/12', UploadedFile::fake()->image('plain.jpg', 1200, 800));

    expect($api->hasVariants($withVariants))->toBeTrue();
    expect($api->hasVariants($without))->toBeFalse();
});

it('streams a private variant with an inline image response', function () {
    $path = storePrivateWithVariants();
    $variant = 'fanart/12/' . pathinfo($path, PATHINFO_FILENAME) . '-400w.webp';

    $response = app(MediaPublicApi::class)->streamVariant($path, 400, 'webp', ['Cache-Control' => 'private, max-age=60']);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('image/webp');
    expect($response->headers->get('Content-Disposition'))->toBe('inline; filename=' . basename($variant));
    expect($response->headers->get('Cache-Control'))->toContain('max-age=60');

    ob_start();
    $response->sendContent();
    $body = ob_get_clean();

    expect($body)->toBe(Storage::disk('private')->get($variant));
});

it('refuses streamVariant on a public path', function () {
    $api = app(MediaPublicApi::class);
    $path = $api->store('news', UploadedFile::fake()->image('photo.jpg', 1200, 800));

    expect(fn () => $api->streamVariant($path, 400))->toThrow(InvalidArgumentException::class);
});

it('throws on streamVariant for a width that was not generated', function () {
    $path = storePrivateWithVariants();

    expect(fn () => app(MediaPublicApi::class)->streamVariant($path, 1200))
        ->toThrow(InvalidArgumentException::class);
});
