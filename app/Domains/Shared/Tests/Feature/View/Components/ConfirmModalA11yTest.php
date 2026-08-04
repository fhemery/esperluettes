<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

describe('Shared confirm-modal a11y', function () {
    it('forwards focusable so the modal focuses the first control on open', function () {
        // modal.blade.php consumes `focusable` via $attributes->has() and injects
        // firstFocusable().focus() into x-init — it does not re-emit the attribute.
        $this->blade(
            '<x-shared::confirm-modal name="delete-thing" title="Delete?" body="Sure?" />'
        )->assertSee('firstFocusable().focus()', false);
    });
});
