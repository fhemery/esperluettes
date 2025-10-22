<?php

return [
    'navigation' => [
        'group' => 'FAQ',
        'categories' => 'Catégories',
        'questions' => 'Questions',
    ],

    'categories' => [
        'resource' => [
            'label' => 'Catégorie de FAQ',
            'plural' => 'Catégories de FAQ',
        ],
        'sections' => [
            'details' => 'Détails de la catégorie',
        ],
        'fields' => [
            'name' => 'Nom',
            'slug' => 'Slug',
            'description' => 'Description',
            'is_active' => 'Active',
            'questions' => 'Questions',
            'sort_order' => 'Ordre',
            'created_at' => 'Créée le',
        ],
        'filters' => [
            'active' => [
                'label' => 'Active',
                'all' => 'Toutes les catégories',
                'true' => 'Actives uniquement',
                'false' => 'Inactives uniquement',
            ],
        ],
    ],

    'questions' => [
        'resource' => [
            'label' => 'Question de FAQ',
            'plural' => 'Questions de FAQ',
        ],
        'sections' => [
            'details' => 'Détails de la question',
            'media' => 'Média',
        ],
        'fields' => [
            'category' => 'Catégorie',
            'question' => 'Question',
            'slug' => 'Slug',
            'answer' => 'Réponse',
            'image' => 'Image',
            'remove_image' => "Supprimer l'image actuelle",
            'image_alt_text' => "Texte alternatif de l'image",
            'has_image' => 'Image',
            'is_active' => 'Active',
            'sort_order' => 'Ordre',
            'created_at' => 'Créée le',
        ],
        'help' => [
            'image' => 'Téléversez une image optionnelle pour accompagner cette question',
        ],
        'filters' => [
            'active' => [
                'label' => 'Active',
                'all' => 'Toutes les questions',
                'true' => 'Actives uniquement',
                'false' => 'Inactives uniquement',
            ],
            'category' => [
                'label' => 'Catégorie',
            ],
        ],
        'bulk' => [
            'activate_selected' => 'Activer la sélection',
            'deactivate_selected' => 'Désactiver la sélection',
        ],
    ],
];
