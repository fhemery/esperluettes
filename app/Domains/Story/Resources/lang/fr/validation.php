<?php

return [
    'title' => [
        'required' => 'Le titre est requis.',
        'string' => 'Le titre doit être une chaîne de caractères.',
        'min' => 'Le titre doit contenir au moins 1 caractère.',
        'max' => 'Le titre ne peut pas dépasser 255 caractères.',
    ],

    'description' => [
        'string' => 'La description doit être une chaîne de caractères.',
        'max' => 'La description ne peut pas dépasser 3000 caractères.',
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
];
