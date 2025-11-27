<?php

return [
    'name' => 'Pseudo',
    'email' => 'E-mail',
    'password' => 'Mot de passe',
    'confirm_password' => 'Confirmer le mot de passe',

    'activation' => [
        'label' => "Code d'activation",
        'help' => "Entrez le code d'activation qui vous a été fourni.",
    ],

    'links' => [
        'already_registered' => 'Déjà inscrit ?',
    ],

    'submit' => "S'inscrire",

    // Validation messages for RegisterRequest
    'form' => [
        'name' => [
            'required' => 'Veuillez saisir votre nom.',
            'min' => 'Le nom doit faire minimum 2 caractères',
            'string' => 'Le nom doit être une chaîne de caractères valide.',
            'max' => 'Le nom ne peut pas dépasser :max caractères.',
        ],
        'email' => [
            'required' => 'Veuillez saisir votre adresse e-mail.',
            'string' => "L'adresse e-mail doit être une chaîne de caractères valide.",
            'lowercase' => "L'adresse e-mail doit être en minuscules.",
            'email' => 'Veuillez fournir une adresse e-mail valide.',
            'max' => "L'adresse e-mail ne peut pas dépasser :max caractères.",
            'unique' => "Cette adresse e-mail est déjà enregistrée.",
        ],
        'password' => [
            'required' => 'Veuillez choisir un mot de passe.',
            'confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'min' => 'Le mot de passe doit contenir au moins :min caractères.',
            // The following keys are used only if such constraints are enabled
            'letters' => 'Le mot de passe doit contenir au moins une lettre.',
            'mixed' => 'Le mot de passe doit contenir des lettres majuscules et minuscules.',
            'numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
            'symbols' => 'Le mot de passe doit contenir au moins un symbole.',
            'uncompromised' => 'Ce mot de passe apparaît dans une fuite de données. Veuillez en choisir un autre.',
        ],
        'activation_code' => [
            'required' => "Le code d'activation est requis.",
            'string' => "Le code d'activation doit être une chaîne de caractères.",
            'invalid' => "Le code d'activation est invalide, expiré ou déjà utilisé.",
        ],
        'under_15' => [
            'label' => 'J\'ai moins de 15 ans',
        ],
        'accept_terms' => [
            'label' => 'J\'accepte les <a href="/mentions-legales-et-cgu" target="_blank">conditions d\'utilisation</a> et le <a href="/reglement" target="_blank">règlement</a>',
            'required' => 'Vous devez accepter les conditions d\'utilisation.',
        ]
    ],
];
