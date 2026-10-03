import { buildCanonicalText } from '../../../../Shared/Resources/js/anchoring/canonical-text.js';
import { extractAnchor } from '../../../../Shared/Resources/js/anchoring/extract-anchor.js';
import { closestBlock } from '../../../../Shared/Resources/js/anchoring/block-elements.js';
import { trimRangeToText } from '../../../../Shared/Resources/js/anchoring/text-range.js';

/**
 * The only annotatable areas inside a chapter: text blocks, like Quote. Also
 * written on `<x-comment::annotate-button>` (`data-requires-selection-within`)
 * — keep both in sync.
 */
export const ANNOTATABLE_AREA_SELECTOR = '.ce-block--text';

export const EDITOR_ID = 'annotation-body-editor';

const FORM_WIDTH = 360;

function editorTextarea() {
    return document.getElementById('quill-editor-area-' + EDITOR_ID);
}

/** Plain text of the editor's HTML, parsed inert (a <template> runs nothing). */
function plainText(html) {
    const template = document.createElement('template');
    template.innerHTML = html ?? '';
    return (template.content.textContent ?? '').replace(/ /g, ' ');
}

/**
 * Capture form for a chapter annotation draft. Reads the current selection,
 * anchors it like Quote does, and stores `{ body, highlighted, prefix, suffix }`
 * in the comment-draft `annotations` slot. Nothing is sent to the server here:
 * drafts are posted with the root comment.
 *
 * Configuration comes from data attributes on the component root
 * (`<x-comment::annotation-form>`), always read through `$root`: inside a
 * directive Alpine's `$el` is the element that fired it (e.g. the « Enregistrer »
 * button), not the component root.
 */
export function annotationForm() {
    return {
        open: false,
        centred: false,
        error: null,
        tooLong: false,
        multiBlock: false,
        highlighted: '',
        _anchor: null,
        _tempId: null,
        _pos: { top: 0, left: 0 },

        get canSave() {
            return !this.tooLong && !this.multiBlock;
        },

        get formStyle() {
            const width = `width:min(${FORM_WIDTH}px, calc(100vw - 16px)); z-index:9999;`;
            if (this.centred) {
                return `position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); ${width}`;
            }
            return `position:absolute; top:${this._pos.top}px; left:${this._pos.left}px; ${width}`;
        },

        _context() {
            const data = this.$root.dataset;
            return {
                userId: data.userId ? parseInt(data.userId, 10) : null,
                entityType: data.entityType,
                entityId: data.entityId,
            };
        },

        _setBody(html) {
            window.initQuillEditor?.(EDITOR_ID);
            const textarea = editorTextarea();
            if (!textarea) return;
            textarea.value = html;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        },

        _show() {
            this.open = true;
            this.$nextTick?.(() => this.$root.querySelector('.ql-editor')?.focus());
        },

        openForm() {
            const selection = window.getSelection();
            if (!selection || selection.isCollapsed || selection.rangeCount === 0) return;

            const range = selection.getRangeAt(0);
            const container = range.commonAncestorContainer;
            const articleEl = (container.nodeType === 3 ? container.parentElement : container)
                ?.closest?.('[data-quote-article]');
            if (!articleEl) return;

            // One editor block only (decision #1): several paragraphs inside a
            // block are fine; a boundary merely touching the next block is not a span.
            const covered = trimRangeToText(range);
            if (!covered) return;
            const spansSeveralBlocks = closestBlock(covered.startContainer) !== closestBlock(covered.endContainer);

            const maxLength = Number(this.$root.dataset.highlightMaxLength);
            const { text, nodeMap } = buildCanonicalText(articleEl, { within: ANNOTATABLE_AREA_SELECTOR });
            const anchor = extractAnchor(range, articleEl, { text, nodeMap });
            // extractAnchor gives up on long selections: still tell the reader why.
            const selectedText = anchor?.highlighted ?? selection.toString();
            const tooLong = maxLength > 0 && selectedText.length > maxLength;
            if (!anchor && !tooLong) return;

            const rect = range.getBoundingClientRect();
            const left = Math.min(
                Math.max(8, rect.left + rect.width / 2 - FORM_WIDTH / 2),
                window.innerWidth - FORM_WIDTH - 8,
            );
            this._pos = {
                top: rect.bottom + window.scrollY + 8,
                left: Math.max(8, left) + window.scrollX,
            };
            this.centred = false;

            // Clear the selection so the toolbar hides.
            selection.removeAllRanges();

            this._anchor = anchor;
            this._tempId = null;
            this.highlighted = selectedText;
            this.tooLong = tooLong;
            this.multiBlock = spansSeveralBlocks;
            this.error = this.tooLong
                ? this.$root.dataset.errorHighlightTooLong
                : (this.multiBlock ? this.$root.dataset.errorHighlightMultiBlock : null);
            this._setBody('');
            this._show();
        },

        openEdit({ tempId } = {}) {
            const { userId, entityType, entityId } = this._context();
            const draft = window.commentDrafts
                ?.listAnnotations(userId, entityType, entityId)
                .find((item) => item.tempId === tempId);
            if (!draft) return;

            this._anchor = { highlighted: draft.highlighted, prefix: draft.prefix, suffix: draft.suffix };
            this._tempId = draft.tempId;
            this.highlighted = draft.highlighted;
            this.tooLong = false;
            this.multiBlock = false;
            this.error = null;
            this.centred = true;
            this._setBody(draft.body);
            this._show();
        },

        onKeydown(event) {
            if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
                event.preventDefault();
                this.save();
            }
        },

        cancel() {
            this.open = false;
            this._anchor = null;
            this._tempId = null;
        },

        save() {
            if (!this.open || !this._anchor || !this.canSave) return;

            const body = editorTextarea()?.value ?? '';
            const length = plainText(body).trim().length;
            if (length === 0) {
                this.error = this.$root.dataset.errorBlank;
                return;
            }
            const bodyMax = Number(this.$root.dataset.bodyMaxLength);
            if (bodyMax > 0 && length > bodyMax) {
                this.error = this.$root.dataset.errorBodyTooLong;
                return;
            }

            const { userId, entityType, entityId } = this._context();
            if (this._tempId) {
                window.commentDrafts?.updateAnnotation(userId, entityType, entityId, this._tempId, body);
            } else {
                window.commentDrafts?.addAnnotation(userId, entityType, entityId, {
                    body,
                    highlighted: this._anchor.highlighted,
                    prefix: this._anchor.prefix ?? '',
                    suffix: this._anchor.suffix ?? '',
                });
            }
            this.cancel();
        },
    };
}
