import { createQuotesStore } from './stores/quotes-store.js';
import { quoteMiniForm } from './ui/mini-form.js';

document.addEventListener('alpine:init', () => {
    Alpine.store('quotes', createQuotesStore());
    Alpine.data('quoteMiniForm', quoteMiniForm);
});
