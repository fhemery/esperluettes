<?php

return [
    'navigation' => [
        'group' => 'Calendrier',
        'activities' => 'Activités',
    ],

    'resource' => [
        'label' => 'Activité',
    ],

    'sections' => [
        'media' => 'Média',
        'restrictions' => 'Restrictions & Paramètres',
        'dates' => 'Dates',
    ],

    'fields' => [
        'name' => 'Nom',
        'activity_type' => 'Type',
        'description' => 'Description',
        'image' => 'Image',
        'role_restrictions' => 'Restrictions de rôle',
        'requires_subscription' => 'Inscription requise',
        'max_participants' => 'Nombre max de participants',
        'preview_starts_at' => 'Début de visibilité',
        'active_starts_at' => 'Date de début',
        'active_ends_at' => 'Date de fin',
        'archived_at' => 'Fin de visibilité',
        'status' => 'Statut',
    ],

    'actions' => [
        'remove_image' => 'Supprimer l’image actuelle',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'bulk_delete' => 'Supprimer la sélection',
    ],

    'status' => [
        'draft' => 'Brouillon',
        'preview' => 'Aperçu',
        'active' => 'Actif',
        'ended' => 'Terminé',
        'archived' => 'Archivé',
    ],
];
