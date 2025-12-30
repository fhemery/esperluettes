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
    ],
];
