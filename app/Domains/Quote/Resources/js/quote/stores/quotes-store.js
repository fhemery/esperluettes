import { getQuotesForChapter } from '../api/client.js';

export function createQuotesStore() {
    return {
        items: [],
        canQuote: false,
        loaded: false,

        async load(chapterId, storyId) {
            try {
                const data = await getQuotesForChapter(chapterId, storyId);
                this.items = data.items ?? [];
                this.canQuote = data.can_quote ?? false;
                this.loaded = true;
            } catch {
                this.loaded = true;
            }
        },

        add(quote) {
            this.items.unshift(quote);
        },

        update(quote) {
            const idx = this.items.findIndex(q => q.id === quote.id);
            if (idx !== -1) this.items[idx] = quote;
        },

        remove(quoteId) {
            this.items = this.items.filter(q => q.id !== quoteId);
        },
    };
}
