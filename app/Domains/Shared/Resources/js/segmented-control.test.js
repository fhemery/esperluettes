import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import registerSegmentedControl from './segmented-control.js';

/** Builds the root element the Blade component renders: one radio button per option. */
function makeRoot(options) {
    const root = document.createElement('div');
    options.forEach((value) => {
        const button = document.createElement('button');
        button.setAttribute('role', 'radio');
        button.dataset.value = value;
        root.appendChild(button);
    });
    document.body.appendChild(root);
    return root;
}

/** Builds the raw Alpine data object, `this.$el` stubbed with a real element, then runs init(). */
function makeControl({ name = 'demo', options = ['a', 'b', 'c'], selected = 'a', storageKey = null } = {}) {
    let factory = null;
    registerSegmentedControl({ data: (id, f) => { if (id === 'segmentedControl') factory = f; } });

    const control = factory({ name, options, selected, storageKey });
    control.$el = makeRoot(options);
    control.init();
    return control;
}

function keydown(key) {
    return { key, preventDefault: vi.fn() };
}

describe('segmentedControl', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    it('starts on the selected option without a storage key', () => {
        localStorage.setItem('demo.key', 'c');

        const control = makeControl({ selected: 'b' });

        expect(control.value).toBe('b');
    });

    it('restores a stored value', () => {
        localStorage.setItem('demo.key', 'c');

        const control = makeControl({ selected: 'a', storageKey: 'demo.key' });

        expect(control.value).toBe('c');
    });

    it('falls back to the default when the stored value is unknown', () => {
        localStorage.setItem('demo.key', 'garbage');

        const control = makeControl({ selected: 'b', storageKey: 'demo.key' });

        expect(control.value).toBe('b');
    });

    it('falls back to the default when localStorage throws', () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('denied');
        });

        const control = makeControl({ selected: 'b', storageKey: 'demo.key' });

        expect(control.value).toBe('b');
    });

    it('writes the new value on select', () => {
        const control = makeControl({ selected: 'a', storageKey: 'demo.key' });

        control.select('c');

        expect(control.value).toBe('c');
        expect(localStorage.getItem('demo.key')).toBe('c');
    });

    it('does not throw when localStorage.setItem throws', () => {
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('quota');
        });
        const control = makeControl({ selected: 'a', storageKey: 'demo.key' });

        expect(() => control.select('b')).not.toThrow();
        expect(control.value).toBe('b');
    });

    it('dispatches segmented-control-change with name and value on init and on select', () => {
        const events = [];
        const listener = (e) => events.push(e.detail);
        window.addEventListener('segmented-control-change', listener);
        try {
            const control = makeControl({ name: 'mode', selected: 'a' });
            control.select('b');
            control.select('b');
        } finally {
            window.removeEventListener('segmented-control-change', listener);
        }

        expect(events).toEqual([
            { name: 'mode', value: 'a' },
            { name: 'mode', value: 'b' },
        ]);
    });

    it('reflects the current value in data-value on its root', () => {
        const control = makeControl({ selected: 'b' });
        expect(control.$el.dataset.value).toBe('b');

        control.select('c');

        expect(control.$el.dataset.value).toBe('c');
    });

    it('moves the selection with arrow keys, wrapping around', () => {
        const control = makeControl({ selected: 'c' });
        const buttons = control.$el.querySelectorAll('[role="radio"]');

        const right = keydown('ArrowRight');
        control.onKeydown(right);
        expect(control.value).toBe('a');
        expect(document.activeElement).toBe(buttons[0]);
        expect(right.preventDefault).toHaveBeenCalled();

        control.onKeydown(keydown('ArrowLeft'));
        expect(control.value).toBe('c');
        expect(document.activeElement).toBe(buttons[2]);

        control.onKeydown(keydown('ArrowUp'));
        expect(control.value).toBe('b');

        control.onKeydown(keydown('ArrowDown'));
        expect(control.value).toBe('c');

        control.onKeydown(keydown('Home'));
        expect(control.value).toBe('a');

        control.onKeydown(keydown('End'));
        expect(control.value).toBe('c');

        const other = keydown('Tab');
        control.onKeydown(other);
        expect(control.value).toBe('c');
        expect(other.preventDefault).not.toHaveBeenCalled();
    });

    it('ignores unknown values passed to select', () => {
        const control = makeControl({ selected: 'a', storageKey: 'demo.key' });

        control.select('nope');

        expect(control.value).toBe('a');
        expect(localStorage.getItem('demo.key')).toBeNull();
    });
});
