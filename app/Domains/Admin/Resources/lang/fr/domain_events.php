<?php

return [
    'model_label' => 'Événement',
    'plural_label' => 'Événements',
    'navigation_label' => 'Événements',
    'navigation_group' => 'Audit',
    'columns' => [
        'occurred_at' => 'Date',
        'event' => 'Événement',
        'summary' => 'Résumé',
        'display_name' => 'Nom',
        'user_id' => 'UserID',
        'url' => 'URL',
        'ip' => 'IP',
        'payload' => 'Données',
        'meta' => 'Méta',
        'id' => 'Id',
        'user_agent' => 'Agent utilisateur',
    ],
    'filters' => [
        'name_filter' => 'Nom',
        'user_id' => 'ID utilisateur',
        'occurred_after' => 'Après le',
        'occurred_before' => 'Avant le',
        'per_page' => 'Éléments par page',
    ],
];
