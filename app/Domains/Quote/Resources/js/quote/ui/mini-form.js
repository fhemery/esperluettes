import { buildCanonicalText } from '../../../../../Shared/Resources/js/anchoring/canonical-text.js';
import { extractAnchor } from '../../../../../Shared/Resources/js/anchoring/extract-anchor.js';
import { closestBlock } from '../../../../../Shared/Resources/js/anchoring/block-elements.js';
import { createQuote } from '../api/client.js';

export function quoteMiniForm() {
    return {
        open: false,
        saving: false,
        error: null,
        tooLong: false,
        multiBlock: false,
        note: '',
        selectedText: '',
        _anchor: null,
        _chapterId: null,
        _storyId: null,
        _pos: { top: 0, left: 0 },

        openForm({ chapterId, storyId }) {
            const selection = window.getSelection();
            if (!selection || selection.isCollapsed || selection.rangeCount === 0) return;

            const range = selection.getRangeAt(0);
            const region = range.commonAncestorContainer.nodeType === 3
                ? range.commonAncestorContainer.parentElement?.closest('.annotable-region')
                : range.commonAncestorContainer?.closest?.('.annotable-region');

            if (!region) return;

            // A quote must stay inside a single block: the canonical text inserts a
            // synthetic space at every block boundary, which maps to no text node and
            // would leave an untinted hole in the highlight.
            const spansSeveralBlocks = closestBlock(range.startContainer) !== closestBlock(range.endContainer);

            const { text: canonicalText, nodeMap } = buildCanonicalText(region);
            const anchor = extractAnchor(range, region, { text: canonicalText, nodeMap });

            if (!anchor) return;

            // Compute document-relative position just below the selection
            const rect = range.getBoundingClientRect();
            const formWidth = 360;
            const left = Math.min(
                Math.max(8, rect.left + rect.width / 2 - formWidth / 2),
                window.innerWidth - formWidth - 8
            );
            this._pos = {
                top: rect.bottom + window.scrollY + 8,
                left: left + window.scrollX,
            };

            // Clear selection so the toolbar hides
            selection.removeAllRanges();

            this._anchor = anchor;
            this._chapterId = chapterId;
            this._storyId = storyId;
            this.selectedText = anchor.highlighted;
            this.note = '';

            const maxLength = Number(this.$el.dataset.highlightMaxLength);
            this.tooLong = anchor.highlighted.length > maxLength;
            this.multiBlock = spansSeveralBlocks;
            this.error = this.tooLong
                ? this.$el.dataset.errorHighlightTooLong
                : (this.multiBlock ? this.$el.dataset.errorHighlightMultiBlock : null);
            this.open = true;
        },

        cancel() {
            this.open = false;
            this._anchor = null;
        },

        async save() {
            if (!this._anchor || this.saving || this.tooLong || this.multiBlock) return;

            this.saving = true;
            this.error = null;

            try {
                const quote = await createQuote({
                    chapterId: this._chapterId,
                    storyId: this._storyId,
                    highlightedText: this._anchor.highlighted,
                    prefix: this._anchor.prefix ?? null,
                    suffix: this._anchor.suffix ?? null,
                    note: this.note.trim() || null,
                });

                Alpine.store('quotes').add(quote);
                this.open = false;
                this._anchor = null;
            } catch {
                this.error = this.$el.dataset.errorSave;
            } finally {
                this.saving = false;
            }
        },
    };
}
