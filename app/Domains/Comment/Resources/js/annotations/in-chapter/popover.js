import { setProcessed, postReply } from '../api.js';
import { appendReply } from '../replies.js';
import { plainText } from '../capture-form.js';
import { rowKey } from './gutter.js';
import { annotatorName } from './annotator.js';
import { formatDate } from '../../../../../Shared/Resources/js/date-utils.js';

/** Our id in `Alpine.store('popover').openId`, which keeps popovers exclusive. */
export const POPOVER_ID = 'annotation-overlay-popover';

/** The popover's own reply editor — not the « N annotations » pop-up's `annotation-reply-editor`. */
export const REPLY_EDITOR_ID = 'annotation-overlay-reply-editor';

const ROLE_AUTHOR = 'author';
const ROLE_COMMENTER = 'commenter';
const SOURCE_SERVER = 'server';
const SOURCE_DRAFT = 'draft';
const SOURCE_PENDING_ADD = 'pending-add';
const PANEL_SELECTOR = '[data-annotation-popover-panel]';
const AVATAR_SELECTOR = '[data-annotation-group]';
const MARGIN = 8;
const PANEL_MAX_WIDTH = 320;

const panelEl = () => document.querySelector(PANEL_SELECTOR);
const replyTextarea = () => document.getElementById(`quill-editor-area-${REPLY_EDITOR_ID}`);

/**
 * Fixed position of a panel next to `trigger`, as `<x-shared::popover>` does
 * it: the first of right / left / bottom / top that fits the viewport, else
 * the last tried, then clamped inside the viewport.
 */
export function placePanel(trigger, width, height, viewportWidth, viewportHeight) {
    const candidates = {
        right: { top: trigger.top, left: trigger.right + MARGIN },
        left: { top: trigger.top, left: trigger.left - width - MARGIN },
        bottom: { top: trigger.bottom + MARGIN, left: trigger.left + trigger.width / 2 - width / 2 },
        top: { top: trigger.top - height - MARGIN, left: trigger.left + trigger.width / 2 - width / 2 },
    };
    const fits = (p) => p.left >= MARGIN && p.left + width + MARGIN <= viewportWidth
        && p.top >= MARGIN && p.top + height + MARGIN <= viewportHeight;
    const order = ['right', 'left', 'bottom', 'top'];
    const pos = { ...(candidates[order.find((place) => fits(candidates[place]))] ?? candidates.top) };
    const clamp = (min, value, max) => Math.min(Math.max(min, value), max);
    pos.left = clamp(MARGIN, pos.left, viewportWidth - width - MARGIN);
    pos.top = clamp(MARGIN, pos.top, viewportHeight - height - MARGIN);
    return pos;
}

/**
 * The in-chapter annotation popover, `Alpine.data('annotationPopover')`, nested
 * in the overlay root; its panel is teleported to `body`.
 *
 * Opens on `annotation-overlay:open { groupKey, keys }` (a gutter avatar) on
 * the group's rows that are still visible in `Alpine.store('annotationOverlay')`,
 * « i / N » with arrows. Exclusive with every other popover through
 * `Alpine.store('popover').openId`. Escape and a click outside close it and
 * give the focus back to the avatar. `sync()` (the panel's x-effect) closes it
 * when the heat map turns on or no member is left, and moves on when the
 * current row is hidden.
 *
 * Actions follow the row's `can_*` flags: processed toggle, « Répondre ».
 * The commenter's own rows get « Modifier » / « Supprimer » by `source`, as the
 * drafts modal (draft) and the « N annotations » pop-up (saved row, pending
 * add) do: the capture form opens by event, deletes go through
 * `window.commentDrafts`, whose change event recomposes the overlay store.
 * After a server mutation nothing is refetched: the updated list is dispatched
 * as `annotations:list-refreshed { commentId, list }`, which both the overlay
 * store and the « N annotations » pop-up cache apply.
 *
 * Context lives in this closure, read once in init(): methods are called from
 * inside `<x-shared::button>` (its own x-data).
 */
export function annotationPopover() {
    let messages = { position: '', action: '', replyEmpty: '', replyTooLong: '', deleteWithReplies: '' };
    let defaultAvatar = '';
    let unknownName = '';
    let ownProfile = { name: '', avatar: '' };
    let replyMax = 0;
    let anchorEl = null;
    let listeners = [];

    const resetReplyBody = () => {
        window.initQuillEditor?.(REPLY_EDITOR_ID);
        const textarea = replyTextarea();
        if (!textarea) return;
        textarea.value = '';
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    };

    return {
        id: POPOVER_ID,
        open: false,
        /** Row keys of the opened group, in reading order. */
        keys: [],
        currentKey: null,
        styleObj: {},
        error: '',
        busy: false,
        replying: false,
        replyValid: false,
        replyError: '',
        sending: false,
        hintFor: null,

        init() {
            const data = this.$root.dataset;
            messages = {
                position: data.position ?? '',
                action: data.actionError ?? '',
                replyEmpty: data.replyEmpty ?? '',
                replyTooLong: data.replyTooLong ?? '',
                deleteWithReplies: data.deleteWithReplies ?? '',
            };
            defaultAvatar = data.defaultAvatar ?? '';
            unknownName = data.unknownName ?? '';
            ownProfile = { name: data.userName ?? '', avatar: data.userAvatar ?? '' };
            replyMax = Number(data.replyMaxLength ?? 0);

            const on = (target, name, handler, options) => {
                target.addEventListener(name, handler, options);
                listeners.push([target, name, handler, options]);
            };
            on(window, 'annotation-overlay:open', (event) => this.show(event.detail ?? {}, event.target));
            on(window, 'keydown', (event) => {
                if (event.key === 'Escape' && this.open) this.close(true);
            });
            on(document, 'click', (event) => {
                if (!this.open) return;
                const target = event.target;
                if (panelEl()?.contains(target)) return;
                // Another avatar opens its own group instead.
                if (target?.closest?.(AVATAR_SELECTOR)) return;
                // Quill's link tooltip and the like may leave the DOM on the same click.
                if (target?.isConnected === false) return;
                this.close(true);
            });
            on(window, 'editor-valid', (event) => {
                if (event.detail?.id === REPLY_EDITOR_ID) this.replyValid = !!event.detail.valid;
            });
            const reposition = () => { if (this.open) this.place(); };
            on(window, 'resize', reposition);
            on(window, 'scroll', reposition, true);

            this.$watch('$store.popover.openId', (openId) => {
                if (this.open && openId !== this.id) this.close(false);
            });
        },

        destroy() {
            listeners.forEach(([target, name, handler, options]) => target.removeEventListener(name, handler, options));
            listeners = [];
        },

        get overlay() {
            return this.$store.annotationOverlay;
        },

        /** The opened group's rows still visible in the chapter. */
        get members() {
            if (!this.open) return [];
            const byKey = new Map(this.overlay.visibleRows.map((row) => [rowKey(row), row]));
            return this.keys.map((key) => byKey.get(key)).filter(Boolean);
        },

        get index() {
            return this.members.findIndex((row) => rowKey(row) === this.currentKey);
        },

        get current() {
            return this.members[this.index] ?? null;
        },

        get position() {
            return messages.position
                .replace('{index}', String(this.index + 1))
                .replace('{total}', String(this.members.length));
        },

        show({ groupKey, keys }, trigger) {
            this.keys = Array.isArray(keys) && keys.length > 0 ? keys.slice() : [groupKey];
            this.open = true;
            this.currentKey = this.members[0] ? rowKey(this.members[0]) : null;
            if (!this.currentKey) {
                this.open = false;
                return;
            }
            anchorEl = trigger instanceof Element ? trigger : null;
            this.error = '';
            this.hintFor = null;
            this.cancelReply();
            this.$store.popover.openId = this.id;
            this.$nextTick(() => {
                this.place();
                panelEl()?.focus({ preventScroll: true });
            });
        },

        close(returnFocus = false) {
            if (!this.open) return;
            this.open = false;
            this.cancelReply();
            this.error = '';
            if (this.$store.popover.openId === this.id) this.$store.popover.openId = null;
            if (returnFocus) this.avatar()?.focus();
        },

        /** The avatar that opened us, or — re-rendered since — the current one of the group. */
        avatar() {
            if (anchorEl?.isConnected) return anchorEl;
            for (const key of this.keys) {
                const button = document.querySelector(`${AVATAR_SELECTOR}[data-annotation-group="${key}"]`);
                if (button) return button;
            }
            return null;
        },

        place() {
            const trigger = this.avatar();
            if (!trigger) return;
            const panel = panelEl();
            const rect = panel?.getBoundingClientRect();
            const width = rect?.width || Math.min(PANEL_MAX_WIDTH, window.innerWidth - MARGIN * 2);
            const pos = placePanel(trigger.getBoundingClientRect(), width, rect?.height || 0, window.innerWidth, window.innerHeight);
            this.styleObj = {
                top: `${pos.top}px`,
                left: `${pos.left}px`,
                maxWidth: `min(${PANEL_MAX_WIDTH}px, calc(100vw - ${MARGIN * 2}px))`,
                maxHeight: `calc(100vh - ${MARGIN * 2}px)`,
                overflowY: 'auto',
            };
        },

        /** Heat on or no member left: closed. Current row hidden: the next visible member of the group. */
        sync() {
            if (!this.open) return;
            const members = this.members;
            if (this.overlay.heatOn || members.length === 0) {
                this.close(false);
                return;
            }
            if (this.index !== -1) return;
            const visible = new Set(members.map(rowKey));
            const from = this.keys.indexOf(this.currentKey);
            for (let step = 1; step <= this.keys.length; step++) {
                const key = this.keys[(from + step) % this.keys.length];
                if (visible.has(key)) {
                    this.currentKey = key;
                    break;
                }
            }
        },

        go(offset) {
            const members = this.members;
            if (members.length === 0) return;
            const next = (this.index + offset + members.length) % members.length;
            this.currentKey = rowKey(members[next]);
            this.error = '';
            this.cancelReply();
        },

        next() {
            this.go(1);
        },

        previous() {
            this.go(-1);
        },

        /** Server rows carry their profile; the commenter's local rows are theirs. */
        authorName(row) {
            if (row?.source && row.source !== 'server') return ownProfile.name;
            return annotatorName(row?.author_profile, unknownName);
        },

        authorAvatar(row) {
            if (row?.source && row.source !== 'server') return ownProfile.avatar || defaultAvatar;
            return row?.author_profile?.avatar_url || defaultAvatar;
        },

        replyAuthor(reply) {
            return annotatorName(reply.author_profile, unknownName);
        },

        replyAvatar(reply) {
            return reply.author_profile?.avatar_url || defaultAvatar;
        },

        replyDate(reply) {
            return reply.created_at ? formatDate(reply.created_at) : '';
        },

        canToggle(row) {
            return !!row?.can_mark_as_processed;
        },

        canReply(row) {
            return !!row?.can_reply;
        },

        /** Commenter: local rows, or a saved row the server lets them edit — never an emptied one. */
        canEdit(row) {
            if (this.overlay.role !== ROLE_COMMENTER || !row || row.is_emptied) return false;
            if (row.source === SOURCE_SERVER) return !!row.can_edit;
            return row.source === SOURCE_DRAFT || row.source === SOURCE_PENDING_ADD;
        },

        /** Commenter: as « Modifier », plus an emptied saved row (decision #5). */
        canDelete(row) {
            if (this.overlay.role !== ROLE_COMMENTER || !row) return false;
            if (row.source === SOURCE_SERVER) return !!row.can_edit || !!row.is_emptied;
            return row.source === SOURCE_DRAFT || row.source === SOURCE_PENDING_ADD;
        },

        /** Commenter: close, then open the capture form on the row (draft, pending add or saved row). */
        edit() {
            const row = this.current;
            if (!this.canEdit(row)) return;
            const event = row.source === SOURCE_SERVER
                ? new CustomEvent('annotations:edit-saved-row', {
                    detail: { id: row.id, body: row.body, highlighted: row.highlighted_text },
                })
                : new CustomEvent('annotation:open-edit', { detail: { tempId: row.tempId } });
            this.close(false);
            // Opened after this click has finished bubbling, as the drafts modal
            // does: the capture form's @click.outside would close it on this click.
            setTimeout(() => window.dispatchEvent(event), 0);
        },

        /** Commenter: drop the draft / pending add, or store a pending delete of a saved row. */
        remove() {
            const row = this.current;
            const drafts = window.commentDrafts;
            if (!this.canDelete(row) || !drafts) return;
            const { userId, entityType, entityId } = this.overlay.context;
            const args = [userId, entityType, entityId];
            if (row.source === SOURCE_DRAFT) {
                drafts.removeAnnotation(...args, row.tempId);
            } else if (row.source === SOURCE_PENDING_ADD) {
                drafts.removePendingAdd(...args, row.tempId);
            } else {
                if ((row.replies?.length ?? 0) > 0 && !window.confirm(messages.deleteWithReplies)) return;
                drafts.setPendingDelete(...args, row.id);
            }
            this.sync();
        },

        /** After an author's reply: replies notify nobody, the root comment does. */
        showsHint(row) {
            return this.overlay.role === ROLE_AUTHOR && row != null && this.hintFor === row.id;
        },

        /**
         * Replace server row `row` in its list through `transform`, by dispatching
         * the updated list — the overlay store and the pop-up cache both apply it.
         */
        updateRow(row, transform) {
            const commentId = Number(row.comment_id);
            const list = this.overlay.serverLists.find((l) => l.comment_id === commentId);
            if (!list) return;
            const items = transform(list.items);
            window.dispatchEvent(new CustomEvent('annotations:list-refreshed', {
                detail: { commentId, list: { comment_id: commentId, viewer_role: this.overlay.role, items } },
            }));
            this.sync();
        },

        async toggleProcessed() {
            const row = this.current;
            if (!this.canToggle(row) || this.busy) return;
            this.busy = true;
            this.error = '';
            try {
                const result = await setProcessed(row.id, !row.is_processed);
                const isProcessed = !!result.is_processed;
                this.updateRow(row, (items) => items.map((item) => (item.id === row.id ? { ...item, is_processed: isProcessed } : item)));
            } catch {
                this.error = messages.action;
            } finally {
                this.busy = false;
            }
        },

        startReply() {
            if (!this.canReply(this.current)) return;
            this.replying = true;
            this.replyError = '';
            this.replyValid = false;
            resetReplyBody();
            this.$nextTick(() => panelEl()?.querySelector('.ql-editor')?.focus());
        },

        cancelReply() {
            this.replying = false;
            this.replyError = '';
        },

        async sendReply() {
            const row = this.current;
            if (!this.replying || this.sending || !row) return;
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

            this.sending = true;
            this.replyError = '';
            try {
                const reply = await postReply(row.id, body);
                this.updateRow(row, (items) => appendReply(items, row.id, reply));
                this.hintFor = row.id;
                this.cancelReply();
            } catch {
                this.replyError = messages.action;
            } finally {
                this.sending = false;
            }
        },
    };
}
