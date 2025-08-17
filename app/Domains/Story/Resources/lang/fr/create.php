<?php

return [
    'title' => 'Créer une nouvelle histoire',
    'intro' => 'Commençons par quelques informations de base',

    'form' => [
        'title' => [
            'label' => 'Titre',
            'placeholder' => 'Entrez le titre de votre histoire',
        ],
        'visibility' => [
            'label' => 'Visibilité',
            'help' => [
                'intro' => 'Vous pouvez choisir à qui vous exposez votre histoire',
                'public' => 'Tout le monde (y compris les visiteurs non connectés)',
                'community' => 'Les & connectées',
                'private' => 'Vous seul (et vos co-auteurs le cas échéant)',
            ],
            'options' => [
                'public' => 'Publique',
                'community' => 'Communauté',
                'private' => 'Privée',
            ],
        ],
    ],

    'actions' => [
        'continue' => 'Continuer',
    ],
];
