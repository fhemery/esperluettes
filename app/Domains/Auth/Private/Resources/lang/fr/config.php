<?php

return [
    // Domain name displayed in the admin config parameters page
    'domain_name' => 'Authentification',

    // Parameter definitions
    'params' => [
        'require_activation_code' => [
            'name' => "Code d'activation nécessaire à l'inscription",
            'description' => "Si activé, un code d'activation est obligatoire pour s'inscrire. Sinon, les utilisateurs peuvent s'inscrire sans code et deviennent des Graines.",
        ],
        'non_confirmed_comment_threshold' => [
            'name' => "Nombre minimum de commentaires d'une Graine",
            'description' => "Le nombre minimum de commentaires qu'une graine doit déposer pour être éligible à une promotion.",
        ],
        'non_confirmed_timespan' => [
            'name' => 'Durée minimum de la période d\'essai',
            'description' => "Temps minimum qu'une Graine doit attendre pour demander sa promotion.",
        ],
    ],
];
