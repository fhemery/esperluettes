import { createQuotesStore } from './stores/quotes-store.js';
import { quoteMiniForm } from './ui/mini-form.js';
import { quoteHighlighter } from './ui/chapter-highlights.js';
import { quotePanel } from './ui/panel.js';

document.addEventListener('alpine:init', () => {
    Alpine.store('quotes', createQuotesStore());
    Alpine.data('quoteMiniForm', quoteMiniForm);
    Alpine.data('quoteHighlighter', quoteHighlighter);
    Alpine.data('quotePanel', quotePanel);
});
