import { fetchAnnotations, setProcessed, deleteAnnotation } from './api.js';

/** `<x-shared::modal>` name of the server-mode pop-up. */
export const MODAL_NAME = 'annotations-server';

const ROLE_AUTHOR = 'author';

/**
 * Server-mode pop-up: the published annotations under one root comment, opened
 * by the « N annotations » button of a comment item (window event
 * `annotations:open` { commentId }). One instance per comment list, outside the
 * root-comment form. Fetches on the first open of a comment and keeps the list
 * in a cache; mutations update that cache in place.
 *
 * Context lives in this closure, read once in init(): methods are called from
 * inside <x-shared::modal> and <x-shared::button> (their own x-data), where
 * `$root` / `$el` are not this component.
 */
export function annotationsModal() {
    const cache = new Map();
    let labels = { one: '', many: '' };
    let messages = { load: '', action: '' };

    const updateButton = (commentId, count) => {
        const button = document.querySelector(`[data-annotations-button][data-comment-id="${commentId}"]`);
        if (!button) return;
        const label = button.querySelector('[data-annotations-count]');
        if (label) {
            label.textContent = count === 1 ? labels.one : labels.many.replace('__COUNT__', String(count));
        }
        button.hidden = count === 0;
    };

    return {
        commentId: null,
        viewerRole: null,
        items: [],
        loading: false,
        busyId: null,
        error: '',

        init() {
            const data = this.$root.dataset;
            labels = { one: data.labelOne ?? '', many: data.labelMany ?? '' };
            messages = { load: data.loadError ?? '', action: data.actionError ?? '' };
        },

        async open(commentId) {
            const id = Number(commentId);
            this.commentId = id;
            this.error = '';
            window.dispatchEvent(new CustomEvent('open-modal', { detail: MODAL_NAME }));

            const cached = cache.get(id);
            if (cached) {
                this.viewerRole = cached.viewerRole;
                this.items = cached.items;
                return;
            }

            this.viewerRole = null;
            this.items = [];
            this.loading = true;
            try {
                const payload = await fetchAnnotations(id);
                const entry = { viewerRole: payload.viewer_role, items: payload.items ?? [] };
                cache.set(id, entry);
                if (this.commentId !== id) return;
                this.viewerRole = entry.viewerRole;
                this.items = entry.items;
            } catch (e) {
                if (this.commentId === id) this.error = messages.load;
            } finally {
                if (this.commentId === id) this.loading = false;
            }
        },

        canToggle(row) {
            return !!row.can_mark_as_processed;
        },

        canRemove(row) {
            return !!row.can_delete;
        },

        /** The processed marker is for chapter authors only — never the commenter. */
        showsProcessed(row) {
            return this.viewerRole === ROLE_AUTHOR && row.is_processed === true;
        },

        async toggle(row) {
            if (this.busyId !== null) return;
            this.busyId = row.id;
            this.error = '';
            try {
                const result = await setProcessed(row.id, !row.is_processed);
                row.is_processed = !!result.is_processed;
            } catch (e) {
                this.error = messages.action;
            } finally {
                this.busyId = null;
            }
        },

        async remove(row) {
            if (this.busyId !== null) return;
            const commentId = this.commentId;
            this.busyId = row.id;
            this.error = '';
            try {
                await deleteAnnotation(row.id);
                const entry = cache.get(commentId);
                const remaining = (entry?.items ?? this.items).filter((r) => r.id !== row.id);
                if (entry) entry.items = remaining;
                if (this.commentId === commentId) this.items = remaining;
                updateButton(commentId, remaining.length);
            } catch (e) {
                this.error = messages.action;
            } finally {
                this.busyId = null;
            }
        },
    };
}
