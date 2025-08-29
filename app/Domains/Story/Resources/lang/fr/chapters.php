<?php

return [
    'created_success' => 'Chapitre créé avec succès.',

    'back_to_story' => 'Retour à l\'histoire',
    'author_note' => 'Note de l\'auteur',

    'create' => [
        'title' => 'Nouveau chapitre',
        'heading' => 'Ajouter un chapitre à ":story"',
    ],

    'form' => [
        'title' => [
            'label' => 'Titre',
            'placeholder' => 'Titre du chapitre',
        ],
        'author_note' => [
            'label' => 'Note de l\'auteur',
            'help' => 'Optionnel. 1000 caractères maximum (après suppression du HTML).',
            'note_limit' => 'Jusqu\'à 1000 caractères (après suppression du HTML).',
        ],
        'content' => [
            'label' => 'Contenu',
        ],
        'published' => [
            'label' => 'Publié',
            'help' => [
                'label' => 'Statut de publication',
                'text' => 'Décochez si vous souhaitez garder le chapitre en brouillon, ou le cacher temporairement.',
            ],
        ],
        'cancel' => 'Annuler',
        'submit' => 'Créer le chapitre',
    ],

    'sections' => [
        'chapters' => 'Chapitres',
        'add_chapter' => 'Ajouter un chapitre',
    ],

    'list' => [
        'empty' => 'Aucun chapitre disponible pour le moment.',
        'draft' => 'Brouillon',
    ],
];
