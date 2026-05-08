<?php

return [
    'categories' => [
        'title' => 'Catégories de FAQ',
        'nav_label' => 'Catégories',
        'create_button' => 'Nouvelle catégorie',

        'form' => [
            'name' => 'Nom',
            'slug' => 'Slug',
            'slug_help' => 'Identifiant URL unique (lettres minuscules, chiffres, tirets)',
            'description' => 'Description',
            'is_active' => 'Active',
            'create' => 'Créer la catégorie',
            'update' => 'Enregistrer',
            'cancel' => 'Annuler',
        ],

        'table' => [
            'order' => 'Ordre',
            'name' => 'Nom',
            'slug' => 'Slug',
            'description' => 'Description',
            'questions' => 'Questions',
            'active' => 'Active',
            'actions' => 'Actions',
        ],

        'created' => 'Catégorie créée avec succès.',
        'updated' => 'Catégorie mise à jour avec succès.',
        'deleted' => 'Catégorie supprimée avec succès.',
        'active_updated' => 'Statut de la catégorie mis à jour.',
        'confirm_delete' => 'Supprimer cette catégorie et toutes ses questions ?',
        'no_items' => 'Aucune catégorie.',
    ],

    'questions' => [
        'title' => 'Questions de FAQ',
        'nav_label' => 'Questions',
        'create_button' => 'Nouvelle question',

        'form' => [
            'category' => 'Catégorie',
            'question' => 'Question',
            'slug' => 'Slug',
            'slug_help' => 'Identifiant URL unique (lettres minuscules, chiffres, tirets)',
            'answer' => 'Réponse',
            'image' => 'Image',
            'image_alt_text' => "Texte alternatif de l'image",
            'is_active' => 'Active',
            'create' => 'Créer la question',
            'update' => 'Enregistrer',
            'cancel' => 'Annuler',
        ],

        'table' => [
            'category' => 'Catégorie',
            'question' => 'Question',
            'image' => 'Image',
            'active' => 'Active',
            'sort_order' => 'Ordre',
            'actions' => 'Actions',
        ],

        'filter_category' => 'Filtrer par catégorie',
        'all_categories' => 'Toutes les catégories',

        'created' => 'Question créée avec succès.',
        'updated' => 'Question mise à jour avec succès.',
        'deleted' => 'Question supprimée avec succès.',
        'active_updated' => 'Statut de la question mis à jour.',
        'confirm_delete' => 'Supprimer cette question ?',
        'no_items' => 'Aucune question.',
    ],
];
