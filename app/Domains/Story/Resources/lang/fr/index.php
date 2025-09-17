<?php

return [
    'title' => 'Histoires',
    'heading' => 'Histoires',
    'empty' => "Aucune histoire publique pour le moment.",
    'connection_required' => 'Vous devez être connecté',
    'filter' => 'Filtrer',
    'reset_filters' => 'Réinitialiser',
    'filters' => [
        'audiences' => [
            'label' => 'Audience',
            'help' => 'Inclure les histoires possédant une des audiences sélectionnées',
        ],
        'trigger_warnings' => [
            'label' => 'Exclure les avertissements de contenu',
            'help' => 'Exclure les histoires possédant au moins un des avertissements sélectionnés'
        ],
        'genres' => [
            'label' => 'Genres',
            'help' => 'Inclure les histoires possédant tous les gens indiqués',
        ],
        'type' => [
            'label' => 'Type d\'histoire',
            'placeholder' => '— Tous —'
        ],
        'no_tw_only' => [
            'label' => 'Sans avertissements seulement',
            'help' => 'N\'afficher que les histoires marquées sans avertissements de contenu',
        ],        
        
    ],
];
