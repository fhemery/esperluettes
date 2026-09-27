<?php

use App\Domains\Editor\Public\Blocks\EditorBlockRegistry;
use App\Domains\Editor\Public\Blocks\EditorBlockType;
use Tests\TestCase;

uses(TestCase::class);

function registryFakeType(string $key): EditorBlockType
{
    return new class ($key) implements EditorBlockType {
        public function __construct(private readonly string $key) {}

        public function key(): string
        {
            return $this->key;
        }

        public function labelKey(): string
        {
            return 'fake.label';
        }

        public function icon(): string
        {
            return 'star';
        }

        public function editorView(): string
        {
            return 'fake::view';
        }

        public function render(array $block, array $context): string
        {
            return '';
        }
    };
}

describe('EditorBlockRegistry', function () {
    it('lists text then image as built-in types', function () {
        $types = app(EditorBlockRegistry::class)->all();

        expect(array_map(fn (EditorBlockType $t) => $t->key(), $types))->toBe(['text', 'image']);
        expect($types[0]->icon())->toBe('notes');
        expect($types[1]->icon())->toBe('image');
    });

    it('is a singleton, so plugin registrations are visible to every consumer', function () {
        app(EditorBlockRegistry::class)->register(registryFakeType('fake'));

        expect(app(EditorBlockRegistry::class)->get('fake'))->not->toBeNull();
    });

    it('rejects a second registration of the same key', function () {
        app(EditorBlockRegistry::class)->register(registryFakeType('text'));
    })->throws(InvalidArgumentException::class);

    it('returns null for an unknown key', function () {
        expect(app(EditorBlockRegistry::class)->get('nope'))->toBeNull();
    });
});
