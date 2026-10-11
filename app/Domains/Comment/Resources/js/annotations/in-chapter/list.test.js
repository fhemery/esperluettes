import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { annotationList, FOCUS_EVENT } from './list.js';

let component;

function make(rows) {
    document.body.innerHTML = `<div data-annotation-overlay data-unknown-name="Esperluette disparue"
        data-default-avatar="/default.svg" data-user-name="Moi" data-user-avatar="/me.png"></div>`;
    component = annotationList();
    component.$store = { annotationOverlay: { rows } };
    component.init();
}

const server = (id, over = {}) => ({
    id, source: 'server', highlighted_text: `passage ${id}`, is_processed: false,
    author_profile: { display_name: 'Alice', avatar_url: '/alice.png' }, ...over,
});

afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
});

describe('annotation list', () => {
    beforeEach(() => make([
        server(1),
        server(2, { is_processed: true, author_profile: null }),
        { source: 'draft', tempId: 't1', highlighted_text: 'x'.repeat(200), author_profile: undefined },
    ]));

    it('counts every row', () => {
        expect(component.count).toBe(3);
    });

    it('describes each row with its commenter, passage and key', () => {
        const [saved, anonymised, draft] = component.items;
        expect(saved).toMatchObject({ key: 'id:1', name: 'Alice', avatar: '/alice.png', text: 'passage 1', processed: false });
        expect(anonymised).toMatchObject({ key: 'id:2', avatar: '/default.svg', processed: true });
        expect(draft).toMatchObject({ key: 'temp:t1', name: 'Moi', avatar: '/me.png' });
        expect(draft.text.length).toBeLessThan(200);
    });

    it('asks the overlay to focus the chosen row', () => {
        const seen = vi.fn();
        window.addEventListener(FOCUS_EVENT, (event) => seen(event.detail));
        component.select(component.items[0]);
        expect(seen).toHaveBeenCalledWith({ key: 'id:1' });
    });
});
