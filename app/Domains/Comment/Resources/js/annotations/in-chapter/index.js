// In-chapter annotation overlay: Alpine root wrapping the chapter article.
// Loads the viewer's annotations into Alpine.store('annotationOverlay') and
// tints their passages (renderer.js), with the annotators' avatars in the
// right gutter (gutter.js); an avatar opens the popover (popover.js); the author's
// filter menu (filter-menu.js) writes the store's filters. Observes Quote's stores — `quotes.items` (its re-wraps
// invalidate our ranges) and `quoteAggregate.visible` (heat map on → no
// annotation display).
import './overlay.css';
import { createOverlayStore } from './store.js';
import { renderHighlights } from './renderer.js';
import { groupByPosition, gutterItems } from './gutter.js';
import { annotationPopover } from './popover.js';
import { annotationFilterMenu } from './filter-menu.js';
import { annotatorName } from './annotator.js';
import { annotationList, FOCUS_EVENT } from './list.js';

/** Frames to wait for the display to render before giving up on a focus request. */
const FOCUS_FRAMES = 20;

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    // Quote's store is read live, not mirrored through an effect: an effect writing
    // `heatOn` can land after the render effect in one Alpine flush, whose re-run is
    // then dropped, leaving the chapter untinted.
    Alpine.store('annotationOverlay', createOverlayStore({
        heatSource: () => Alpine.store('quoteAggregate')?.visible === true,
    }));
    Alpine.data('annotationPopover', annotationPopover);
    Alpine.data('annotationFilterMenu', annotationFilterMenu);
    Alpine.data('annotationList', annotationList);

    Alpine.data('annotationOverlay', () => {
        // Kept out of the reactive component: the render effect writes `frame`,
        // and `placed` holds live Ranges.
        let effects = [];
        let frame = null;
        let placed = [];
        let cleanups = [];

        return {
            role: null,
            mode: 'draft',
            rootCommentId: null,
            entityType: '',
            entityId: null,
            userId: null,
            url: '',
            defaultAvatar: '',
            /** Gutter buttons: [{ key, top, avatar, label, more, moreLabel, members }]. */
            groups: [],

            init() {
                const d = this.$el.dataset;
                this.role = d.role || null;
                this.mode = d.mode || 'draft';
                this.rootCommentId = d.rootCommentId ? Number(d.rootCommentId) : null;
                this.entityType = d.entityType || '';
                this.entityId = d.entityId ? Number(d.entityId) : null;
                this.userId = d.userId ? Number(d.userId) : null;
                this.url = d.url || '';
                this.defaultAvatar = d.defaultAvatar || '';

                const articleEl = this.$el.querySelector('[data-quote-article]');
                if (!articleEl) return;

                const relayout = () => this.layoutGutter();
                window.addEventListener('resize', relayout);
                cleanups.push(() => window.removeEventListener('resize', relayout));
                // Lazily-loaded images reflow every line below them (as in Quote's author heat).
                // The gutter spans the whole root: it resizes when the filter menu
                // appears above the article (shifting every passage down), and when
                // it is shown again after the heat map. Alpine's x-show shows one
                // frame late, after the render effect's own frame, hence observing.
                if (typeof ResizeObserver !== 'undefined') {
                    const observer = new ResizeObserver(relayout);
                    observer.observe(articleEl);
                    // x-ref is bound once the children are initialised, after init().
                    this.$nextTick(() => {
                        if (this.$refs.gutter) observer.observe(this.$refs.gutter);
                    });
                    cleanups.push(() => observer.disconnect());
                }
                articleEl.querySelectorAll('img').forEach((img) => {
                    img.addEventListener('load', relayout);
                    cleanups.push(() => img.removeEventListener('load', relayout));
                });

                const store = Alpine.store('annotationOverlay');
                const onFocus = (event) => this.focusRow(event.detail?.key);
                window.addEventListener(FOCUS_EVENT, onFocus);
                cleanups.push(() => window.removeEventListener(FOCUS_EVENT, onFocus));
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
                    // Read to subscribe: Quote's re-wrap after a change collapses our ranges.
                    Alpine.store('quotes')?.items?.slice();
                    const rows = store.visibleRows.slice();
                    // One frame later, so Quote's own effect has re-wrapped the article first.
                    if (frame !== null) cancelAnimationFrame(frame);
                    frame = requestAnimationFrame(() => {
                        frame = null;
                        placed = renderHighlights(articleEl, rows);
                        this.layoutGutter();
                    });
                }));
            },

            /** Rebuild the gutter buttons from the placed ranges; none below md. */
            layoutGutter() {
                const gutter = this.$refs.gutter;
                if (!gutter || getComputedStyle(gutter).display === 'none') {
                    this.groups = [];
                    return;
                }
                const labels = JSON.parse(this.$el.dataset.avatarLabels || '{}');
                const moreLabel = this.$el.dataset.moreLabel || '+{count}';
                const items = gutterItems(placed, gutter.getBoundingClientRect().top);
                this.groups = groupByPosition(items).map((group) => {
                    const { name, avatar } = this.annotator(group.lead.row);
                    const more = group.extra.length;
                    const template = more === 0 ? labels.zero : (more === 1 ? labels.one : labels.other);
                    return {
                        key: group.key,
                        top: group.top,
                        avatar: avatar || this.defaultAvatar,
                        label: (template ?? '').replace('{name}', name).replace('{count}', String(more)),
                        more,
                        /** Shown processed (A5): the avatar is dimmed. */
                        processed: group.lead.row.is_processed === true,
                        moreLabel: moreLabel.replace('{count}', String(more)),
                        /** Row keys the popover pages through. */
                        members: group.members.map((item) => item.key),
                    };
                });
            },

            /**
             * From the header list: show the annotations (Quote's heat map would
             * hide them), then scroll to the row's avatar and open its popover.
             * The gutter is rebuilt a frame or two after the display turns on.
             */
            focusRow(key) {
                if (!key) return;
                const store = Alpine.store('annotationOverlay');
                const heat = Alpine.store('quoteAggregate');
                if (heat?.visible) heat.visible = false;
                store.enabled = true;

                let frames = 0;
                const attempt = () => {
                    const group = this.groups.find((g) => g.members.includes(key));
                    const button = group
                        ? this.$el.querySelector(`[data-annotation-group="${group.key}"]`)
                        : null;
                    if (button) {
                        button.scrollIntoView({ block: 'center' });
                        button.click();
                    } else if (++frames < FOCUS_FRAMES) {
                        requestAnimationFrame(attempt);
                    }
                };
                requestAnimationFrame(attempt);
            },

            /** Server rows carry their profile; the commenter's local rows are theirs. */
            annotator(row) {
                if (row.source === 'server') {
                    return {
                        name: annotatorName(row.author_profile, this.$el.dataset.unknownName ?? ''),
                        avatar: row.author_profile?.avatar_url ?? '',
                    };
                }
                return { name: this.$el.dataset.userName ?? '', avatar: this.$el.dataset.userAvatar ?? '' };
            },

            destroy() {
                effects.forEach((effect) => Alpine.release(effect));
                effects = [];
                cleanups.forEach((cleanup) => cleanup());
                cleanups = [];
                placed = [];
                if (frame !== null) cancelAnimationFrame(frame);
                Alpine.store('annotationOverlay')?.destroy();
            },
        };
    });
});
