import { updateQuoteNote, deleteQuote as deleteQuoteApi } from '../api/client.js';

export function quotePanel() {
    return {
        open: false,
        quote: null,
        editingNote: false,
        noteValue: '',
        saving: false,
        deleting: false,
        error: null,

        showPanel(quote) {
            this.quote = { ...quote };
            this.noteValue = quote.note ?? '';
            this.editingNote = false;
            this.error = null;
            this.open = true;
        },

        startEdit() {
            this.editingNote = true;
        },

        cancelEdit() {
            this.noteValue = this.quote.note ?? '';
            this.editingNote = false;
        },

        async saveNote() {
            if (this.saving) return;
            this.saving = true;
            this.error = null;
            try {
                const updated = await updateQuoteNote(this.quote.id, this.noteValue.trim() || null);
                Alpine.store('quotes').update(updated);
                this.quote = { ...updated };
                this.editingNote = false;
            } catch {
                this.error = this.$el.dataset.errorSave;
            } finally {
                this.saving = false;
            }
        },

        async confirmDelete() {
            if (this.deleting) return;
            this.deleting = true;
            this.error = null;
            try {
                await deleteQuoteApi(this.quote.id);
                Alpine.store('quotes').remove(this.quote.id);
                this.open = false;
                this.quote = null;
            } catch {
                this.error = this.$el.dataset.errorDelete;
            } finally {
                this.deleting = false;
            }
        },

        close() {
            if (this.editingNote) {
                this.cancelEdit();
                return;
            }
            this.open = false;
        },
    };
}
