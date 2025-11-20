<?php

return [
    'user_management' => [
        'title' => 'Gestion des utilisateurs',
        'search_instruction' => "Tapez le nom d'une Esperluette pour chercher",
        'min_chars_instruction' => 'Saisissez au moins 2 caractères pour chercher',
        'no_results' => 'Pas de résultat',
        'error' => 'Erreur',

        'headers' => [
            'user_id' => 'ID Utilisateur',
            'profile_name' => 'Nom du profil',
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
