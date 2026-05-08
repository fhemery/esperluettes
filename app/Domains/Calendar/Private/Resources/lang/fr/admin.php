<?php

return [
    'nav_group' => 'Calendrier',

    'activities' => [
        'title' => 'Activités',
        'nav_label' => 'Activités',
        'create_button' => 'Créer une activité',
        'edit_title' => 'Modifier l\'activité',
        'created' => 'Activité créée avec succès.',
        'updated' => 'Activité mise à jour avec succès.',
        'deleted' => 'Activité supprimée avec succès.',
        'no_items' => 'Aucune activité.',
        'confirm_delete' => 'Confirmer la suppression de cette activité ?',
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
    'sections' => [
        'details' => 'Activité',
        'media' => 'Média',
        'restrictions' => 'Restrictions & Paramètres',
        'dates' => 'Dates',
    ],
    'status' => [
        'draft' => 'Brouillon',
        'preview' => 'Aperçu',
        'active' => 'Actif',
        'ended' => 'Terminé',
        'archived' => 'Archivé',
    ],
    'timezone_hint' => 'Heure GMT',
];
