// In-chapter annotation overlay: Alpine root wrapping the chapter article.
// Loads the viewer's annotations into Alpine.store('annotationOverlay') and
// tints their passages (renderer.js). Observes Quote's stores — `quotes.items`
// (its re-wraps invalidate our ranges) and `quoteAggregate.visible` (heat map
// on → no annotation display).
import './overlay.css';
import { createOverlayStore } from './store.js';
import { renderHighlights } from './renderer.js';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    Alpine.store('annotationOverlay', createOverlayStore());

    Alpine.data('annotationOverlay', () => {
        // Kept out of the reactive component: the render effect writes `frame`.
        let effects = [];
        let frame = null;

        return {
            role: null,
            mode: 'draft',
            rootCommentId: null,
            entityType: '',
            entityId: null,
            userId: null,
            url: '',

            init() {
                const d = this.$el.dataset;
                this.role = d.role || null;
                this.mode = d.mode || 'draft';
                this.rootCommentId = d.rootCommentId ? Number(d.rootCommentId) : null;
                this.entityType = d.entityType || '';
                this.entityId = d.entityId ? Number(d.entityId) : null;
                this.userId = d.userId ? Number(d.userId) : null;
                this.url = d.url || '';

                const articleEl = this.$el.querySelector('[data-quote-article]');
                if (!articleEl) return;

                const store = Alpine.store('annotationOverlay');
                store.load({
                    role: this.role,
                    mode: this.mode,
                    rootCommentId: this.rootCommentId,
                    entityType: this.entityType,
                    entityId: this.entityId,
                    userId: this.userId,
                    url: this.url,
                });

                // Created here, after every alpine:init handler, so Quote's stores exist.
                effects.push(Alpine.effect(() => {
                    store.heatOn = Alpine.store('quoteAggregate')?.visible === true;
                }));
                effects.push(Alpine.effect(() => {
                    // Read to subscribe: Quote's re-wrap after a change collapses our ranges.
                    Alpine.store('quotes')?.items?.slice();
                    const rows = store.visibleRows.slice();
                    // One frame later, so Quote's own effect has re-wrapped the article first.
                    if (frame !== null) cancelAnimationFrame(frame);
                    frame = requestAnimationFrame(() => {
                        frame = null;
                        renderHighlights(articleEl, rows);
                    });
                }));
            },

            destroy() {
                effects.forEach((effect) => Alpine.release(effect));
                effects = [];
                if (frame !== null) cancelAnimationFrame(frame);
                Alpine.store('annotationOverlay')?.destroy();
            },
        };
    });
});
