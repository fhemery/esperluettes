<?php

return [
    'user_management' => 'Gestion des utilisateurs',

    'role' => [
        'label' => 'Rôle',
        'plural_label' => 'Rôles',
        'navigation_label' => 'Rôles',
        'name_header' => 'Nom',
        'users_count_header' => 'Utilisateurs',
        'description_header' => 'Description',
    ],

    'users' => [
        'model_label' => 'Utilisateur',
        'plural_label' => 'Utilisateurs',
        'navigation_label' => 'Utilisateurs',
        'name_header' => 'Nom',
        'email_header' => 'Email',
        'roles_header' => 'Rôles',
        'email_verified_at_header' => 'Email vérifié ?',
        'is_active_header' => 'Actif',

        'actions' => [
            'activate' => 'Activer',
            'deactivate' => 'Désactiver',
        ],
        'status' => [
            'active' => 'Actif',
            'inactive' => 'Inactif',
        ],
        'activation' => [
            'success' => "L'utilisateur a été activé avec succès.",
            'confirm_title' => "Confirmer l'activation",
            'confirm_message' => "Êtes-vous sûr de vouloir activer cet utilisateur ?",
        ],
        'deactivation' => [
            'success' => "L'utilisateur a été désactivé avec succès.",
            'confirm_title' => 'Confirmer la désactivation',
            'confirm_message' => "Êtes-vous sûr de vouloir désactiver cet utilisateur ? Toutes ses sessions actives seront terminées.",
        ],
    ],

    'activation_codes' => [
        'model_label' => "Code d'activation",
        'plural_label' => "Codes d'activation",
        'navigation_label' => "Codes d'activation",
        'code_header' => "Code d'activation",
        'sponsor_header' => 'Parrain',
        'status_header' => 'Statut',
        'used_by_header' => 'Utilisé par',
        'used_at_header' => 'Utilisé le',
        'expires_at_header' => "Expire le",
        'comment_header' => 'Commentaire',
        'created_header' => 'Créé',

        'sponsor_user_label' => 'Utilisateur parrain',
        'sponsor_user_helper' => "Sélectionnez optionnellement l'utilisateur qui sera le parrain de ce code d'activation",
        'comment_label' => 'Commentaire',
        'comment_placeholder' => "Commentaire optionnel sur ce code d'activation",
        'expires_at_label' => "Date d'expiration",
        'expires_at_helper' => "Laissez vide pour aucune expiration",

        'status' => [
            'active' => 'Actif',
            'used' => 'Utilisé',
            'expired' => 'Expiré',
        ],
        'filter' => [
            'status' => 'Statut',
            'sponsor' => 'Sponsor',
        ],
        'actions' => [
            'generate' => 'Générer un code',
        ],
        'placeholder' => [
            'no_sponsor' => 'Aucun sponsor',
            'not_used' => 'Non utilisé',
            'no_comment' => 'Aucun commentaire',
            'no_expiration' => 'Aucune expiration',
        ],
    ],
];
