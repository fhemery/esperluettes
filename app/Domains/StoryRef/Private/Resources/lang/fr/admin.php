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

    'copyrights' => [
        'nav_label' => 'Droits d\'auteur',
        'title' => 'Gestion des droits d\'auteur',
        'create_title' => 'Créer un droit d\'auteur',
        'edit_title' => 'Modifier le droit :name',

        'create_button' => 'Nouveau droit',
        'edit_button' => 'Modifier',
        'delete_button' => 'Supprimer',
        'export_button' => 'Export CSV',

        'created' => 'Droit d\'auteur créé avec succès.',
        'updated' => 'Droit d\'auteur mis à jour avec succès.',
        'deleted' => 'Droit d\'auteur supprimé avec succès.',
        'cannot_delete_in_use' => 'Impossible de supprimer ce droit d\'auteur : il est utilisé par :count histoire(s).',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer ce droit d\'auteur ?',
        'no_copyrights' => 'Aucun droit d\'auteur défini.',

        'table' => [
            'order' => 'Ordre',
            'name' => 'Nom',
            'slug' => 'Slug',
            'description' => 'Description',
            'active' => 'Actif',
            'actions' => 'Actions',
            'created_at' => 'Créé le',
            'updated_at' => 'Mis à jour le',
        ],

        'active_yes' => 'Actif',
        'active_no' => 'Inactif',

        'form' => [
            'name' => 'Nom',
            'slug' => 'Slug (identifiant URL)',
            'slug_help' => 'Lettres minuscules, chiffres et tirets uniquement (ex: tous-droits-reserves, creative-commons)',
            'description' => 'Description',
            'is_active' => 'Droit actif (visible dans les formulaires)',

            'create' => 'Créer le droit',
            'update' => 'Mettre à jour',
            'cancel' => 'Annuler',
        ],

        'validation' => [
            'name_required' => 'Le nom est obligatoire.',
            'name_max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'slug_required' => 'Le slug est obligatoire.',
            'slug_format' => 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des tirets.',
            'slug_unique' => 'Ce slug est déjà utilisé par un autre droit d\'auteur.',
            'order_required' => 'L\'ordre d\'affichage est obligatoire.',
            'order_integer' => 'L\'ordre doit être un nombre entier.',
            'description_max' => 'La description ne peut pas dépasser 1000 caractères.',
        ],
    ],

    'feedbacks' => [
        'nav_label' => 'Retours attendus',
        'title' => 'Gestion des retours attendus',
        'create_title' => 'Créer un type de retour',
        'edit_title' => 'Modifier le retour :name',

        'create_button' => 'Nouveau type',
        'edit_button' => 'Modifier',
        'delete_button' => 'Supprimer',
        'export_button' => 'Export CSV',

        'created' => 'Type de retour créé avec succès.',
        'updated' => 'Type de retour mis à jour avec succès.',
        'deleted' => 'Type de retour supprimé avec succès.',
        'cannot_delete_in_use' => 'Impossible de supprimer ce type de retour : il est utilisé par :count histoire(s).',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer ce type de retour ?',
        'no_feedbacks' => 'Aucun type de retour défini.',

        'table' => [
            'order' => 'Ordre',
            'name' => 'Nom',
            'slug' => 'Slug',
            'description' => 'Description',
            'active' => 'Actif',
            'actions' => 'Actions',
            'created_at' => 'Créé le',
            'updated_at' => 'Mis à jour le',
        ],

        'active_yes' => 'Actif',
        'active_no' => 'Inactif',

        'form' => [
            'name' => 'Nom',
            'slug' => 'Slug (identifiant URL)',
            'slug_help' => 'Lettres minuscules, chiffres et tirets uniquement (ex: commentaires, corrections)',
            'description' => 'Description',
            'is_active' => 'Type actif (visible dans les formulaires)',

            'create' => 'Créer le type',
            'update' => 'Mettre à jour',
            'cancel' => 'Annuler',
        ],

        'validation' => [
            'name_required' => 'Le nom est obligatoire.',
            'name_max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'slug_required' => 'Le slug est obligatoire.',
            'slug_format' => 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des tirets.',
            'slug_unique' => 'Ce slug est déjà utilisé par un autre type de retour.',
            'order_required' => 'L\'ordre d\'affichage est obligatoire.',
            'order_integer' => 'L\'ordre doit être un nombre entier.',
            'description_max' => 'La description ne peut pas dépasser 1000 caractères.',
        ],
    ],

    'genres' => [
        'nav_label' => 'Genres',
        'title' => 'Gestion des genres',
        'create_title' => 'Créer un genre',
        'edit_title' => 'Modifier le genre :name',

        'create_button' => 'Nouveau genre',
        'edit_button' => 'Modifier',
        'delete_button' => 'Supprimer',
        'export_button' => 'Export CSV',

        'created' => 'Genre créé avec succès.',
        'updated' => 'Genre mis à jour avec succès.',
        'deleted' => 'Genre supprimé avec succès.',
        'cannot_delete_in_use' => 'Impossible de supprimer ce genre : il est utilisé par :count histoire(s).',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer ce genre ?',
        'no_genres' => 'Aucun genre défini.',

        'table' => [
            'order' => 'Ordre',
            'name' => 'Nom',
            'slug' => 'Slug',
            'description' => 'Description',
            'active' => 'Actif',
            'actions' => 'Actions',
            'created_at' => 'Créé le',
            'updated_at' => 'Mis à jour le',
        ],

        'active_yes' => 'Actif',
        'active_no' => 'Inactif',

        'form' => [
            'name' => 'Nom',
            'slug' => 'Slug (identifiant URL)',
            'slug_help' => 'Lettres minuscules, chiffres et tirets uniquement (ex: fantaisie, science-fiction)',
            'description' => 'Description',
            'is_active' => 'Genre actif (visible dans les formulaires)',

            'create' => 'Créer le genre',
            'update' => 'Mettre à jour',
            'cancel' => 'Annuler',
        ],

        'validation' => [
            'name_required' => 'Le nom est obligatoire.',
            'name_max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'slug_required' => 'Le slug est obligatoire.',
            'slug_format' => 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des tirets.',
            'slug_unique' => 'Ce slug est déjà utilisé par un autre genre.',
            'order_required' => 'L\'ordre d\'affichage est obligatoire.',
            'order_integer' => 'L\'ordre doit être un nombre entier.',
            'description_max' => 'La description ne peut pas dépasser 1000 caractères.',
        ],
    ],
];
