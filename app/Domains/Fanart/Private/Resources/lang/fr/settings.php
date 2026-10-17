<?php

return [
    'sections' => [
        'fanart' => [
            'name' => 'Fanarts',
            'description' => 'Qui peut proposer des fanarts sur vos histoires. Sur une histoire à plusieurs auteurs, le réglage le plus restrictif choisi par l\'un des auteurs s\'applique.',
        ],
    ],
    'params' => [
        'fanart-images-mode' => [
            'name' => 'Fanarts : images',
            'description' => 'Qui peut proposer des images sur vos histoires. Avec plusieurs auteurs, le niveau le plus restrictif (non, puis fanartistes désignés, puis oui) l\'emporte.',
            'options' => [
                'yes' => 'Tous les lecteurs confirmés',
                'designated' => 'Les fanartistes désignés',
                'no' => 'Personne',
            ],
        ],
        'fanart-audio-mode' => [
            'name' => 'Fanarts : sons',
            'description' => 'Qui peut proposer des sons sur vos chapitres. Avec plusieurs auteurs, le niveau le plus restrictif (non, puis fanartistes désignés, puis oui) l\'emporte.',
            'options' => [
                'yes' => 'Tous les lecteurs confirmés',
                'designated' => 'Les fanartistes désignés',
                'no' => 'Personne',
            ],
        ],
        'hide-fanart-tab' => [
            'name' => 'Masquer mes fanarts',
            'description' => 'Si activé, l\'onglet Fanarts de votre profil n\'est visible que de vous et de l\'équipe de modération.',
        ],
    ],
];
