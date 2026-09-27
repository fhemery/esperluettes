<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    app()->setLocale('fr');
});

it('translates image and max on a file into French', function () {
    $validator = Validator::make(
        ['file' => UploadedFile::fake()->create('x.pdf', 3000)],
        ['file' => ['nullable', 'image', 'max:2048']],
    );

    $messages = $validator->errors()->get('file');

    expect($messages)->toHaveCount(2);
    expect(implode(' ', $messages))->toContain('image')->toContain('2048 Ko');
    foreach ($messages as $message) {
        expect($message)->not->toStartWith('validation.');
    }
});

it('resolves validation.required from the PHP file', function () {
    expect(__('validation.required'))->toBe('Ce champ est obligatoire.');
});

it('lets a form request\'s own messages win', function () {
    $validator = Validator::make(
        ['field' => ''],
        ['field' => ['required']],
        ['field.required' => 'Custom'],
    );

    expect($validator->errors()->first('field'))->toBe('Custom');
});

it('gives maxstripped a French default with the limit substituted', function () {
    $message = Validator::make(['t' => '<p>abcdef</p>'], ['t' => ['maxstripped:3']])
        ->errors()->first('t');

    expect($message)->toBe('Ce champ ne doit pas dépasser 3 caractères.')
        ->not->toStartWith('validation.');
});

it('gives minstripped a French default with the limit substituted', function () {
    $message = Validator::make(['t' => '<p>abcdef</p>'], ['t' => ['minstripped:10']])
        ->errors()->first('t');

    expect($message)->toBe('Ce champ doit contenir au moins 10 caractères.')
        ->not->toStartWith('validation.');
});

it('gives required_trimmed a French default', function () {
    // Non-implicit rule: only null reaches it in a direct Validator::make.
    $message = Validator::make(['t' => null], ['t' => ['required_trimmed']])
        ->errors()->first('t');

    expect($message)->toBe('Ce champ est obligatoire.')
        ->not->toStartWith('validation.');
});

it('keeps a form request override over the custom-rule default', function () {
    $message = Validator::make(
        ['t' => '<p>abcdef</p>'],
        ['t' => ['maxstripped:3']],
        ['t.maxstripped' => 'Custom'],
    )->errors()->first('t');

    expect($message)->toBe('Custom');
});
