<?php

return [
    // Navigation
    'nav' => [
        'pages' => 'Pages statiques',
        'group' => 'Contenu',
    ],

    // Page titles
    'pages' => [
        'title' => 'Pages statiques',
        'create_title' => 'Nouvelle page',
        'edit_title' => 'Modifier la page',
        'create_button' => 'Nouvelle page',
        'empty' => 'Aucune page statique pour le moment.',
    ],

    // Table columns
    'table' => [
        'title' => 'Titre',
        'slug' => 'URL',
        'status' => 'Statut',
        'published_at' => 'Publié le',
        'actions' => 'Actions',
    ],

    // Statuses
    'status' => [
        'draft' => 'Brouillon',
        'published' => 'Publié',
    ],

    // Actions
    'actions' => [
        'view' => 'Voir',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'publish' => 'Publier',
        'unpublish' => 'Dépublier',
    ],

    // Confirmation dialogs
    'confirm' => [
        'delete' => 'Êtes-vous sûr de vouloir supprimer cette page ?',
        'publish' => 'Êtes-vous sûr de vouloir publier cette page ?',
        'unpublish' => 'Êtes-vous sûr de vouloir dépublier cette page ?',
    ],

    // Flash messages
    'messages' => [
        'created' => 'Page créée avec succès.',
        'updated' => 'Page mise à jour avec succès.',
        'deleted' => 'Page supprimée avec succès.',
        'published' => 'Page publiée avec succès.',
        'unpublished' => 'Page dépubliée avec succès.',
    ],

    // Form
    'form' => [
        'content_section' => 'Contenu',
        'media_section' => 'Média',
        'settings_section' => 'Paramètres',
        'title' => 'Titre',
        'slug' => 'Slug',
        'slug_help' => 'URL de la page (lettres minuscules, chiffres et tirets uniquement)',
        'summary' => 'Résumé',
        'content' => 'Contenu',
        'header_image' => 'Image d\'en-tête',
        'header_image_help' => 'Image optionnelle affichée en haut de la page.',
        'status' => 'Statut',
        'meta_description' => 'Meta description',
        'meta_description_help' => 'Description pour les moteurs de recherche (max 160 caractères)',
        'cancel' => 'Annuler',
        'create' => 'Créer la page',
        'update' => 'Mettre à jour',
    ],

    // Validation
    'validation' => [
        'title_required' => 'Le titre est obligatoire.',
        'slug_required' => 'Le slug est obligatoire.',
        'slug_format' => 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des tirets.',
        'slug_unique' => 'Ce slug est déjà utilisé.',
        'content_required' => 'Le contenu est obligatoire.',
    ],
];
