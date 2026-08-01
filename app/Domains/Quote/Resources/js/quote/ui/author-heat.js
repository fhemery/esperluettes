import { buildCanonicalText } from '../../../../../Shared/Resources/js/anchoring/canonical-text.js';
import { findAnchor } from '../../../../../Shared/Resources/js/anchoring/reanchor.js';
import { groupPassages, segmentByDepth } from './author-summary.js';

/**
 * Tint deepening with the number of quotes covering the text. Written as whole
 * class names so Tailwind's source scanner keeps them.
 */
const DEPTH_CLASSES = ['bg-accent/15', 'bg-accent/30', 'bg-accent/45', 'bg-accent/60'];

function depthClass(depth) {
    return DEPTH_CLASSES[Math.min(depth, DEPTH_CLASSES.length) - 1];
}

/**
 * Author heat: the depth-graded tint over the quoted passages of a chapter,
 * plus the md+ margin markers carrying each passage's count.
 *
 * Everything is derived from the `quoteAggregate` store: nothing is rendered
 * until the author turns the heat on, and turning it off removes it all.
 */
export function quoteAuthorHeat({ markerLabelOne = '', markerLabelOther = '' } = {}) {
    return {
        markerLabelOne,
        markerLabelOther,
        _stopEffect: null,
        _articleEl: null,
        _gutterEl: null,
        _groups: [],
        _markers: [],
        _resizeObserver: null,
        _onViewportChange: null,
        _images: [],

        init() {
            const store = Alpine.store('quoteAggregate');
            this._articleEl = this.$el.querySelector('[data-quote-article]');
            this._gutterEl = this.$refs.gutter ?? null;
            if (!this._articleEl) return;

            this._stopEffect = Alpine.effect(() => {
                const rows = store.visible ? store.rows.slice() : [];
                this._render(rows);
            });

            this._onViewportChange = () => this._syncMarkers();
            window.addEventListener('resize', this._onViewportChange);

            // The article reflows long after `fonts.ready`: an advanced chapter
            // holds lazily-loaded images, so every line below one of them moves
            // when it finally arrives. Watch both the article box and the images.
            if (typeof ResizeObserver !== 'undefined') {
                this._resizeObserver = new ResizeObserver(this._onViewportChange);
                this._resizeObserver.observe(this._articleEl);
            }

            this._images = Array.from(this._articleEl.querySelectorAll('img'));
            this._images.forEach(img => img.addEventListener('load', this._onViewportChange));
        },

        destroy() {
            this._stopEffect?.();
            this._resizeObserver?.disconnect();
            if (this._onViewportChange) {
                window.removeEventListener('resize', this._onViewportChange);
                this._images.forEach(img => img.removeEventListener('load', this._onViewportChange));
            }
        },

        _render(rows) {
            this._clear();
            if (!rows.length) return;

            const canonical = buildCanonicalText(this._articleEl);

            const resolved = rows.map(row => {
                const result = findAnchor(canonical, {
                    prefix: row.prefix ?? '',
                    highlighted: row.highlighted_text ?? '',
                    suffix: row.suffix ?? '',
                });

                return {
                    ...row,
                    range: result.status === 'missing' ? null : { start: result.start, end: result.end },
                };
            });

            const liveRanges = resolved.filter(r => r.range).map(r => r.range);
            if (!liveRanges.length) return;

            // Wrapping splits the text node it touches, and the original node
            // keeps the head — so offsets before the split stay valid only if the
            // later segments are wrapped first. Hence right to left.
            const segments = segmentByDepth(liveRanges).sort((a, b) => b.start - a.start);
            for (const segment of segments) {
                this._wrapSegment(canonical.nodeMap, segment);
            }

            this._groups = groupPassages(resolved);
            this._syncMarkers();
        },

        /**
         * A canonical segment is not a DOM range: it can span several text nodes
         * (one per `ce-block`). Re-split it per nodeMap entry and wrap each piece
         * on its own — one `surroundContents()` per segment would throw.
         */
        _wrapSegment(nodeMap, segment) {
            const entries = nodeMap
                .filter(e => e.end > segment.start && e.start < segment.end)
                .sort((a, b) => b.start - a.start);

            for (const entry of entries) {
                const nodeStart = Math.max(0, segment.start - entry.start);
                const nodeEnd = Math.min(entry.domNode.length, segment.end - entry.start);
                if (nodeStart >= nodeEnd) continue;

                const mark = document.createElement('mark');
                mark.className = `quote-heat text-inherit ${depthClass(segment.depth)}`;
                mark.dataset.quoteStart = String(segment.start);
                mark.dataset.quoteDepth = String(segment.depth);

                try {
                    const range = document.createRange();
                    range.setStart(entry.domNode, nodeStart);
                    range.setEnd(entry.domNode, nodeEnd);
                    range.surroundContents(mark);
                } catch {
                    // Defensive: a piece that is not wrappable is simply not tinted.
                }
            }
        },

        _clear() {
            this._removeMarkers();
            this._groups = [];
            this._articleEl.querySelectorAll('mark.quote-heat').forEach(mark => {
                mark.replaceWith(...mark.childNodes);
            });
            this._articleEl.normalize();
        },

        _removeMarkers() {
            this._markers.forEach(marker => marker.el.remove());
            this._markers = [];
        },

        /**
         * Below `md` the gutter is `display:none`, and no marker is built at all
         * — not merely hidden.
         */
        _syncMarkers() {
            this._removeMarkers();
            if (!this._gutterEl || getComputedStyle(this._gutterEl).display === 'none') return;

            for (const group of this._groups) {
                if (group.stale) continue;

                const liveRow = group.rows.find(r => r.range);
                const mark = this._articleEl.querySelector(
                    `mark.quote-heat[data-quote-start="${liveRow.range.start}"]`
                );
                if (!mark) continue;

                const el = document.createElement('div');
                el.className = 'absolute right-0 -translate-y-1/2 pointer-events-auto inline-flex items-center '
                    + 'justify-center min-w-5 h-5 px-1 rounded-full text-xs font-bold surface-accent text-on-surface';
                el.textContent = String(group.count);
                el.setAttribute('aria-label', this._markerLabel(group.count));
                el.dataset.quoteMarker = group.key;

                this._gutterEl.appendChild(el);
                this._markers.push({ el, mark });
            }

            this._positionMarkers();
        },

        _positionMarkers() {
            if (!this._gutterEl || !this._markers.length) return;
            const gutterTop = this._gutterEl.getBoundingClientRect().top;

            for (const { el, mark } of this._markers) {
                const rect = mark.getBoundingClientRect();
                el.style.top = `${rect.top - gutterTop + rect.height / 2}px`;
            }
        },

        _markerLabel(count) {
            const template = count > 1 ? this.markerLabelOther : this.markerLabelOne;
            return template.replace('{count}', String(count));
        },
    };
}
