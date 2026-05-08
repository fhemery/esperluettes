<?php

return [
    'feature_toggles' => [
        'title'          => 'Commutateurs de fonctionnalité',
        'nav_label'      => 'Commutateurs de fonctionnalité',
        'create_title'   => 'Créer un feature toggle',
        'edit_title'     => 'Modifier le feature toggle',
        'created'        => 'Feature toggle créé avec succès.',
        'updated'        => 'Feature toggle mis à jour avec succès.',
        'deleted'        => 'Feature toggle supprimé avec succès.',
        'access_updated' => 'Accès mis à jour avec succès.',
        'columns' => [
            'domain'           => 'Domaine',
            'name'             => 'Nom',
            'description'      => 'Description',
            'access'           => 'Accès',
            'admin_visibility' => 'Visibilité admin',
            'roles'            => 'Rôles',
        ],
        'access' => [
            'on'         => 'ON',
            'off'        => 'OFF',
            'role_based' => 'PAR RÔLE',
        ],
        'admin_visibility' => [
            'tech_admins_only' => 'Tech admins uniquement',
            'all_admins'       => 'Tous les admins',
        ],
        'form' => [
            'name'             => 'Nom',
            'domain'           => 'Domaine',
            'admin_visibility' => 'Visibilité admin',
            'access'           => 'Accès',
            'roles'            => 'Rôles (accès par rôle)',
        ],
        'actions' => [
            'create'         => 'Créer',
            'set_on'         => 'ON',
            'set_off'        => 'OFF',
            'set_role_based' => 'Par rôle',
            'edit'           => 'Modifier',
            'delete'         => 'Supprimer',
        ],
        'confirm_delete' => 'Confirmer la suppression de ce feature toggle ?',
        'no_items'       => 'Aucun feature toggle.',
    ],

    'parameters' => [
        'title' => 'Paramètres de configuration',
        'nav_label' => 'Paramètres',
        'search_placeholder' => 'Rechercher un paramètre...',
        'no_parameters' => 'Aucun paramètre de configuration enregistré.',
        'no_results' => 'Aucun paramètre ne correspond à votre recherche.',
        'overridden' => 'Modifié',
        'save' => 'Enregistrer',
        'saved' => 'Paramètre enregistré avec succès.',
        'reset_tooltip' => 'Rétablir la valeur par défaut',
        'reset_success' => 'Paramètre réinitialisé à sa valeur par défaut.',
    ],
];
