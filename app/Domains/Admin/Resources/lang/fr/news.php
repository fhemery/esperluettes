<?php

return [
    'navigation' => [
        'group' => 'Actualités',
        'news' => 'Liste des Actualités',
        'pinned_order' => 'Gestion de la page d\'accueil',
    ],

    'resource' => [
        'label' => 'Actualité',
        'plural' => 'Actualités',
        'pinned_order_label' => 'Ordre des actualités en page d\'accueil',
    ],

    'fields' => [
        'title' => 'Titre',
        'slug' => 'Identifiant',
        'summary' => 'Résumé',
        'content' => 'Contenu',
        'header_image' => 'Image d’en-tête',
        'status' => 'Statut',
        'published_at' => 'Publié le',
        'meta_description' => 'Méta description',
        'is_pinned' => 'En page d\'accueil',
        'display_order' => 'Ordre',
    ],

    'status' => [
        'draft' => 'Brouillon',
        'published' => 'Publié',
    ],

    'actions' => [
        'publish' => 'Publier',
        'unpublish' => 'Dépublier',
        'remove_header_image' => 'Supprimer l\'image',
    ],

    'help' => [
        'header_image' => 'Téléchargez une image; des variantes responsives seront générées à l’enregistrement.',
        'display_order' => 'Utilisé uniquement lorsque l’actualité est épinglée en page d\'accueil.',
    ],

    'filters' => [
        'all' => 'Tous',
        'not_pinned' => 'Pas en page d\'accueil',
    ],
];
