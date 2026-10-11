import { annotatorName } from './annotator.js';

/**
 * The author's filter menu, `Alpine.data('annotationFilterMenu')`: one entry per
 * commenter of the chapter (all ticked) and « Afficher les annotations
 * traitées » (off). It only writes `Alpine.store('annotationOverlay').filters`,
 * which drive the tint and the gutter; nothing is persisted (a reload resets).
 * Anonymised commenters (`author_id` null) share one entry, under the unknown
 * user's name and the default avatar.
 */
export function annotationFilterMenu() {
    let unknownName = '';
    let defaultAvatar = '';

    return {
        open: false,

        init() {
            unknownName = this.$root.dataset.unknownName ?? '';
            defaultAvatar = this.$root.dataset.defaultAvatar ?? '';
        },

        get overlay() {
            return this.$store.annotationOverlay;
        },

        /** Author only, with at least one annotation (processed or filtered out too), display on. */
        get shown() {
            const overlay = this.overlay;
            return overlay.role === 'author' && overlay.shown && overlay.rows.length > 0;
        },

        /** [{ id, name, avatar }] in row order, one per author_id. */
        get commenters() {
            const seen = new Map();
            for (const row of this.overlay.rows) {
                const id = row.author_id ?? null;
                if (seen.has(id)) continue;
                seen.set(id, id === null
                    ? { id, name: unknownName, avatar: defaultAvatar }
                    : {
                        id,
                        name: annotatorName(row.author_profile, unknownName),
                        avatar: row.author_profile?.avatar_url || defaultAvatar,
                    });
            }
            return [...seen.values()];
        },

        get showProcessed() {
            return this.overlay.filters.showProcessed === true;
        },

        isTicked(id) {
            return !this.overlay.filters.hiddenCommenterIds.has(id ?? null);
        },

        toggleCommenter(id) {
            const key = id ?? null;
            // A new Set rather than a mutation, so Alpine's effects see the change.
            const hidden = new Set(this.overlay.filters.hiddenCommenterIds);
            if (hidden.has(key)) hidden.delete(key);
            else hidden.add(key);
            this.overlay.filters.hiddenCommenterIds = hidden;
        },

        toggleProcessed() {
            this.overlay.filters.showProcessed = !this.showProcessed;
        },
    };
}
