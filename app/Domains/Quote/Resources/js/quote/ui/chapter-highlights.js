import { buildCanonicalText } from '../../../../../Shared/Resources/js/anchoring/canonical-text.js';
import { findAnchor } from '../../../../../Shared/Resources/js/anchoring/reanchor.js';

export function quoteHighlighter({ chapterId, storyId, markLabel = '' }) {
    return {
        chapterId,
        storyId,
        markLabel,
        _stopEffect: null,

        init() {
            const store = Alpine.store('quotes');
            const articleEl = this.$el.querySelector('[data-quote-article]');
            if (!articleEl) return;

            store.load(this.chapterId, this.storyId);

            this._stopEffect = Alpine.effect(() => {
                const items = store.items.slice();
                this._renderHighlights(articleEl, items);
            });
        },

        destroy() {
            this._stopEffect?.();
        },

        _renderHighlights(articleEl, quotes) {
            articleEl.querySelectorAll('mark.quote-tint').forEach(mark => {
                mark.replaceWith(...mark.childNodes);
            });
            articleEl.normalize();

            if (!quotes.length) return;

            const canonical = buildCanonicalText(articleEl);

            for (const quote of quotes) {
                if (quote.anchor_missing || !quote.chapter_available) continue;

                const result = findAnchor(canonical, {
                    prefix: quote.prefix ?? '',
                    highlighted: quote.highlighted_text ?? '',
                    suffix: quote.suffix ?? '',
                });

                if (result.status === 'missing') continue;

                this._applyHighlight(canonical.nodeMap, result.start, result.end, quote.id);
            }
        },

        _applyHighlight(nodeMap, start, end, quoteId) {
            const segments = nodeMap.filter(e => e.end > start && e.start < end);
            for (const entry of segments) {
                const nodeStart = Math.max(0, start - entry.start);
                const nodeEnd = Math.min(entry.domNode.length, end - entry.start);
                if (nodeStart >= nodeEnd) continue;
                const mark = document.createElement('mark');
                mark.className = 'quote-tint bg-tertiary/10 cursor-pointer';
                mark.dataset.quoteId = String(quoteId);
                mark.setAttribute('role', 'button');
                mark.setAttribute('tabindex', '0');
                if (this.markLabel) mark.setAttribute('aria-label', this.markLabel);
                try {
                    const range = document.createRange();
                    range.setStart(entry.domNode, nodeStart);
                    range.setEnd(entry.domNode, nodeEnd);
                    range.surroundContents(mark);
                } catch {
                    // range spans element boundary — skip
                }
            }
        },

        openPanel(event) {
            const mark = event.target.closest('mark.quote-tint');
            if (!mark) return;
            const quoteId = Number(mark.dataset.quoteId);
            const quote = Alpine.store('quotes').items.find(q => q.id === quoteId);
            if (!quote) return;
            this.$dispatch('quote:open-panel', { quote });
        },

        handleKey(event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            const mark = event.target.closest('mark.quote-tint');
            if (!mark) return;
            event.preventDefault();
            this.openPanel(event);
        },
    };
}
