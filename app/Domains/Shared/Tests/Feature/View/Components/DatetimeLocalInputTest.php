<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

describe('Shared datetime-local-input', function () {
    it('renders a date input carrying the id and a time input, both unnamed', function () {
        $html = (string) $this->blade('<x-shared::datetime-local-input id="starts" name="starts" value="2026-09-20T10:00" />');

        expect($html)
            ->toMatch('/<input[^>]*type="date"[^>]*id="starts"|<input[^>]*id="starts"[^>]*type="date"/')
            ->toContain('type="time"');
        expect(preg_match_all('/<input[^>]*\bname="starts"/', $html))->toBe(1);
        expect($html)->not->toMatch('/<input[^>]*type="(date|time)"[^>]*\bname=/');
    });

    it('carries the name and the raw value on a hidden input', function () {
        $this->blade('<x-shared::datetime-local-input id="starts" name="starts" value="2026-09-20T10:00" />')
            ->assertSee('type="hidden"', false)
            ->assertSee('name="starts"', false)
            ->assertSee('value="2026-09-20T10:00"', false);
    });

    it('labels the time input', function () {
        $this->blade('<x-shared::datetime-local-input id="starts" name="starts" />')
            ->assertSee('aria-label="shared::components.datetime_input.time"', false);
    });

    it('names the group and the time input after the caller label', function () {
        $html = (string) $this->blade('<x-shared::input-label for="starts">Début</x-shared::input-label>'
            . '<x-shared::datetime-local-input id="starts" name="starts" />');

        expect($html)
            ->toContain('id="starts_label"')
            ->toContain('role="group"')
            ->toContain('aria-labelledby="starts_label"')
            ->toContain('id="starts_time"')
            ->toContain('aria-labelledby="starts_label starts_time"');
    });

    it('sets no step on the time input', function () {
        $html = (string) $this->blade('<x-shared::datetime-local-input id="starts" name="starts" />');

        expect($html)->not->toContain('step=');
    });

    it("applies the caller's classes to both visible inputs", function () {
        $html = (string) $this->blade('<x-shared::datetime-local-input id="starts" name="starts" class="mt-1 block w-full" />');

        expect(preg_match_all('/<input[^>]*type="(?:date|time)"[^>]*class="[^"]*mt-1 block w-full/', $html))->toBe(2);
        expect(preg_match_all('/<input[^>]*type="(?:date|time)"[^>]*class="[^"]*border-accent/', $html))->toBe(2);
    });

    it('disables every input when disabled', function () {
        $html = (string) $this->blade('<x-shared::datetime-local-input id="starts" name="starts" :disabled="true" />');

        expect(preg_match_all('/<input[^>]*\bdisabled\b/', $html))->toBe(3);
    });

    it('sets min on the date input only', function () {
        $html = (string) $this->blade('<x-shared::datetime-local-input id="starts" name="starts" min="2026-10-24" />');

        expect(preg_match_all('/<input[^>]*\bmin="2026-10-24"/', $html))->toBe(1);
        expect($html)->toMatch('/<input[^>]*type="date"[^>]*\bmin="2026-10-24"|<input[^>]*\bmin="2026-10-24"[^>]*type="date"/');
    });

    it('skips the UTC conversion at submit in local mode', function () {
        $local = (string) $this->blade('<x-shared::datetime-local-input id="starts" name="starts" submit="local" />');
        $utc = (string) $this->blade('<x-shared::datetime-local-input id="starts" name="starts" />');

        expect($local)->toContain("submit: 'local'");
        expect($utc)->toContain("submit: 'utc'");
    });

    it('renders no named input in mirror mode and disables both visible inputs', function () {
        $html = (string) $this->blade('<x-shared::datetime-local-input id="mirror" mirror-of="source" />');

        expect($html)
            ->not->toContain('type="hidden"')
            ->not->toMatch('/<input[^>]*\bname=/')
            ->toContain('id="mirror"');
        expect(preg_match_all('/<input[^>]*\bdisabled\b[^>]*aria-disabled="true"|<input[^>]*aria-disabled="true"[^>]*\bdisabled\b/', $html))->toBe(2);
    });
});
