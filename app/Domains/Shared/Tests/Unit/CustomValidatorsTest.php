<?php

use App\Domains\Shared\Validation\CustomValidators;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Ensure our custom rules are registered for these tests
    CustomValidators::register();
});

it('maxstripped passes when stripped length is within the limit', function () {
    $data = [
        'desc' => '<p>Hello <strong>world</strong></p>', // stripped => "Hello world"
    ];

    $rules = [
        'desc' => 'maxstripped:11,strict', // exactly 11 characters
    ];

    $validator = Validator::make($data, $rules);

    expect($validator->passes())->toBeTrue();
});

it('maxstripped fails when stripped length exceeds the limit', function () {
    $data = [
        'desc' => '<p>Hello <em>beautiful</em> world</p>', // stripped => "Hello beautiful world" (longer than 10)
    ];

    $rules = [
        'desc' => 'maxstripped:10,strict',
    ];

    $validator = Validator::make($data, $rules);

    expect($validator->fails())->toBeTrue();
});

it('maxstripped normalizes double newlines to a single newline before measuring', function () {
    // Two paragraphs commonly result in two line breaks when tags are stripped; our rule reduces "\n\n" to "\n"
    $data = [
        'desc' => "foo\n\nbar",
    ];

    // After normalization: "foo\nbar" which is length 7
    $rules = [
        'desc' => 'maxstripped:7,strict',
    ];

    $validator = Validator::make($data, $rules);

    expect($validator->passes())->toBeTrue();
});
