// In-chapter annotation overlay: Alpine root wrapping the chapter article.
// Inert for now — it only reads the context its Blade component renders.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('annotationOverlay', () => ({
        role: null,
        mode: 'draft',
        rootCommentId: null,
        entityType: '',
        entityId: null,
        userId: null,
        url: '',

        init() {
            const d = this.$el.dataset;
            this.role = d.role || null;
            this.mode = d.mode || 'draft';
            this.rootCommentId = d.rootCommentId ? Number(d.rootCommentId) : null;
            this.entityType = d.entityType || '';
            this.entityId = d.entityId ? Number(d.entityId) : null;
            this.userId = d.userId ? Number(d.userId) : null;
            this.url = d.url || '';
        },
    }));
});
