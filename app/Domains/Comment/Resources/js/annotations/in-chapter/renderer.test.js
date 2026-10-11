import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { renderHighlights, HIGHLIGHT_ANNOTATION, HIGHLIGHT_PROCESSED } from './renderer.js';

class FakeHighlight {
    constructor(...ranges) {
        this.ranges = ranges;
    }
}

let article;

function mountArticle(html) {
    article = document.createElement('article');
    article.innerHTML = html;
    document.body.appendChild(article);
    return article;
}

function row(id, highlighted, overrides = {}) {
    return { id, highlighted_text: highlighted, prefix: '', suffix: '', is_processed: null, ...overrides };
}

beforeEach(() => {
    vi.stubGlobal('CSS', { highlights: new Map() });
    vi.stubGlobal('Highlight', FakeHighlight);
});

afterEach(() => {
    vi.unstubAllGlobals();
    article?.remove();
});

describe('renderHighlights', () => {
    it('registers ok and moved rows, drops missing ones', () => {
        mountArticle('<div class="ce-block--text"><p>Le chat dort sur le tapis rouge du salon.</p></div>');

        const result = renderHighlights(article, [
            row(1, 'chat dort'),
            // Highlighted text changed, but prefix/suffix still frame one region: moved.
            row(2, 'tapis bleu', { prefix: 'sur le ', suffix: ' du salon.' }),
            row(3, 'introuvable'),
        ]);

        expect(result.map((r) => r.row.id)).toEqual([1, 2]);
        expect(result[0].range.toString()).toBe('chat dort');
        expect(result[1].range.toString()).toBe('tapis rouge');
        const tint = CSS.highlights.get(HIGHLIGHT_ANNOTATION);
        expect(tint.ranges).toHaveLength(2);
    });

    it('puts processed rows in the processed highlight', () => {
        mountArticle('<div class="ce-block--text"><p>Un passage traité et un autre non.</p></div>');

        renderHighlights(article, [
            row(1, 'passage traité', { is_processed: true }),
            row(2, 'autre non', { is_processed: false }),
        ]);

        expect(CSS.highlights.get(HIGHLIGHT_PROCESSED).ranges.map(String)).toEqual(['passage traité']);
        expect(CSS.highlights.get(HIGHLIGHT_ANNOTATION).ranges.map(String)).toEqual(['autre non']);
    });

    it('only reads text blocks', () => {
        mountArticle('<div class="ce-block--image"><p>chat dort</p></div><div class="ce-block--text"><p>Le chat dort.</p></div>');

        const [{ range }] = renderHighlights(article, [row(1, 'chat dort')]);
        expect(range.startContainer.parentElement.closest('.ce-block--text')).not.toBeNull();
    });

    it('still finds the passage after Quote re-wraps the article in a quote mark', () => {
        mountArticle('<div class="ce-block--text"><p>Le chat dort sur le tapis.</p></div>');
        renderHighlights(article, [row(1, 'dort sur')]);

        // Quote unwraps/re-wraps: the text node our first range lived in is split.
        const text = article.querySelector('p').firstChild;
        const range = document.createRange();
        range.setStart(text, 3);
        range.setEnd(text, 12);
        const mark = document.createElement('mark');
        mark.className = 'quote-tint';
        range.surroundContents(mark);

        const [{ range: rebuilt }] = renderHighlights(article, [row(1, 'dort sur')]);
        expect(rebuilt.toString()).toBe('dort sur');
        expect(CSS.highlights.get(HIGHLIGHT_ANNOTATION).ranges.map(String)).toEqual(['dort sur']);
    });

    it('clears both highlights when no row is visible', () => {
        mountArticle('<div class="ce-block--text"><p>Le chat dort.</p></div>');
        renderHighlights(article, [row(1, 'chat', { is_processed: true }), row(2, 'dort')]);

        expect(renderHighlights(article, [])).toEqual([]);
        expect(CSS.highlights.has(HIGHLIGHT_ANNOTATION)).toBe(false);
        expect(CSS.highlights.has(HIGHLIGHT_PROCESSED)).toBe(false);
    });

    it('returns ranges without throwing when CSS.highlights is absent', () => {
        vi.stubGlobal('CSS', {});
        mountArticle('<div class="ce-block--text"><p>Le chat dort.</p></div>');

        const result = renderHighlights(article, [row(1, 'chat')]);
        expect(result.map((r) => r.range.toString())).toEqual(['chat']);
    });

    it('does not throw when CSS itself is undefined', () => {
        vi.stubGlobal('CSS', undefined);
        mountArticle('<div class="ce-block--text"><p>Le chat dort.</p></div>');

        expect(() => renderHighlights(article, [row(1, 'chat')])).not.toThrow();
    });
});
