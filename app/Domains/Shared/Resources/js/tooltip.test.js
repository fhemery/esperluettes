import { describe, it, expect, vi } from 'vitest';
import registerTooltip from './tooltip.js';

/** Builds the raw Alpine data object the `popover` component is made of. */
function makePopover() {
    let factory = null;
    registerTooltip({ data: (name, f) => { factory = f; } });

    const popover = factory();
    popover.$store = { popover: { openId: null } };
    popover.id = 'test-popover';
    return popover;
}

function keydown(key) {
    return { key, preventDefault: vi.fn(), stopPropagation: vi.fn() };
}

describe('popover keyboard activation', () => {
    it('opens on Enter and swallows the key', () => {
        const popover = makePopover();
        const event = keydown('Enter');

        popover.onTriggerKeydown(event);

        expect(popover.pinned).toBe(true);
        expect(popover.open).toBe(true);
        expect(event.preventDefault).toHaveBeenCalled();
        expect(event.stopPropagation).toHaveBeenCalled();
    });

    it('opens on Space', () => {
        const popover = makePopover();

        popover.onTriggerKeydown(keydown(' '));

        expect(popover.open).toBe(true);
    });

    it('closes again on a second Enter, after the same delay a mouse leave gets', () => {
        vi.useFakeTimers();
        try {
            const popover = makePopover();

            popover.onTriggerKeydown(keydown('Enter'));
            popover.onTriggerKeydown(keydown('Enter'));

            expect(popover.pinned).toBe(false);
            vi.advanceTimersByTime(300);
            expect(popover.open).toBe(false);
        } finally {
            vi.useRealTimers();
        }
    });

    it('claims the exclusive-open slot, so other popovers close', () => {
        const popover = makePopover();

        popover.onTriggerKeydown(keydown('Enter'));

        expect(popover.$store.popover.openId).toBe('test-popover');
    });

    it('ignores every other key', () => {
        const popover = makePopover();
        const event = keydown('a');

        popover.onTriggerKeydown(event);

        expect(popover.open).toBe(false);
        expect(event.preventDefault).not.toHaveBeenCalled();
    });
});
