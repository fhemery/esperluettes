/** `<x-shared::modal>` name of the drafts pop-up. */
export const MODAL_NAME = 'annotation-drafts';

/**
 * Banner + drafts-mode pop-up above the root-comment form. Mirrors the
 * comment-draft `annotations` slot and, when the enclosing form submits,
 * serialises the slot into its hidden `annotations` input. Clearing the slot
 * after a successful post is the consumed marker's job (comment-draft module).
 *
 * Context is read once in init() and kept in this closure: methods are also
 * called from inside the nested <x-shared::modal> scope, where Alpine's
 * `$root` / `$el` would point at the modal, not at this component.
 */
export function annotationDrafts() {
    let context = null;
    let input = null;
    let form = null;
    let labels = { one: '', many: '' };
    let onChanged = null;
    let onSubmit = null;

    const list = () => window.commentDrafts
        ?.listAnnotations(context.userId, context.entityType, context.entityId) ?? [];

    return {
        drafts: [],

        get count() {
            return this.drafts.length;
        },

        get visible() {
            return this.drafts.length > 0;
        },

        get label() {
            return this.count === 1 ? labels.one : labels.many.replace('__COUNT__', String(this.count));
        },

        init() {
            const root = this.$root;
            const data = root.dataset;
            context = {
                userId: data.userId ? parseInt(data.userId, 10) : null,
                entityType: data.entityType,
                entityId: data.entityId,
            };
            labels = { one: data.labelOne ?? '', many: data.labelMany ?? '' };
            input = root.querySelector('input[name="annotations"]');
            form = root.closest('form');

            this.refresh();
            // This bundle loads in <head>; the draft module is a body-end script,
            // so on a fresh page window.commentDrafts may not exist yet.
            if (!window.commentDrafts) {
                document.addEventListener('DOMContentLoaded', () => this.refresh(), { once: true });
            }

            onChanged = (event) => {
                const detail = event.detail ?? {};
                if (detail.entityType !== context.entityType) return;
                if (String(detail.entityId) !== String(context.entityId)) return;
                this.refresh();
            };
            window.addEventListener('comment-drafts:annotations-changed', onChanged);

            onSubmit = () => this.serialise();
            form?.addEventListener('submit', onSubmit);
        },

        destroy() {
            if (onChanged) window.removeEventListener('comment-drafts:annotations-changed', onChanged);
            if (onSubmit) form?.removeEventListener('submit', onSubmit);
        },

        refresh() {
            this.drafts = list();
        },

        serialise() {
            if (!input) return;
            const items = list().map(({ body, highlighted, prefix, suffix }) => ({
                body,
                highlighted_text: highlighted,
                prefix,
                suffix,
            }));
            input.value = items.length > 0 ? JSON.stringify(items) : '';
        },

        openModal() {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: MODAL_NAME }));
        },

        remove(tempId) {
            window.commentDrafts?.removeAnnotation(context.userId, context.entityType, context.entityId, tempId);
            this.refresh();
        },

        edit(tempId) {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: MODAL_NAME }));
            // Opened after this click has finished bubbling: the capture form's
            // @click.outside would otherwise close it on the click that opened it.
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('annotation:open-edit', { detail: { tempId } }));
            }, 0);
        },
    };
}
