<?php

return [
    'title' => [
        'required' => 'Le titre est requis.',
        'string' => 'Le titre doit être une chaîne de caractères.',
        'min' => 'Le titre doit contenir au moins 1 caractère.',
        'max' => 'Le titre ne peut pas dépasser 255 caractères.',
    ],

    'description' => [
        'required' => 'La description est requise.',
        'string' => 'La description doit être une chaîne de caractères.',
        'min' => 'La description doit contenir au moins :min caractères.',
        'max' => 'La description ne peut pas dépasser :max caractères.',
    ],

    'visibility' => [
        'required' => 'La visibilité est requise.',
        'in' => 'La visibilité sélectionnée est invalide.',
    ],

    'type' => [
        'required' => 'Le type d\'histoire est requis',
        'integer' => 'Le type d\'histoire doit être un entier',
        'exists' => 'Ce type d\'histoire n\'existe pas'
    ],

    'audience' => [
        'required' => 'L\'audience est requise',
        'integer' => 'L\'audience doit être un entier',
        'exists' => 'Cette audience n\'existe pas'
    ],
    'copyright' => [
        'required' => 'Le copyright est requis',
        'integer' => 'Le copyright doit être un entier',
        'exists' => 'Ce copyright n\'existe pas'
    ],
    'genres' => [
        'required' => 'Les genres sont requis',
        'array' => 'Le champ genres doit être une liste',
        'min' => 'Sélectionnez au moins 1 genre',
        'max' => 'Vous ne pouvez sélectionner que 3 genres maximum',
        'integer' => 'Chaque genre doit être un entier',
        'exists' => 'Un genre sélectionné n\'existe pas',
    ],

    'status' => [
        'integer' => 'Le statut doit être un entier',
        'exists' => 'Ce statut n\'existe pas',
    ],

    'feedback' => [
        'integer' => 'Le retour doit être un entier',
        'exists' => 'Ce retour n\'existe pas',
    ],

    'trigger_warnings' => [
        'array' => 'Le champ avertissements de contenu doit être une liste',
        'integer' => 'Chaque avertissement de contenu doit être un entier',
        'exists' => 'Un avertissement de contenu sélectionné n\'existe pas',
    ],

    'tw_disclosure' => [
        'required' => 'Veuillez indiquer comment vous gérez les avertissements de contenu.',
        'in' => 'Choix invalide pour l\'indication des avertissements.',
        'listed_requires_tw' => 'Si vous choisissez « Avertissements listés », vous devez en sélectionner au moins un.',
    ],

    // Chapters
    'author_note_too_long' => 'La note de l\'auteur dépasse la limite de 1000 caractères.',
];
