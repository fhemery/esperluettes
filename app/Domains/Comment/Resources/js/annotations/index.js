import { annotationForm } from './capture-form.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('annotationForm', annotationForm);
});
