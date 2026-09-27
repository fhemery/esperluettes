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
