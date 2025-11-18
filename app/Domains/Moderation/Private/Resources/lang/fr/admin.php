<?php

return [
    'user_management' => [
        'title' => 'Gestion des utilisateurs',
        'search_instruction' => "Tapez le nom d'une Esperluette pour chercher",
        'min_chars_instruction' => 'Minimum 2 caractères requis',
        'no_results' => 'Aucun résultat trouvé',
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
        ],
    ],
];
