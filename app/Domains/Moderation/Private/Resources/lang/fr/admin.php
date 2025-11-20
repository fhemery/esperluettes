<?php

return [
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
