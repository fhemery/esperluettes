<?php

return [
    'groups' => [
        'tech' => 'Tech',
        'moderation' => 'Modération',
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
];

