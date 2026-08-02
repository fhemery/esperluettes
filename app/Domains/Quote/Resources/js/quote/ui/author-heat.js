import { buildCanonicalText } from '../../../../../Shared/Resources/js/anchoring/canonical-text.js';
import { annotateRanges } from './author-anchoring.js';
import { groupPassages, segmentByDepth } from './author-summary.js';

/**
 * Tint deepening with the number of quotes covering the text. Written as whole
 * class names so Tailwind's source scanner keeps them.
 */
const DEPTH_CLASSES = ['bg-accent/15', 'bg-accent/30', 'bg-accent/45', 'bg-accent/60'];

/** Marker height (`h-5`) plus a hairline, so nudged markers stay legible. */
const MARKER_MIN_GAP = 22;

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
export function quoteAuthorHeat({
    markerLabelOne = '',
    markerLabelOther = '',
    tintLabelOne = '',
    tintLabelOther = '',
} = {}) {
    return {
        markerLabelOne,
        markerLabelOther,
        tintLabelOne,
        tintLabelOther,
        _stopEffect: null,
        _articleEl: null,
        _gutterEl: null,
        _groups: [],
        _resolved: [],
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
            const resolved = annotateRanges(canonical, rows);

            this._resolved = resolved;

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
                mark.className = `quote-heat text-inherit cursor-pointer ${depthClass(segment.depth)}`;
                mark.dataset.quoteStart = String(segment.start);
                mark.dataset.quoteDepth = String(segment.depth);
                mark.setAttribute('tabindex', '0');
                mark.setAttribute('role', 'button');
                mark.setAttribute('aria-label', this._tintLabel(segment.depth));
                mark.addEventListener('click', () => this._openPanel(this._quotesCovering(segment)));
                mark.addEventListener('keydown', event => {
                    if (event.key !== 'Enter' && event.key !== ' ') return;
                    event.preventDefault();
                    this._openPanel(this._quotesCovering(segment));
                });

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

        /**
         * A row of the chapter summary was selected: scroll its passage into
         * view and open its popover. The heat has just been turned on by the
         * store, so the marks exist by the time this runs.
         */
        focusGroup(groupKey) {
            const group = this._groups.find(g => g.key === groupKey);
            if (!group || group.stale) return;

            const liveRow = group.rows.find(r => r.range);
            const mark = this._articleEl.querySelector(
                `mark.quote-heat[data-quote-start="${liveRow.range.start}"]`
            );
            mark?.scrollIntoView?.({ behavior: 'smooth', block: 'center' });

            this._openPanel(this._sortNewestFirst(group.rows));
        },

        /**
         * Every quote whose live range covers the given canonical segment —
         * which, on the deepest segment of a pile of overlapping quotes, is all
         * of them. Newest first (A4).
         */
        _quotesCovering({ start, end }) {
            return this._sortNewestFirst(
                this._resolved.filter(r => r.range && r.range.start <= start && r.range.end >= end)
            );
        },

        _sortNewestFirst(quotes) {
            return quotes.slice().sort((a, b) => new Date(b.created_at ?? 0) - new Date(a.created_at ?? 0));
        },

        /**
         * The panel lives outside this component, so it is opened by event. The
         * payload carries the aggregate rows as the server sent them: they have
         * no note field at all, by design of `AggregateQuoteDto`.
         */
        _openPanel(quotes) {
            if (!quotes.length) return;
            window.dispatchEvent(new CustomEvent('quote:open-author-panel', { detail: { quotes } }));
        },

        _clear() {
            this._removeMarkers();
            this._groups = [];
            this._resolved = [];
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

                const el = document.createElement('button');
                el.type = 'button';
                el.className = 'absolute right-0 -translate-y-1/2 pointer-events-auto inline-flex items-center '
                    + 'justify-center min-w-5 h-5 px-1 rounded-full text-xs font-bold surface-accent text-on-surface';
                el.textContent = String(group.count);
                el.setAttribute('aria-label', this._markerLabel(group.count));
                el.dataset.quoteMarker = group.key;
                el.addEventListener('click', () => this._openPanel(this._sortNewestFirst(group.rows)));

                this._gutterEl.appendChild(el);
                this._markers.push({ el, mark });
            }

            this._positionMarkers();
        },

        /**
         * Two passages starting on the same line get the same natural `top` and
         * would stack into what reads as a single marker — the very case the
         * author most needs to see. Walk them top-down and push each one that is
         * too close down until it clears the previous.
         */
        _positionMarkers() {
            if (!this._gutterEl || !this._markers.length) return;
            const gutterTop = this._gutterEl.getBoundingClientRect().top;

            const placed = this._markers
                .map(({ el, mark }) => {
                    const rect = mark.getBoundingClientRect();
                    return { el, top: rect.top - gutterTop + rect.height / 2 };
                })
                .sort((a, b) => a.top - b.top);

            let previousTop = null;
            for (const marker of placed) {
                if (previousTop !== null && marker.top - previousTop < MARKER_MIN_GAP) {
                    marker.top = previousTop + MARKER_MIN_GAP;
                }
                marker.el.style.top = `${marker.top}px`;
                previousTop = marker.top;
            }
        },

        _markerLabel(count) {
            const template = count > 1 ? this.markerLabelOther : this.markerLabelOne;
            return template.replace('{count}', String(count));
        },

        _tintLabel(count) {
            const template = count > 1 ? this.tintLabelOther : this.tintLabelOne;
            return template.replace('{count}', String(count));
        },
    };
}

/**
 * The author's passage popover: who quoted the passage, and when. Distinct from
 * `quotePanel`, which is the reader's own note/edit/delete panel — this one has
 * no note to show, because the payload has no note field.
 */
export function quoteAuthorPassagePanel({ titleOne = '', titleOther = '' } = {}) {
    return {
        titleOne,
        titleOther,
        open: false,
        quotes: [],

        show(quotes) {
            this.quotes = quotes ?? [];
            this.open = this.quotes.length > 0;
        },

        close() {
            this.open = false;
            this.quotes = [];
        },

        title() {
            const template = this.quotes.length > 1 ? this.titleOther : this.titleOne;
            return template.replace('{count}', String(this.quotes.length));
        },

        profileUrl(quote) {
            return `/profile/${quote.quoter.slug}`;
        },

        /** Relative date — « il y a 3 jours » — in the document's language. */
        relativeDate(iso) {
            const date = new Date(iso);
            if (Number.isNaN(date.getTime())) return '';

            const seconds = Math.round((date.getTime() - Date.now()) / 1000);
            const units = [
                ['year', 31536000], ['month', 2592000], ['week', 604800],
                ['day', 86400], ['hour', 3600], ['minute', 60],
            ];
            const formatter = new Intl.RelativeTimeFormat(document.documentElement.lang || undefined, {
                numeric: 'auto',
            });

            for (const [unit, size] of units) {
                if (Math.abs(seconds) >= size) return formatter.format(Math.round(seconds / size), unit);
            }
            return formatter.format(seconds, 'second');
        },
    };
}
