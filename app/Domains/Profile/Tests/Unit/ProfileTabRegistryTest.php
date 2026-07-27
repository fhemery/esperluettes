<?php

use App\Domains\Profile\Public\Api\ProfileTabRegistry;
use App\Domains\Profile\Public\Contracts\ProfileTabDefinition;
use App\Domains\Profile\Public\Contracts\ProfileTabVisibility;
use App\Domains\Profile\Public\Visibility\AlwaysVisible;
use App\Domains\Profile\Public\Visibility\AuthenticatedOnly;
use Tests\TestCase;

uses(TestCase::class);

/** Visible only to the profile owner. */
class OwnerOnlyVisibilityStub implements ProfileTabVisibility
{
    public function isVisible(int $ownerUserId, ?int $viewerId): bool
    {
        return $ownerUserId === $viewerId;
    }
}

/** Visible to nobody, to exercise the "no visible tab" edge. */
class NeverVisibleStub implements ProfileTabVisibility
{
    public function isVisible(int $ownerUserId, ?int $viewerId): bool
    {
        return false;
    }
}

/** Does not implement the contract — used to assert the registry rejects it. */
class NotAVisibilityStub
{
}

function tab(string $key, array $overrides = []): ProfileTabDefinition
{
    return new ProfileTabDefinition(
        key: $key,
        order: $overrides['order'] ?? 10,
        component: $overrides['component'] ?? "test::{$key}",
        labelKey: $overrides['labelKey'] ?? "test::{$key}.label",
        ownLabelKey: $overrides['ownLabelKey'] ?? null,
        icon: $overrides['icon'] ?? null,
        visibility: $overrides['visibility'] ?? AlwaysVisible::class,
        privacy: $overrides['privacy'] ?? null,
        isDefault: $overrides['isDefault'] ?? false,
    );
}

beforeEach(function () {
    $this->registry = new ProfileTabRegistry();
});

describe('registration', function () {
    it('rejects a duplicate key', function () {
        $this->registry->register(tab('stories'));

        expect(fn () => $this->registry->register(tab('stories')))
            ->toThrow(InvalidArgumentException::class, "Profile tab 'stories' is already registered.");
    });

    it('rejects a second default tab', function () {
        $this->registry->register(tab('stories', ['isDefault' => true]));

        expect(fn () => $this->registry->register(tab('quotes', ['isDefault' => true])))
            ->toThrow(InvalidArgumentException::class);
    });

    it('exposes registered tabs by key', function () {
        $this->registry->register(tab('stories'));

        expect($this->registry->has('stories'))->toBeTrue()
            ->and($this->registry->get('stories')?->key)->toBe('stories')
            ->and($this->registry->has('banana'))->toBeFalse()
            ->and($this->registry->get('banana'))->toBeNull();
    });
});

describe('ordering', function () {
    it('sorts by order', function () {
        $this->registry->register(tab('quotes', ['order' => 50]));
        $this->registry->register(tab('about', ['order' => 10]));
        $this->registry->register(tab('stories', ['order' => 20]));

        expect(array_column($this->registry->all(), 'key'))->toBe(['about', 'stories', 'quotes']);
    });

    it('breaks ties on key so the render stays deterministic', function () {
        $this->registry->register(tab('zulu', ['order' => 10]));
        $this->registry->register(tab('alpha', ['order' => 10]));

        expect(array_column($this->registry->all(), 'key'))->toBe(['alpha', 'zulu']);
    });
});

describe('visibility', function () {
    it('filters tabs the viewer may not see', function () {
        $this->registry->register(tab('stories', ['order' => 10]));
        $this->registry->register(tab('about', ['order' => 20, 'visibility' => AuthenticatedOnly::class]));

        expect(array_column($this->registry->visibleFor(1, null), 'key'))->toBe(['stories'])
            ->and(array_column($this->registry->visibleFor(1, 2), 'key'))->toBe(['stories', 'about']);
    });

    it('passes owner and viewer through to the visibility rule', function () {
        $this->registry->register(tab('private', ['visibility' => OwnerOnlyVisibilityStub::class]));

        expect($this->registry->isVisible('private', 7, 7))->toBeTrue()
            ->and($this->registry->isVisible('private', 7, 8))->toBeFalse()
            ->and($this->registry->isVisible('private', 7, null))->toBeFalse();
    });

    it('reports an unregistered key as not visible', function () {
        expect($this->registry->isVisible('banana', 1, 1))->toBeFalse();
    });

    it('rejects a visibility class that does not implement the contract', function () {
        $this->registry->register(tab('broken', ['visibility' => NotAVisibilityStub::class]));

        expect(fn () => $this->registry->isVisible('broken', 1, 1))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('default tab', function () {
    it('returns the flagged tab when the viewer can see it', function () {
        $this->registry->register(tab('about', ['order' => 10, 'visibility' => AuthenticatedOnly::class]));
        $this->registry->register(tab('stories', ['order' => 20, 'isDefault' => true]));

        expect($this->registry->defaultFor(1, 2)?->key)->toBe('stories');
    });

    it('falls back to the first visible tab when the default is hidden', function () {
        $this->registry->register(tab('stories', ['order' => 10, 'visibility' => NeverVisibleStub::class, 'isDefault' => true]));
        $this->registry->register(tab('about', ['order' => 20]));

        expect($this->registry->defaultFor(1, 2)?->key)->toBe('about');
    });

    it('falls back to the first visible tab when no tab is flagged', function () {
        $this->registry->register(tab('about', ['order' => 20]));
        $this->registry->register(tab('stories', ['order' => 10]));

        expect($this->registry->defaultFor(1, 2)?->key)->toBe('stories');
    });

    it('returns null when nothing is visible', function () {
        $this->registry->register(tab('stories', ['visibility' => NeverVisibleStub::class]));

        expect($this->registry->defaultFor(1, 2))->toBeNull();
    });
});

describe('labels', function () {
    it('uses the own-profile label when there is one', function () {
        $definition = tab('stories', ['labelKey' => 'a.stories', 'ownLabelKey' => 'a.my-stories']);

        expect($definition->labelKeyFor(true))->toBe('a.my-stories')
            ->and($definition->labelKeyFor(false))->toBe('a.stories');
    });

    it('falls back to the shared label when there is no own-profile variant', function () {
        $definition = tab('following', ['labelKey' => 'a.following']);

        expect($definition->labelKeyFor(true))->toBe('a.following')
            ->and($definition->labelKeyFor(false))->toBe('a.following');
    });
});
