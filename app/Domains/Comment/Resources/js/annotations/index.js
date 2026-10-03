import { annotationForm } from './capture-form.js';
import { annotationDrafts } from './drafts.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('annotationForm', annotationForm);
    Alpine.data('annotationDrafts', annotationDrafts);
});
