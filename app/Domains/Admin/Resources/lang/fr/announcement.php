<?php

return [
    'navigation' => [
        'group' => 'Annonces',
        'announcements' => 'Liste des Annonces',
        'pinned_order' => 'Gestion de la page d\'accueil',
    ],

    'resource' => [
        'label' => 'Annonce',
        'plural' => 'Annonces',
        'pinned_order_label' => 'Ordre des annonces en page d\'accueil',
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
        'display_order' => 'Utilisé uniquement lorsque l’annonce est épinglée en page d\'accueil.',
    ],

    'filters' => [
        'all' => 'Tous',
        'not_pinned' => 'Pas en page d\'accueil',
    ],
];
