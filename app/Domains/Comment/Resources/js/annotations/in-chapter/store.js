const ROLE_AUTHOR = 'author';
const ROLE_COMMENTER = 'commenter';
const MODE_PENDING = 'pending';

const DRAFT_EVENTS = ['comment-drafts:annotations-changed', 'comment-drafts:annotation-changes-changed'];

const emptyChanges = () => ({ adds: [], edits: {}, deletes: [] });

/**
 * The in-chapter annotation state, registered as `Alpine.store('annotationOverlay')`.
 *
 * `rows` merges the server lists of `GET /comments/annotations/in-entity`
 * (authors: every commenter's roots; commenters: their own root) with the
 * commenter's local state from `window.commentDrafts`: the drafts slot in
 * `draft` mode, the pending `annotationChanges` in `pending` mode (edits
 * replace the body, adds are appended, pending deletes are left out).
 *
 * `visibleRows` is what the chapter shows: rows minus filtered commenters and
 * processed rows (unless `showProcessed`); nothing while the author's heat map
 * is on (`heatOn`, mirrored from Quote by the entry script).
 */
export function createOverlayStore() {
    let listeners = [];

    return {
        role: null,
        context: { mode: 'draft', rootCommentId: null, entityType: null, entityId: null, userId: null, url: '' },
        /** [{ comment_id, items }] in server order. */
        serverLists: [],
        rows: [],
        filters: { hiddenCommenterIds: new Set(), showProcessed: false },
        heatOn: false,

        get visibleRows() {
            if (this.heatOn) return [];
            const hidden = this.filters.hiddenCommenterIds;
            const showProcessed = this.filters.showProcessed;
            return this.rows.filter((row) => !hidden.has(row.author_id)
                && (showProcessed || row.is_processed !== true));
        },

        async load({ role = null, mode = 'draft', rootCommentId = null, entityType = null, entityId = null, userId = null, url = '' } = {}) {
            this.destroy();
            this.role = role;
            this.context = { mode, rootCommentId, entityType, entityId, userId, url };
            this.serverLists = [];

            const sameEntity = (detail = {}) => detail.entityType === entityType
                && String(detail.entityId) === String(entityId);
            const on = (name, handler) => {
                window.addEventListener(name, handler);
                listeners.push([name, handler]);
            };
            if (role === ROLE_COMMENTER) {
                DRAFT_EVENTS.forEach((name) => on(name, (event) => {
                    if (sameEntity(event.detail)) this.compose();
                }));
            }
            on('annotations:list-refreshed', (event) => {
                const detail = event.detail ?? {};
                if (!detail.list) return;
                this.replaceList(Number(detail.commentId), detail.list.items ?? []);
            });

            this.compose();

            const shouldFetch = role === ROLE_AUTHOR || (role === ROLE_COMMENTER && rootCommentId);
            if (!shouldFetch || !url) return;

            try {
                const query = new URLSearchParams({ entity_type: entityType, entity_id: String(entityId) });
                const res = await fetch(`${url}?${query}`, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.serverLists = (data?.lists ?? []).map((list) => ({
                    comment_id: Number(list.comment_id),
                    items: list.items ?? [],
                }));
                this.compose();
            } catch {
                // No in-chapter display on a failed read; the pop-up still works.
            }
        },

        destroy() {
            listeners.forEach(([name, handler]) => window.removeEventListener(name, handler));
            listeners = [];
        },

        replaceList(commentId, items) {
            const index = this.serverLists.findIndex((list) => list.comment_id === commentId);
            const next = { comment_id: commentId, items };
            if (index === -1) this.serverLists = [...this.serverLists, next];
            else this.serverLists = this.serverLists.map((list, i) => (i === index ? next : list));
            this.compose();
        },

        /** Rebuild `rows` from the server lists and the commenter's local state. */
        compose() {
            const server = this.serverLists.flatMap((list) => list.items.map((item) => ({
                ...item,
                comment_id: list.comment_id,
                source: 'server',
            })));
            if (this.role !== ROLE_COMMENTER) {
                this.rows = server;
                return;
            }

            const { mode, rootCommentId, entityType, entityId, userId } = this.context;
            const drafts = window.commentDrafts;
            const local = (item, source) => ({
                tempId: item.tempId,
                comment_id: source === 'draft' ? null : rootCommentId,
                author_id: userId,
                body: item.body,
                highlighted_text: item.highlighted ?? '',
                prefix: item.prefix ?? '',
                suffix: item.suffix ?? '',
                is_processed: null,
                source,
            });

            if (mode !== MODE_PENDING) {
                const list = drafts?.listAnnotations(userId, entityType, entityId) ?? [];
                this.rows = [...server, ...list.map((item) => local(item, 'draft'))];
                return;
            }

            const { adds, edits, deletes } = drafts?.getAnnotationChanges(userId, entityType, entityId) ?? emptyChanges();
            const saved = server
                .filter((row) => !deletes.includes(row.id))
                .map((row) => (Object.prototype.hasOwnProperty.call(edits, row.id)
                    ? { ...row, body: edits[row.id] }
                    : row));
            this.rows = [...saved, ...adds.map((item) => local(item, 'pending-add'))];
        },
    };
}
