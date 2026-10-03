import { fetchAnnotations, setProcessed, deleteAnnotation, postReply, deleteReply } from './api.js';
import { plainText } from './capture-form.js';
import { appendReply, removeReply } from './replies.js';
import { formatDate } from '../../../../Shared/Resources/js/date-utils.js';

/** `<x-shared::modal>` name of the server-mode pop-up. */
export const MODAL_NAME = 'annotations-server';

/** Id of the single reply editor rendered in the pop-up partial. */
export const REPLY_EDITOR_ID = 'annotation-reply-editor';

const ROLE_AUTHOR = 'author';
const ROLE_COMMENTER = 'commenter';
const ROLE_MODERATOR = 'moderator';

const replyTextarea = () => document.getElementById(`quill-editor-area-${REPLY_EDITOR_ID}`);

export const STATE_EDITED = 'edited';
export const STATE_DELETED = 'deleted';
export const STATE_ADDED = 'added';

const emptyChanges = () => ({ adds: [], edits: {}, deletes: [] });

/**
 * Server-mode pop-up: the published annotations under one root comment, opened
 * by the « N annotations » button of a comment item (window event
 * `annotations:open` { commentId }). One instance per comment list, outside the
 * root-comment form. Fetches on the first open of a comment and keeps the list
 * in a cache; mutations update that cache in place.
 *
 * For the root's writer (viewer_role `commenter`) the rows are overlaid with
 * the comment-draft `annotationChanges` slot: pending edits / deletes flag
 * their saved row, pending adds are listed after them, each can be undone.
 * « Modifier » / « Supprimer » only store pending changes; the save banner
 * sends them. Window events listened to:
 *  - `comment-drafts:annotation-changes-changed` — re-read the slot;
 *  - `annotations:save-errors` { commentId, errors } — per-row error lines;
 *  - `annotations:list-refreshed` { commentId, list } — replace the cache;
 *  - `editor-valid` { id, valid } — validity of the reply editor.
 *
 * For everyone, each root shows its reply thread (oldest first, as served).
 * « Répondre » (row `can_reply`) moves the single reply editor of the partial
 * under that row — one open at a time — and « Envoyer » posts it at once.
 * Reply « Supprimer » (`can_delete`) asks first, then uses the writer's reply
 * route, or the moderator delete for a moderator.
 *
 * Context lives in this closure, read once in init(): methods are called from
 * inside <x-shared::modal> and <x-shared::button> (their own x-data), where
 * `$root` / `$el` are not this component.
 */
export function annotationsModal() {
    const cache = new Map();
    let labels = { one: '', many: '' };
    let messages = { load: '', action: '', deleteWithReplies: '', deleteReply: '', replyEmpty: '', replyTooLong: '' };
    let context = { userId: null, entityType: null, entityId: null };
    let replyMax = 0;
    let listeners = [];
    // The reply editor and its resting place in the partial (absent in unit tests).
    let replyEditor = null;
    let replyHome = null;

    /** Replace the cached list of `commentId`, and the shown one when it is on screen. */
    const setItems = (self, commentId, transform) => {
        const entry = cache.get(commentId);
        const next = transform(entry?.items ?? (self.commentId === commentId ? self.items : []));
        if (entry) entry.items = next;
        if (self.commentId === commentId) self.items = next;
        return next;
    };

    const parkReplyEditor = () => {
        if (replyEditor && replyHome && replyEditor.parentElement !== replyHome) replyHome.appendChild(replyEditor);
    };

    const resetReplyBody = () => {
        window.initQuillEditor?.(REPLY_EDITOR_ID);
        const textarea = replyTextarea();
        if (!textarea) return;
        textarea.value = '';
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const updateButton = (commentId, count) => {
        const button = document.querySelector(`[data-annotations-button][data-comment-id="${commentId}"]`);
        if (!button) return;
        const label = button.querySelector('[data-annotations-count]');
        if (label) {
            label.textContent = count === 1 ? labels.one : labels.many.replace('__COUNT__', String(count));
        }
        button.hidden = count === 0;
    };

    const store = () => (context.userId ? window.commentDrafts : null);

    const readChanges = () => store()
        ?.getAnnotationChanges(context.userId, context.entityType, context.entityId)
        ?? emptyChanges();

    const errorKeys = (row) => (row.state === STATE_ADDED
        ? [`adds.${row.tempId}`]
        : [`edits.${row.id}`, `deletes.${row.id}`]);

    const liveKeys = (changes) => new Set([
        ...changes.adds.map((a) => `adds.${a.tempId}`),
        ...Object.keys(changes.edits).map((id) => `edits.${id}`),
        ...changes.deletes.map((id) => `deletes.${id}`),
    ]);

    return {
        commentId: null,
        viewerRole: null,
        items: [],
        changes: emptyChanges(),
        saveErrors: {},
        loading: false,
        busyId: null,
        error: '',
        replyingTo: null,
        replyValid: false,
        replyError: '',
        sendingReply: false,
        hintFor: null,

        init() {
            const data = this.$root.dataset;
            labels = { one: data.labelOne ?? '', many: data.labelMany ?? '' };
            messages = {
                load: data.loadError ?? '',
                action: data.actionError ?? '',
                deleteWithReplies: data.deleteWithRepliesConfirm ?? '',
                deleteReply: data.deleteReplyConfirm ?? '',
                replyEmpty: data.replyEmpty ?? '',
                replyTooLong: data.replyTooLong ?? '',
            };
            replyMax = Number(data.replyMaxLength ?? 0);
            replyEditor = this.$root.querySelector('[data-reply-editor]');
            replyHome = replyEditor?.parentElement ?? null;
            context = {
                userId: data.userId ? parseInt(data.userId, 10) : null,
                entityType: data.entityType ?? null,
                entityId: data.entityId ?? null,
            };

            const on = (name, handler) => {
                window.addEventListener(name, handler);
                listeners.push([name, handler]);
            };
            on('comment-drafts:annotation-changes-changed', (event) => {
                const detail = event.detail ?? {};
                if (detail.entityType !== context.entityType) return;
                if (String(detail.entityId) !== String(context.entityId)) return;
                this.refreshChanges();
            });
            on('annotations:save-errors', (event) => {
                const detail = event.detail ?? {};
                if (Number(detail.commentId) !== this.commentId) return;
                this.saveErrors = { ...(detail.errors ?? {}) };
            });
            on('annotations:list-refreshed', (event) => {
                const detail = event.detail ?? {};
                if (!detail.list) return;
                const id = Number(detail.commentId);
                const entry = { viewerRole: detail.list.viewer_role, items: detail.list.items ?? [] };
                cache.set(id, entry);
                if (this.commentId !== id) return;
                this.viewerRole = entry.viewerRole;
                this.items = entry.items;
                this.saveErrors = {};
            });
            on('editor-valid', (event) => {
                if (event.detail?.id !== REPLY_EDITOR_ID) return;
                this.replyValid = !!event.detail.valid;
            });
        },

        destroy() {
            listeners.forEach(([name, handler]) => window.removeEventListener(name, handler));
            listeners = [];
        },

        get isCommenter() {
            return this.viewerRole === ROLE_COMMENTER;
        },

        /**
         * What the list renders. Authors and moderators: the server rows as is.
         * Commenter: server rows carrying their pending state (`edited` shows
         * the pending body), then the pending adds.
         */
        get rows() {
            if (!this.isCommenter) return this.items;
            const { adds, edits, deletes } = this.changes;
            const saved = this.items.map((item) => {
                let state = null;
                if (deletes.includes(item.id)) state = STATE_DELETED;
                else if (Object.prototype.hasOwnProperty.call(edits, item.id)) state = STATE_EDITED;
                return {
                    ...item,
                    state,
                    body: state === STATE_EDITED ? edits[item.id] : item.body,
                };
            });
            const added = adds.map((add) => ({
                id: `add-${add.tempId}`,
                tempId: add.tempId,
                state: STATE_ADDED,
                body: add.body,
                highlighted_text: add.highlighted,
                replies: [],
                can_edit: false,
                can_delete: false,
                can_mark_as_processed: false,
            }));
            return [...saved, ...added];
        },

        refreshChanges() {
            this.changes = readChanges();
            const live = liveKeys(this.changes);
            this.saveErrors = Object.fromEntries(
                Object.entries(this.saveErrors).filter(([key]) => live.has(key)),
            );
        },

        async open(commentId) {
            const id = Number(commentId);
            if (this.commentId !== id) {
                this.saveErrors = {};
                this.hintFor = null;
            }
            this.cancelReply();
            this.commentId = id;
            this.error = '';
            this.refreshChanges();
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
            if (this.isCommenter) return !!row.can_edit && row.state !== STATE_DELETED && row.state !== STATE_ADDED;
            return !!row.can_delete;
        },

        /** Never on a row pending deletion: the store would keep both changes. */
        canEdit(row) {
            return this.isCommenter && !!row.can_edit && (row.state === null || row.state === STATE_EDITED);
        },

        canUndo(row) {
            return this.isCommenter && !!row.state;
        },

        rowError(row) {
            if (!this.isCommenter) return '';
            const key = errorKeys(row).find((k) => this.saveErrors[k]);
            return key ? this.saveErrors[key] : '';
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

        /** Commenter: reopen the capture form on the (pending) body, stored as a pending edit. */
        edit(row) {
            if (!this.canEdit(row)) return;
            window.dispatchEvent(new CustomEvent('annotations:edit-saved-row', {
                detail: { id: row.id, body: row.body, highlighted: row.highlighted_text },
            }));
        },

        /** Commenter: store a pending delete. Moderator: delete now. */
        async remove(row) {
            if (this.isCommenter) {
                if (!this.canRemove(row)) return;
                if ((row.replies?.length ?? 0) > 0 && !window.confirm(messages.deleteWithReplies)) return;
                store()?.setPendingDelete(context.userId, context.entityType, context.entityId, row.id);
                return;
            }
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

        /** Commenter: drop the row's pending change (also « Retirer » after a refused save). */
        undo(row) {
            const drafts = store();
            if (!drafts || !row.state) return;
            const args = [context.userId, context.entityType, context.entityId];
            if (row.state === STATE_ADDED) drafts.removePendingAdd(...args, row.tempId);
            else if (row.state === STATE_DELETED) drafts.undoPendingDelete(...args, row.id);
            else drafts.undoPendingEdit(...args, row.id);
        },

        replyAuthor(reply) {
            return reply.author_profile?.display_name ?? '';
        },

        replyDate(reply) {
            return reply.created_at ? formatDate(reply.created_at) : '';
        },

        canReply(row) {
            return !!row.can_reply;
        },

        canDeleteReply(reply) {
            return !!reply.can_delete;
        },

        /** The hint after an author's reply: replies notify nobody, the root comment does. */
        showsHint(row) {
            return this.viewerRole === ROLE_AUTHOR && this.hintFor === row.id;
        },

        /** Move the reply editor, emptied, under `row`; it replaces any other open one. */
        startReply(row) {
            if (!this.canReply(row)) return;
            this.replyingTo = row.id;
            this.replyError = '';
            this.replyValid = false;
            resetReplyBody();
            const move = () => {
                const slot = this.$root?.querySelector(`[data-reply-slot="${row.id}"]`);
                if (slot && replyEditor) {
                    slot.appendChild(replyEditor);
                    replyEditor.querySelector('.ql-editor')?.focus();
                }
            };
            if (this.$nextTick) this.$nextTick(move);
            else move();
        },

        cancelReply() {
            this.replyingTo = null;
            this.replyError = '';
            parkReplyEditor();
        },

        async sendReply() {
            if (this.replyingTo === null || this.sendingReply) return;
            const body = replyTextarea()?.value ?? '';
            const length = plainText(body).trim().length;
            if (length === 0) {
                this.replyError = messages.replyEmpty;
                return;
            }
            if (replyMax > 0 && length > replyMax) {
                this.replyError = messages.replyTooLong;
                return;
            }

            const commentId = this.commentId;
            const rootId = this.replyingTo;
            this.sendingReply = true;
            this.replyError = '';
            try {
                const reply = await postReply(rootId, body);
                setItems(this, commentId, (list) => appendReply(list, rootId, reply));
                if (this.commentId === commentId) {
                    this.hintFor = rootId;
                    this.cancelReply();
                }
            } catch (e) {
                if (this.commentId === commentId) this.replyError = messages.action;
            } finally {
                this.sendingReply = false;
            }
        },

        /** Writer: their reply route. Moderator: the moderator delete, which also takes replies. */
        async removeReply(reply) {
            if (!this.canDeleteReply(reply) || this.busyId !== null) return;
            if (!window.confirm(messages.deleteReply)) return;
            const commentId = this.commentId;
            this.busyId = reply.id;
            this.error = '';
            try {
                if (this.viewerRole === ROLE_MODERATOR) await deleteAnnotation(reply.id);
                else await deleteReply(reply.id);
                setItems(this, commentId, (list) => removeReply(list, reply.id));
            } catch (e) {
                this.error = messages.action;
            } finally {
                this.busyId = null;
            }
        },
    };
}
