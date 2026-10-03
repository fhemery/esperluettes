import { annotationForm } from './capture-form.js';
import { annotationDrafts } from './drafts.js';
import { annotationsModal } from './modal.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('annotationForm', annotationForm);
    Alpine.data('annotationDrafts', annotationDrafts);
    Alpine.data('annotationsModal', annotationsModal);
});
