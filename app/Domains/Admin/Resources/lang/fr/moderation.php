<?php

return [
    'navigation_group' => 'Modération',

    'reason' => [
        'label' => 'Raison de signalement',
        'plural_label' => 'Raisons de signalement',
        'navigation_label' => 'Raisons',
        
        'topic_key' => 'Type de contenu',
        'label_field' => 'Libellé',
        'label_helper' => 'Le texte affiché aux utilisateurs lors du signalement',
        'sort_order' => 'Ordre',
        'is_active' => 'Active',
        'is_active_helper' => 'Les raisons inactives sont cachées aux utilisateurs mais conservées pour l\'historique',
        
        'filter_topic' => 'Filtrer par type',
        'filter_active' => 'Statut',
        'filter_all' => 'Toutes',
        'filter_active_only' => 'Actives uniquement',
        'filter_inactive_only' => 'Inactives uniquement',
        
        'cannot_delete_in_use' => 'Impossible de supprimer cette raison car elle est utilisée dans :count signalement(s)|Impossible de supprimer cette raison car elle est utilisée dans :count signalements',
    ],
];
