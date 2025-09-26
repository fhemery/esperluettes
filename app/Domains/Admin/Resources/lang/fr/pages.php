<?php

return [
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
];
