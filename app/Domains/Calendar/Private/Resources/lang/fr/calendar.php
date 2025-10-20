<?php

return [
    'validation' => [
        'activity_type' => [
            'unknown' => 'Type d\'activité inconnu.',
        ],
        'dates' => [
            'active_starts_before_preview' => 'La date de début d\'activité doit être postérieure ou égale à la date de début d\'aperçu.',
            'active_ends_before_start' => 'La date de fin d\'activité doit être postérieure ou égale à la date de début d\'activité.',
            'archived_before_end' => 'La date d\'archivage doit être postérieure ou égale à la date de fin d\'activité.',
        ],
    ],
];
