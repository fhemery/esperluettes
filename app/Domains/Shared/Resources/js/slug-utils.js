function makeSlug(text) {
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

export function registerSlugForm(Alpine) {
    Alpine.data('slugForm', (initialName, initialSlug, isEdit) => ({
        name: initialName,
        slug: initialSlug,
        slugManuallyEdited: isEdit,
        generateSlug() {
            if (!this.slugManuallyEdited) {
                this.slug = makeSlug(this.name);
            }
        },
    }));
}
