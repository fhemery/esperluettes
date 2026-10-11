import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { annotationFilterMenu } from './filter-menu.js';
import { createOverlayStore } from './store.js';
import { renderHighlights, HIGHLIGHT_PROCESSED } from './renderer.js';

const UNKNOWN = 'Esperluette disparue';
const DEFAULT_AVATAR = '/default.svg';

class FakeHighlight {
    constructor(...ranges) {
        this.ranges = ranges;
    }
}

let overlay;
let article;

function item(id, authorId, overrides = {}) {
    return {
        id,
        author_id: authorId,
        author_profile: authorId === null
            ? { display_name: '', avatar_url: '' }
            : { display_name: `Lecteur ${authorId}`, avatar_url: `/a/${authorId}.png` },
        body: `<p>Avis ${id}</p>`,
        highlighted_text: `passage ${id}`,
        prefix: '',
        suffix: '',
        is_processed: false,
        ...overrides,
    };
}

async function loadOverlay(lists, role = 'author') {
    await overlay.load({ role, entityType: 'chapter', entityId: 42, userId: 1, url: '' });
    overlay.serverLists = lists;
    overlay.compose();
}

function mount() {
    const root = document.createElement('div');
    root.dataset.unknownName = UNKNOWN;
    root.dataset.defaultAvatar = DEFAULT_AVATAR;
    const component = annotationFilterMenu();
    component.$root = root;
    component.$store = { annotationOverlay: overlay };
    component.init();
    return component;
}

const visibleIds = () => overlay.visibleRows.map((row) => row.id);

beforeEach(() => {
    overlay = createOverlayStore();
    overlay.enabled = true;
});

afterEach(() => {
    overlay.destroy();
    article?.remove();
    article = null;
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
});

describe('annotationFilterMenu', () => {
    it('lists each commenter once, the anonymised ones under one entry with the default avatar', async () => {
        await loadOverlay([
            { comment_id: 10, items: [item(1, 3), item(2, 3)] },
            { comment_id: 11, items: [item(3, 4)] },
            { comment_id: 12, items: [item(4, null)] },
            { comment_id: 13, items: [item(5, null)] },
        ]);
        const menu = mount();

        expect(menu.commenters).toEqual([
            { id: 3, name: 'Lecteur 3', avatar: '/a/3.png' },
            { id: 4, name: 'Lecteur 4', avatar: '/a/4.png' },
            { id: null, name: UNKNOWN, avatar: DEFAULT_AVATAR },
        ]);
    });

    it('lists commenters whose annotations are all processed', async () => {
        await loadOverlay([{ comment_id: 10, items: [item(1, 3, { is_processed: true })] }]);

        expect(mount().commenters.map((c) => c.id)).toEqual([3]);
    });

    it('starts with every commenter ticked and processed annotations hidden', async () => {
        await loadOverlay([{ comment_id: 10, items: [item(1, 3), item(2, 4, { is_processed: true })] }]);
        const menu = mount();

        expect(menu.isTicked(3)).toBe(true);
        expect(menu.isTicked(4)).toBe(true);
        expect(menu.showProcessed).toBe(false);
        expect(visibleIds()).toEqual([1]);
    });

    it('unticking a commenter hides their rows; ticking them back restores them', async () => {
        await loadOverlay([
            { comment_id: 10, items: [item(1, 3), item(2, 3)] },
            { comment_id: 11, items: [item(3, 4)] },
            { comment_id: 12, items: [item(4, null)] },
        ]);
        const menu = mount();
        const before = overlay.filters.hiddenCommenterIds;

        menu.toggleCommenter(3);
        expect(menu.isTicked(3)).toBe(false);
        expect(visibleIds()).toEqual([3, 4]);
        // A new Set, so Alpine sees the change.
        expect(overlay.filters.hiddenCommenterIds).not.toBe(before);

        menu.toggleCommenter(null);
        expect(visibleIds()).toEqual([3]);

        menu.toggleCommenter(3);
        expect(visibleIds()).toEqual([1, 2, 3]);
    });

    it('showing processed annotations adds them, in the processed highlight', async () => {
        vi.stubGlobal('CSS', { highlights: new Map() });
        vi.stubGlobal('Highlight', FakeHighlight);
        article = document.createElement('article');
        article.innerHTML = '<div class="ce-block--text"><p>Le passage 1 et le passage 2.</p></div>';
        document.body.appendChild(article);
        await loadOverlay([{ comment_id: 10, items: [item(1, 3), item(2, 3, { is_processed: true })] }]);
        const menu = mount();

        menu.toggleProcessed();

        expect(menu.showProcessed).toBe(true);
        expect(visibleIds()).toEqual([1, 2]);
        renderHighlights(article, overlay.visibleRows);
        expect(CSS.highlights.get(HIGHLIGHT_PROCESSED).ranges.map(String)).toEqual(['passage 2']);

        menu.toggleProcessed();
        expect(visibleIds()).toEqual([1]);
    });

    it('keeps its state in the store only', async () => {
        const setItem = vi.spyOn(Storage.prototype, 'setItem');
        await loadOverlay([{ comment_id: 10, items: [item(1, 3)] }]);
        const menu = mount();

        menu.toggleCommenter(3);
        menu.toggleProcessed();

        expect(setItem).not.toHaveBeenCalled();
        // A fresh store (a reload) starts from the defaults.
        const reloaded = createOverlayStore();
        expect(reloaded.filters.hiddenCommenterIds.size).toBe(0);
        expect(reloaded.filters.showProcessed).toBe(false);
    });

    it('is shown to the author with at least one annotation, processed or filtered out included', async () => {
        await loadOverlay([{ comment_id: 10, items: [item(1, 3, { is_processed: true })] }]);
        const menu = mount();
        expect(menu.shown).toBe(true);

        menu.toggleCommenter(3);
        expect(menu.shown).toBe(true);
    });

    it('is hidden when the chapter has no annotation', async () => {
        await loadOverlay([]);

        expect(mount().shown).toBe(false);
    });

    it('is hidden while the heat map is on, back with the same filters after', async () => {
        await loadOverlay([{ comment_id: 10, items: [item(1, 3), item(2, 4)] }]);
        const menu = mount();
        menu.toggleCommenter(3);
        menu.open = true;

        overlay.heatOn = true;
        expect(menu.shown).toBe(false);

        overlay.heatOn = false;
        expect(menu.shown).toBe(true);
        expect(visibleIds()).toEqual([2]);
    });

    it('is not rendered for a commenter', async () => {
        await loadOverlay([{ comment_id: 10, items: [item(1, 1)] }], 'commenter');

        expect(mount().shown).toBe(false);
    });
});
