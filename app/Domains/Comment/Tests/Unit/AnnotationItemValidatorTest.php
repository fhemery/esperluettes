<?php

use App\Domains\Comment\Private\Support\AnnotationItemValidator;
use Tests\TestCase;

uses(TestCase::class);

describe('AnnotationItemValidator', function () {
    beforeEach(function () {
        // 'chapter' resolves to the real ChapterCommentPolicy: body max 1000, highlight max 500
        $this->validator = app(AnnotationItemValidator::class);
        $this->invalid = 'comment::annotations.errors.invalid';
    });

    it('accepts a valid item', function () {
        expect($this->validator->firstError('chapter', '<p>Nice</p>', 'passage', 'before', 'after'))->toBeNull();
    });

    it('refuses a blank body', function () {
        expect($this->validator->firstError('chapter', '<p>   </p>', 'passage', null, null))->toBe($this->invalid);
    });

    it('accepts a body at max length and refuses one over it', function () {
        expect($this->validator->firstError('chapter', str_repeat('a', 1000), 'passage', null, null))->toBeNull()
            ->and($this->validator->firstError('chapter', str_repeat('a', 1001), 'passage', null, null))->toBe($this->invalid);
    });

    it('accepts a highlight at max length and refuses one over it', function () {
        expect($this->validator->firstError('chapter', 'body', str_repeat('é', 500), null, null))->toBeNull()
            ->and($this->validator->firstError('chapter', 'body', str_repeat('é', 501), null, null))->toBe($this->invalid);
    });

    it('refuses an empty highlight', function () {
        expect($this->validator->firstError('chapter', 'body', '', null, null))->toBe($this->invalid);
    });

    it('skips the highlight rule when the highlight is null', function () {
        expect($this->validator->firstError('chapter', 'body', null, null, null))->toBeNull();
    });

    it('refuses a prefix or suffix over 255 characters', function () {
        expect($this->validator->firstError('chapter', 'body', 'passage', str_repeat('a', 255), str_repeat('a', 255)))->toBeNull()
            ->and($this->validator->firstError('chapter', 'body', 'passage', str_repeat('a', 256), null))->toBe($this->invalid)
            ->and($this->validator->firstError('chapter', 'body', 'passage', null, str_repeat('a', 256)))->toBe($this->invalid);
    });
});
