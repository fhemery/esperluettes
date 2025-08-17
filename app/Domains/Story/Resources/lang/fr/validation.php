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
];
