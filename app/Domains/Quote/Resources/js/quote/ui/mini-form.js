import { buildCanonicalText } from '../../../../../Shared/Resources/js/anchoring/canonical-text.js';
import { extractAnchor } from '../../../../../Shared/Resources/js/anchoring/extract-anchor.js';
import { createQuote } from '../api/client.js';

export function quoteMiniForm() {
    return {
        open: false,
        saving: false,
        error: null,
        note: '',
        selectedText: '',
        _anchor: null,
        _chapterId: null,
        _storyId: null,
        _range: null,

        openForm({ chapterId, storyId }) {
            const selection = window.getSelection();
            if (!selection || selection.isCollapsed || selection.rangeCount === 0) return;

            const range = selection.getRangeAt(0);
            const region = range.commonAncestorContainer.nodeType === 3
                ? range.commonAncestorContainer.parentElement?.closest('.annotable-region')
                : range.commonAncestorContainer?.closest?.('.annotable-region');

            if (!region) return;

            const { text: canonicalText, nodeMap } = buildCanonicalText(region);
            const anchor = extractAnchor(range, region, { text: canonicalText, nodeMap });

            if (!anchor) return;

            this._anchor = anchor;
            this._chapterId = chapterId;
            this._storyId = storyId;
            this._range = range.cloneRange();
            this.selectedText = anchor.highlighted;
            this.note = '';
            this.error = null;
            this.open = true;
        },

        cancel() {
            this.open = false;
            this._anchor = null;
        },

        async save() {
            if (!this._anchor || this.saving) return;

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
                this.error = document.documentElement.lang === 'fr'
                    ? 'Impossible de sauvegarder la citation. Veuillez réessayer.'
                    : 'Unable to save the quote. Please try again.';
            } finally {
                this.saving = false;
            }
        },
    };
}
