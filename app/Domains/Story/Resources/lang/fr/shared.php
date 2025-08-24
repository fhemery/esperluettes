<?php

return [
    'visibility' => [
        'label' => 'Visibilité',
        'options' => [
            'public' => 'Publique',
            'community' => 'Communauté',
            'private' => 'Privée',
        ],
        'help' => [
            'intro' => 'Choisissez qui peut voir votre histoire :',
            'public' => 'Publique : visible par tous',
            'community' => 'Communauté : visible par les utilisateurs connectés',
            'private' => 'Privée : visible uniquement par vous et vos co-auteurs',
        ],
    ],
    'description' => [
        'label' => 'Résumé',
    ],
    'type' => [
        'label' => 'Type',
        'placeholder' => 'Sélectionner un type',
        'help' => 'Choisissez la catégorie de votre histoire',
    ],
    'audience' => [
        'label' => 'Audience',
        'placeholder' => 'Sélectionner une audience',
        'help' => 'Choisissez le public visé par votre histoire',
        'note_single_select' => 'Sélection unique requise',
    ],
    'copyright' => [
        'label' => 'Copyright',
        'placeholder' => 'Sélectionner un copyright',
        'help' => 'Sélectionnez le régime de droits pour cette histoire',
    ],
    'genres' => [
        'label' => 'Genres',
        'help' => 'Sélectionnez 1 à 3 genres qui décrivent votre histoire',
        'note_range' => 'Sélectionnez entre 1 et 3 genres',
    ],
    'required' => 'Champ requis',
    'by' => 'Par '
];
