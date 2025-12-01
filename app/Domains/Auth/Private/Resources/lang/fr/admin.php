<?php

return [
    'users' => [
        'title' => 'Gestion des utilisateurs',
        'nav_label' => 'Utilisateurs',
        'export_button' => 'Exporter',
        'edit_button' => 'Modifier',
        'delete_button' => 'Supprimer',
        'no_users' => 'Aucun utilisateur trouvé.',
        'updated' => 'L\'utilisateur a été mis à jour avec succès.',
        'edit_title' => 'Modifier l\'utilisateur :name',

        'filter' => [
            'search' => 'Recherche',
            'search_placeholder' => 'ID, email ou pseudo...',
            'all' => 'Tous',
            'apply' => 'Filtrer',
            'reset' => 'Réinitialiser',
        ],

        'table' => [
            'status' => 'Statut',
            'is_minor' => 'Mineur',
            'minor_status' => 'Statut mineur',
            'minor_tooltip' => 'Utilisateur de moins de 15 ans',
            'minor_verified' => 'Mineur - autorisation vérifiée',
            'minor_not_verified' => 'Mineur - autorisation non vérifiée',
            'email_verified' => 'Email vérifié',
            'email_not_verified' => 'Email non vérifié',
            'authorization_verified' => 'Autorisation vérifiée',
            'download_authorization' => 'Télécharger l\'autorisation',
            'terms_accepted' => 'CGU acceptées',
            'actions' => 'Actions',
        ],

        'form' => [
            'display_name_help' => 'Le pseudo est géré dans le profil de l\'utilisateur.',
            'info_section' => 'Informations',
            'minor_section' => 'Utilisateur mineur',
            'not_verified' => 'Non vérifié',
            'pending' => 'En attente',
            'authorization_file' => 'Fichier d\'autorisation',
            'update' => 'Mettre à jour',
            'cancel' => 'Annuler',
        ],

        'authorization' => [
            'not_found' => 'Le fichier d\'autorisation parentale n\'a pas été trouvé.',
            'cleared' => 'L\'autorisation parentale a été supprimée avec succès.',
            'cannot_clear' => 'Impossible de supprimer l\'autorisation parentale.',
            'clear_button' => 'Supprimer l\'autorisation',
            'clear_confirm' => 'Êtes-vous sûr de vouloir supprimer l\'autorisation parentale ? L\'utilisateur devra en soumettre une nouvelle.',
        ],
    ],
];
