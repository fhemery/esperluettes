import { annotationForm } from './capture-form.js';
import { annotationDrafts } from './drafts.js';
import { annotationsModal } from './modal.js';
import { annotationReactions } from './reactions.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('annotationReactions', annotationReactions);
    Alpine.data('annotationForm', annotationForm);
    Alpine.data('annotationDrafts', annotationDrafts);
    Alpine.data('annotationsModal', annotationsModal);
});
