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

    'reports' => [
        'label' => 'Signalement',
        'plural_label' => 'Signalements',
        'navigation_label' => 'Signalements',

        'fields' => [
            'topic' => 'Type',
            'entity' => 'Id',
            'reason' => 'Raison',
            'description' => 'Description',
            'reported_by' => 'Signalé par',
            'status' => 'Statut',
            'created_at' => 'Date',
            'review_comment' => 'Commentaire modération',
            'review_comment_hint' => 'Ce commentaire est interne aux modérateurices, destiné à se souvenir des décisions et actions.',
            'snapshot' => 'Aperçu',
        ],

        'status' => [
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'dismissed' => 'Refusé',
        ],

        'actions' => [
            'open' => 'Ouvrir',
            'approve' => 'Approuver',
            'dismiss' => 'Refuser',
        ],
    ]
];
