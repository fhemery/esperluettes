<?php

return [
    'tabs' => [
        'general' => 'Général',
    ],

    'sections' => [
        'appearance' => [
            'name' => 'Apparence',
            'description' => 'Personnalisez l\'apparence du site.',
        ],
    ],

    'params' => [
        'theme' => [
            'name' => 'Thème',
            'description' => 'Choisissez le thème visuel du site.',
            'options' => [
                'seasonal' => 'Saisonnier (défaut)',
                'autumn' => 'Automne',
                'winter' => 'Hiver',
                'spring' => 'Printemps',
                'summer' => 'Été',
            ],
        ],
        'font' => [
            'name' => 'Police des textes',
            'description' => 'Choisissez la police utilisée pour le contenu textuel.',
            'options' => [
                'aptos' => 'Aptos (défaut)',
                'times' => 'Times New Roman',
            ],
        ],
        'interline' => [
            'name' => 'Interligne',
            'description' => 'Choisissez l\'espacement entre les lignes dans les textes.',
            'options' => [
                'low' => 'Faible (1)',
                'medium' => 'Moyen (1,5) (défaut)',
                'high' => 'Élevé (2)',
            ],
        ],
    ],
];
