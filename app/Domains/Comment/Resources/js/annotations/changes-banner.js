import { saveAnnotationChanges } from './api.js';

/**
 * Sticky save banner for the pending `annotationChanges` slot (annotations of an
 * already-posted root comment). « Enregistrer » sends the whole slot in one PUT;
 * « Tout annuler » discards it. Hidden while the page's `[data-annotable]` has
 * no `data-root-comment-id` (no root comment yet: drafts mode).
 *
 * Window events dispatched:
 *  - `annotations:list-refreshed` { commentId, list } after a successful save;
 *  - `annotations:save-errors` { commentId, errors } (key → message, keys
 *    `adds.<tempId>`, `edits.<id>`, `deletes.<id>`) after a refused save;
 *  - `annotations:open` { commentId } from « Voir ».
 */
export function annotationChangesBanner() {
    let context = null;
    let text = {};
    let onChanged = null;

    const slot = () => window.commentDrafts
        ?.getAnnotationChanges(context.userId, context.entityType, context.entityId)
        ?? { adds: [], edits: {}, deletes: [] };

    const plural = (count, one, many) => (count === 1 ? one : many.replace('__COUNT__', String(count)));

    const keysOf = (changes) => [
        ...changes.adds.map((a) => `adds.${a.tempId}`),
        ...Object.keys(changes.edits).map((id) => `edits.${id}`),
        ...changes.deletes.map((id) => `deletes.${id}`),
    ];

    const firstMessage = (value) => (Array.isArray(value) ? value[0] : value) ?? '';

    const itemName = (key, changes) => {
        const [kind, id] = key.split('.');
        if (kind === 'adds') {
            const add = changes.adds.find((a) => a.tempId === id);
            return add ? `« ${add.highlighted} »` : key;
        }
        return kind === 'edits' ? text.itemEdit : text.itemDelete;
    };

    const updateCommentCount = (commentId, count) => {
        const button = document.querySelector(`[data-annotations-button][data-comment-id="${commentId}"]`);
        if (!button) return;
        const label = button.querySelector('[data-annotations-count]');
        if (label) label.textContent = plural(count, text.countOne, text.countMany);
        button.hidden = count === 0;
    };

    return {
        count: 0,
        rootCommentId: null,
        saving: false,
        error: '',
        itemErrors: {},

        get label() {
            return plural(this.count, text.labelOne, text.labelMany);
        },

        /** One line per refused item, naming it. */
        get errorLines() {
            const changes = slot();
            return Object.entries(this.itemErrors)
                .map(([key, message]) => `${itemName(key, changes)} : ${message}`);
        },

        init() {
            const data = this.$root.dataset;
            context = {
                userId: data.userId ? parseInt(data.userId, 10) : null,
                entityType: data.entityType,
                entityId: data.entityId,
            };
            text = {
                labelOne: data.labelOne ?? '',
                labelMany: data.labelMany ?? '',
                countOne: data.countOne ?? '',
                countMany: data.countMany ?? '',
                discardConfirm: data.discardConfirm ?? '',
                errorItems: data.errorItems ?? '',
                errorGeneric: data.errorGeneric ?? '',
                stale: data.stale ?? '',
                itemEdit: data.itemEdit ?? '',
                itemDelete: data.itemDelete ?? '',
            };
            const rootId = document.querySelector('[data-annotable]')?.dataset.rootCommentId;
            this.rootCommentId = rootId ? parseInt(rootId, 10) : null;

            this.refresh();
            // This bundle loads in <head>; the draft module is a body-end script.
            if (!window.commentDrafts) {
                document.addEventListener('DOMContentLoaded', () => this.refresh(), { once: true });
            }

            onChanged = (event) => {
                const detail = event.detail ?? {};
                if (detail.entityType !== context.entityType) return;
                if (String(detail.entityId) !== String(context.entityId)) return;
                this.refresh();
            };
            window.addEventListener('comment-drafts:annotation-changes-changed', onChanged);
        },

        destroy() {
            if (onChanged) window.removeEventListener('comment-drafts:annotation-changes-changed', onChanged);
        },

        refresh() {
            if (!this.rootCommentId) {
                this.count = 0;
                return;
            }
            const changes = slot();
            this.count = keysOf(changes).length;
            // Drop errors of items that are no longer pending (undone or discarded).
            const live = new Set(keysOf(changes));
            this.itemErrors = Object.fromEntries(Object.entries(this.itemErrors).filter(([key]) => live.has(key)));
            if (Object.keys(this.itemErrors).length === 0 && this.error === text.errorItems) this.error = '';
        },

        async save() {
            if (this.saving || !this.rootCommentId || this.count === 0) return;
            const commentId = this.rootCommentId;
            const changes = slot();
            this.saving = true;
            this.error = '';
            try {
                const result = await saveAnnotationChanges(commentId, changes);
                if (result.ok) {
                    this.itemErrors = {};
                    window.commentDrafts?.clearAnnotationChanges(context.userId, context.entityType, context.entityId);
                    this.refresh();
                    window.dispatchEvent(new CustomEvent('annotations:list-refreshed', {
                        detail: { commentId, list: result.list },
                    }));
                    updateCommentCount(commentId, result.list?.items?.length ?? 0);
                    return;
                }
                const errors = {};
                if (result.status === 404) {
                    keysOf(changes).forEach((key) => { errors[key] = text.stale; });
                } else {
                    Object.entries(result.errors ?? {}).forEach(([key, value]) => {
                        errors[key] = firstMessage(value);
                    });
                }
                this.itemErrors = errors;
                this.error = Object.keys(errors).length > 0 ? text.errorItems : text.errorGeneric;
                window.dispatchEvent(new CustomEvent('annotations:save-errors', {
                    detail: { commentId, errors },
                }));
            } catch (e) {
                this.error = text.errorGeneric;
            } finally {
                this.saving = false;
            }
        },

        discardAll() {
            if (!window.confirm(text.discardConfirm)) return;
            window.commentDrafts?.clearAnnotationChanges(context.userId, context.entityType, context.entityId);
            this.itemErrors = {};
            this.error = '';
            this.refresh();
        },

        show() {
            if (!this.rootCommentId) return;
            window.dispatchEvent(new CustomEvent('annotations:open', { detail: { commentId: this.rootCommentId } }));
        },
    };
}
