<?php

return [
    'audiences' => [
        'nav_label' => 'Publics cibles',
        'title' => 'Gestion des publics cibles',
        'create_title' => 'Créer un public cible',
        'edit_title' => 'Modifier le public :name',

        'create_button' => 'Nouveau public',
        'edit_button' => 'Modifier',
        'delete_button' => 'Supprimer',
        'export_button' => 'Export CSV',

        'created' => 'Public cible créé avec succès.',
        'updated' => 'Public cible mis à jour avec succès.',
        'deleted' => 'Public cible supprimé avec succès.',
        'cannot_delete_in_use' => 'Impossible de supprimer ce public cible : il est utilisé par :count histoire(s).',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer ce public cible ?',
        'no_audiences' => 'Aucun public cible défini.',

        'table' => [
            'order' => 'Ordre',
            'name' => 'Nom',
            'slug' => 'Slug',
            'mature' => 'Contenu mature',
            'threshold' => 'Âge requis',
            'active' => 'Actif',
            'actions' => 'Actions',
            'created_at' => 'Créé le',
            'updated_at' => 'Mis à jour le',
        ],

        'mature_yes' => 'Mature',
        'active_yes' => 'Actif',
        'active_no' => 'Inactif',

        'form' => [
            'name' => 'Nom',
            'slug' => 'Slug (identifiant URL)',
            'slug_help' => 'Lettres minuscules, chiffres et tirets uniquement (ex: tout-public, adultes)',
            'order' => 'Ordre d\'affichage',
            'is_active' => 'Public actif (visible dans les formulaires)',

            'mature_section' => 'Protection des mineurs',
            'is_mature_audience' => 'Contenu réservé à un public mature',
            'is_mature_help' => 'Si activé, une vérification d\'âge sera affichée avant l\'accès aux chapitres.',
            'threshold_age' => 'Âge minimum requis',
            'threshold_help' => 'L\'utilisateur devra confirmer avoir cet âge ou plus.',
            'years_old' => 'ans',

            'create' => 'Créer le public',
            'update' => 'Mettre à jour',
            'cancel' => 'Annuler',
        ],

        'validation' => [
            'name_required' => 'Le nom est obligatoire.',
            'name_max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'slug_required' => 'Le slug est obligatoire.',
            'slug_format' => 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des tirets.',
            'slug_unique' => 'Ce slug est déjà utilisé par un autre public cible.',
            'order_required' => 'L\'ordre d\'affichage est obligatoire.',
            'order_integer' => 'L\'ordre doit être un nombre entier.',
            'threshold_required_when_mature' => 'L\'âge minimum est obligatoire pour un contenu mature.',
            'threshold_integer' => 'L\'âge doit être un nombre entier.',
            'threshold_min' => 'L\'âge minimum doit être d\'au moins 1 an.',
            'threshold_max' => 'L\'âge maximum ne peut pas dépasser 99 ans.',
        ],
    ],
];
