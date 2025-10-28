<?php

return [
    'story_id' => [
        'required' => 'Veuillez sélectionner une histoire.',
        'integer' => "L'identifiant de l'histoire est invalide.",
        'min' => "L'identifiant de l'histoire est invalide.",
    ],
    'target_word_count' => [
        'required' => 'Veuillez saisir un objectif de mots.',
        'integer' => "Le nombre d'objectifs doit être un nombre entier.",
        'min' => 'Votre objectif doit être au minimum de 1 000 mots.',
    ],
];
