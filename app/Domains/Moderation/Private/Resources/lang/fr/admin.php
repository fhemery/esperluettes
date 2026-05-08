<?php

return [
    'reasons' => [
        'title' => 'Raisons de signalement',
        'nav_label' => 'Raisons',
        'create_button' => 'Nouvelle raison',
        'created' => 'Raison créée.',
        'updated' => 'Raison mise à jour.',
        'deleted' => 'Raison supprimée.',
        'cannot_delete_in_use' => 'Impossible de supprimer cette raison : elle est utilisée dans :count signalement(s).',
        'reorder_button' => 'Réordonner',

        'table' => [
            'id' => 'ID',
            'topic' => 'Type de contenu',
            'label' => 'Libellé',
            'sort_order' => 'Ordre',
            'is_active' => 'Active',
            'actions' => 'Actions',
            'no_results' => 'Aucune raison de signalement.',
        ],

        'filters' => [
            'all_topics' => 'Tous les types',
            'all_statuses' => 'Tous les statuts',
            'active_only' => 'Actives',
            'inactive_only' => 'Inactives',
            'apply' => 'Filtrer',
            'reset' => 'Réinitialiser',
        ],

        'form' => [
            'topic_key' => 'Type de contenu',
            'topic_placeholder' => 'Sélectionner un type',
            'label' => 'Libellé',
            'label_helper' => 'Texte affiché aux utilisateurs lors du signalement',
            'is_active' => 'Active',
            'is_active_helper' => 'Les raisons inactives sont cachées aux utilisateurs mais conservées pour l\'historique',
            'create' => 'Créer',
            'update' => 'Enregistrer',
            'cancel' => 'Annuler',
        ],

        'create_title' => 'Nouvelle raison de signalement',
        'edit_title' => 'Modifier une raison de signalement',
        'confirm_delete' => 'Confirmer la suppression de cette raison ?',
        'delete_button' => 'Supprimer',
        'edit_button' => 'Modifier',
    ],

    'user_management' => [
        'title' => 'Gestion des utilisateurs',
        'search' => [
            'label' => 'Nom d\'Esperluette :',
            'placeholder' => '2 caractères minimum',
        ],
        'search_instruction' => "Tapez le nom d'une Esperluette pour chercher",
        'min_chars_instruction' => 'Saisissez au moins 2 caractères pour chercher',
        'confirm_deactivate' => 'Confirmer la désactivation de ce compte ?',
        'deactivated_success' => 'Le compte a été désactivé avec succès',
        'deactivated_error' => 'Une erreur est survenue lors de la désactivation du compte',
        'confirm_activate' => 'Confirmer la réactivation de ce compte ?',
        'activated_success' => 'Le compte a été réactivé avec succès',
        'activated_error' => 'Une erreur est survenue lors de la réactivation du compte',
        'network_error' => 'Une erreur est survenue lors de la requête',
        'no_results' => 'Pas de résultat',
        'error' => 'Erreur',

        'headers' => [
            'user_id' => 'ID',
            'profile_name' => 'Nom',
            'email' => 'Email',
            'status' => 'Statut',
            'confirmed_reports' => 'Signalements confirmés',
            'rejected_reports' => 'Signalements rejetés',
            'actions' => 'Actions',
        ],

        'status' => [
            'active' => 'Actif',
            'inactive' => 'Inactif',
        ],

        'actions' => [
            'ban' => 'Bannir',
            'reactivate' => 'Réactiver',
            'copy_email' => "Copier l'email",
            'deactivate' => 'Désactiver',
        ],
    ],
];
