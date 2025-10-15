<?php

return [
    'group' => 'Configuration',
    'feature_toggles' => [
        'nav_label' => 'Commutateurs de fonctionnalités',
        'model_label' => 'Commutateur de fonctionnalité',
        'model_labels' => 'Commutateurs de fonctionnalités',

        'columns' => [
            'name' => 'Nom',
            'domain' => 'Domaine',
            'description' => 'Description',
            'access' => 'Accès',
            'admin_visibility' => 'Visibilité admin',
            'roles' => 'Rôles',
        ],
        'access' => [
            'on' => 'Activé',
            'off' => 'Désactivé',
            'role_based' => 'Par rôles',
        ],
        'admin_visibility' => [
            'tech_admins_only' => 'Admins techniques uniquement',
            'all_admins' => 'Tous les admins',
        ],
        'actions' => [
            'create' => 'Créer un commutateur',
            'delete' => 'Supprimer',
            'set_on' => 'Activer',
            'set_off' => 'Désactiver',
            'set_role_based' => 'Par rôles',
        ],
        'form' => [
            'name' => 'Nom',
            'domain' => 'Domaine',
            'admin_visibility' => 'Visibilité admin',
            'access' => 'Accès',
            'roles' => 'Rôles',
            'roles_helper' => 'Utilisé seulement lorsque l\'accès est « par rôles ».',
        ],
        'notifications' => [
            'created' => 'Commutateur créé',
            'updated' => 'Commutateur mis à jour',
            'deleted' => 'Commutateur supprimé',
        ],
    ],
];