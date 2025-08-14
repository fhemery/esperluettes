<?php

return [
    'navigation' => [
        'group' => 'Pages',
        'pages' => 'Pages statiques',
    ],

    'resource' => [
        'label' => 'Page statique',
        'plural' => 'Pages statiques',
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
    ],

    'status' => [
        'draft' => 'Brouillon',
        'published' => 'Publié',
    ],

    'actions' => [
        'publish' => 'Publier',
        'unpublish' => 'Dépublier',
        'remove_header_image' => 'Supprimer l’image',
    ],

    'help' => [
        'header_image' => 'Téléchargez une image; des variantes responsives seront générées à l’enregistrement.',
    ],
];
