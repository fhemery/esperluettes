<?php

return [
    // Navigation
    'nav' => [
        'news' => 'Actualités',
        'pinned' => 'Carousel',
        'group' => 'Actualités',
    ],

    // News management
    'news' => [
        'title' => 'Gestion des actualités',
        'create_title' => 'Nouvelle actualité',
        'edit_title' => 'Modifier l\'actualité',
        'create_button' => 'Créer une actualité',
        'empty' => 'Aucune actualité pour le moment.',
    ],

    // Pinned news
    'pinned' => [
        'title' => 'Ordre du carousel',
        'empty' => 'Aucune actualité épinglée',
        'empty_help' => 'Épinglez des actualités depuis la liste principale pour les afficher dans le carousel.',
        'help' => 'Réorganisez l\'ordre d\'affichage des actualités dans le carousel. Glissez-déposez ou utilisez les flèches.',
    ],

    // Table columns
    'table' => [
        'title' => 'Titre',
        'status' => 'Statut',
        'pinned' => 'Épinglé',
        'published_at' => 'Publié le',
        'actions' => 'Actions',
    ],

    // Status labels
    'status' => [
        'draft' => 'Brouillon',
        'published' => 'Publié',
    ],

    // Filters
    'filters' => [
        'all_statuses' => 'Tous les statuts',
        'all_pinned' => 'Tous',
        'pinned_only' => 'Épinglés uniquement',
        'not_pinned' => 'Non épinglés',
    ],

    // Actions
    'actions' => [
        'view' => 'Voir',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'publish' => 'Publier',
        'unpublish' => 'Dépublier',
    ],

    // Confirm dialogs
    'confirm' => [
        'delete' => 'Êtes-vous sûr de vouloir supprimer cette actualité ?',
        'publish' => 'Êtes-vous sûr de vouloir publier cette actualité ?',
        'unpublish' => 'Êtes-vous sûr de vouloir dépublier cette actualité ?',
    ],

    // Messages
    'messages' => [
        'created' => 'L\'actualité a été créée avec succès.',
        'updated' => 'L\'actualité a été mise à jour avec succès.',
        'deleted' => 'L\'actualité a été supprimée.',
        'published' => 'L\'actualité a été publiée.',
        'unpublished' => 'L\'actualité a été dépubliée.',
    ],

    // Form fields
    'form' => [
        'title' => 'Titre',
        'slug' => 'Slug (URL)',
        'slug_help' => 'Utilisez uniquement des lettres minuscules, chiffres et tirets.',
        'summary' => 'Résumé',
        'summary_help' => 'Court résumé affiché dans les listes et le carousel (max 500 caractères).',
        'content' => 'Contenu',
        'header_image' => 'Image d\'en-tête',
        'header_image_help' => 'Cette image sera affichée en haut de l\'actualité et dans le carousel.',
        'status' => 'Statut',
        'is_pinned' => 'Épingler dans le carousel',
        'meta_description' => 'Meta description (SEO)',
        'meta_description_help' => 'Description pour les moteurs de recherche (max 160 caractères).',
        'create' => 'Créer',
        'update' => 'Enregistrer',
        'cancel' => 'Annuler',
    ],

    // Validation messages
    'validation' => [
        'title_required' => 'Le titre est obligatoire.',
        'title_max' => 'Le titre ne peut pas dépasser 200 caractères.',
        'slug_required' => 'Le slug est obligatoire.',
        'slug_format' => 'Le slug ne doit contenir que des lettres minuscules, chiffres et tirets.',
        'slug_unique' => 'Ce slug est déjà utilisé.',
        'summary_required' => 'Le résumé est obligatoire.',
        'summary_max' => 'Le résumé ne peut pas dépasser 500 caractères.',
        'content_required' => 'Le contenu est obligatoire.',
        'header_image_type' => 'Le fichier doit être une image.',
        'header_image_max' => 'L\'image ne doit pas dépasser 2 Mo.',
        'status_required' => 'Le statut est obligatoire.',
        'status_invalid' => 'Le statut sélectionné n\'est pas valide.',
        'meta_description_max' => 'La meta description ne peut pas dépasser 160 caractères.',
    ],
];
