import { updateQuoteNote, deleteQuote as deleteQuoteApi } from '../api/client.js';

/**
 * Profile "Citations" tab: renders the server-provided first page and supports
 * load-more pagination, plus owner-only inline note editing and deletion.
 */
export function quoteList(initial = {}) {
    return {
        items: initial.items ?? [],
        total: initial.total ?? 0,
        page: initial.page ?? 1,
        isOwn: initial.isOwn ?? false,
        slug: initial.slug ?? '',
        i18n: initial.i18n ?? {},
        loading: false,
        error: null,
        editingId: null,
        editValue: '',
        savingId: null,

        get hasMore() {
            return this.items.length < this.total;
        },

        formatDate(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (Number.isNaN(d.getTime())) return '';
            return d.toLocaleDateString(document.documentElement.lang || undefined, {
                year: 'numeric', month: 'short', day: 'numeric',
            });
        },

        async loadMore() {
            if (this.loading || !this.hasMore) return;
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(`/quotes/profile/${this.slug}?page=${this.page + 1}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.items.push(...(data.items ?? []));
                this.page = data.page ?? this.page + 1;
                this.total = data.total_count ?? this.total;
            } catch {
                this.error = this.i18n.load_more_error ?? '';
            } finally {
                this.loading = false;
            }
        },

        startEdit(item) {
            this.editingId = item.id;
            this.editValue = item.note ?? '';
            this.error = null;
        },

        cancelEdit() {
            this.editingId = null;
            this.editValue = '';
        },

        async saveEdit(item) {
            if (this.savingId) return;
            this.savingId = item.id;
            this.error = null;
            try {
                const updated = await updateQuoteNote(item.id, this.editValue.trim() || null);
                item.note = updated.note ?? null;
                this.editingId = null;
            } catch {
                this.error = this.i18n.save_note_error ?? '';
            } finally {
                this.savingId = null;
            }
        },

        async remove(item) {
            if (!window.confirm(this.i18n.delete_confirm ?? '')) return;
            this.error = null;
            try {
                await deleteQuoteApi(item.id);
                this.items = this.items.filter(q => q.id !== item.id);
                this.total = Math.max(0, this.total - 1);
            } catch {
                this.error = this.i18n.delete_quote_error ?? '';
            }
        },
    };
}
