import { createQuotesStore } from './stores/quotes-store.js';
import { createAggregateStore } from './stores/aggregate-store.js';
import { quoteMiniForm } from './ui/mini-form.js';
import { quoteHighlighter } from './ui/chapter-highlights.js';
import { quoteAuthorHeat, quoteAuthorPassagePanel } from './ui/author-heat.js';
import { quotePanel } from './ui/panel.js';
import { quoteList } from './profile/quote-list.js';

document.addEventListener('alpine:init', () => {
    Alpine.store('quotes', createQuotesStore());
    Alpine.store('quoteAggregate', createAggregateStore());
    Alpine.data('quoteMiniForm', quoteMiniForm);
    Alpine.data('quoteHighlighter', quoteHighlighter);
    Alpine.data('quoteAuthorHeat', quoteAuthorHeat);
    Alpine.data('quoteAuthorPassagePanel', quoteAuthorPassagePanel);
    Alpine.data('quotePanel', quotePanel);
    Alpine.data('quoteList', quoteList);
});
