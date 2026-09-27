<?php

return [
    'nav_group' => 'Configuration',

    'feature_toggles' => [
        'title'          => 'Commutateurs de fonctionnalité',
        'nav_label'      => 'Commutateurs de fonctionnalité',
        'edit_title'     => 'Modifier le feature toggle',
        'updated'        => 'Feature toggle mis à jour avec succès.',
        'deleted'        => 'Feature toggle supprimé avec succès.',
        'access_updated' => 'Accès mis à jour avec succès.',
        'declared_cannot_be_deleted' => 'Ce feature toggle est déclaré dans le code : il ne peut pas être supprimé.',
        'columns' => [
            'domain' => 'Domaine',
            'name'   => 'Nom',
            'access' => 'Accès',
            'roles'  => 'Rôles',
        ],
        'access' => [
            'on'         => 'ON',
            'off'        => 'OFF',
            'role_based' => 'PAR RÔLE',
        ],
        'form' => [
            'access' => 'Accès',
            'roles'  => 'Rôles (accès par rôle)',
        ],
        'orphans' => [
            'title' => 'Non déclarés dans le code',
            'label' => 'Non déclaré dans le code',
        ],
        'actions' => [
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
