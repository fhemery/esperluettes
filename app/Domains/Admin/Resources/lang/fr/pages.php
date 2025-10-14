<?php

return [
    'groups' => [
        'tech' => 'Tech',
    ],
    'system_maintenance' => [
        'nav_label' => 'Admin site tech.',
        'title' => 'Maintenance du système',
        'description' => 'Utilisez l’action d’en-tête « Vider tous les caches » pour nettoyer les caches de configuration, routes, vues et autres.',
        'no_permission' => "Vous n’avez pas l’autorisation d’utiliser cette page.",
        'actions' => [
            'clear_cache' => 'Vider tous les caches',
        ],
        'notifications' => [
            'cache_cleared' => 'Caches vidés',
        ],
    ],
    'view_logs' => [
        'nav_label' => 'Journaux (logs)',
        'title' => 'Consultation des journaux',
        'description' => 'Sélectionnez un fichier de logs ci-dessous. Affiche les 1000 dernières lignes par défaut. Utilisez « Télécharger » pour récupérer l’intégralité du fichier.',
        'select_file' => 'Fichier de logs',
        'refresh' => 'Rafraîchir',
        'download' => 'Télécharger',
    ],
    'feature_toggles' => [
        'nav_label' => 'Commutateurs de fonctionnalités',
        'columns' => [
            'name' => 'Nom',
            'domain' => 'Domaine',
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

