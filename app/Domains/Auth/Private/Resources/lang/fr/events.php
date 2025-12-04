<?php

return [
    'user_registered' => [
        'summary' => ":name (id=:id) vient de s'inscrire",
    ],
    'email_verified' => [
        'summary' => "Email vérifié",
    ],
    'password_changed' => [
        'summary' => "Mot de passe changé (id=:id)",
    ],
    'password_reset_requested' => [
        'summary' => "Demande de réinitialisation de mot de passe pour :email (id=:id)",
    ],
    'user_logged_in' => [
        'summary' => "Connexion réussie",
    ],
    'user_logged_out' => [
        'summary' => "Déconnexion",
    ],
    'user_role_granted' => [
        'summary' => "Rôle attribué : :role, ID user : :id",
        'system' => [
            'summary' => "Rôle attribué par le système : :role, ID user : :id",
        ],
    ],
    'user_role_revoked' => [
        'summary' => "Rôle révoqué : :role, ID user : :id",
        'system' => [
            'summary' => "Rôle révoqué par le système : :role, ID user : :id",
        ],
    ],
    'user_deactivated' => [
        'summary' => "Utilisateur désactivé (id=:id)",
    ],
    'user_reactivated' => [
        'summary' => "Utilisateur réactivé (id=:id)",
    ],
    'user_deleted' => [
        'summary' => "Utilisateur supprimé (id=:id)",
    ],
    'promotion_requested' => [
        'summary' => "Promotion demandée par l'utilisateur id=:id",
    ],
    'promotion_accepted' => [
        'summary' => "Promotion acceptée pour l'utilisateur id=:id",
    ],
    'promotion_rejected' => [
        'summary' => "Promotion refusée pour l'utilisateur id=:id",
    ],
];
