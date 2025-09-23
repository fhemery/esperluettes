<?php

return [
    'title' => 'Toutes les histoires',
    'heading' => 'Toutes les histoires',
    'empty' => "Aucune histoire publique pour le moment.",
    'connection_required' => 'Vous devez être connecté',
    'filter' => 'Filtrer',
    'reset_filters' => 'Réinitialiser',
    'filters' => [
        'audiences' => [
            'label' => 'Classification par âge',
            'help' => 'Inclure les histoires possédant une des audiences sélectionnées',
            'placeholder' => '— Toutes —'
        ],
        'trigger_warnings' => [
            'label' => 'Exclure selon le contenu',
            'help' => 'Exclure les histoires possédant au moins un des avertissements sélectionnés',
            'placeholder' => '— Aucun —'
        ],
        'genres' => [
            'label' => 'Genres',
            'help' => 'Inclure les histoires possédant tous les gens indiqués',
            'placeholder' => '— Tous —'
        ],
        'type' => [
            'label' => 'Type d\'histoire',
            'placeholder' => '— Tous —'
        ],
        'no_tw_only' => [
            'label' => 'Histoires sans avertissement',
            'help' => 'Ne retourne que les histoires où l\'auteurice a explicitement dit qu\'il n\'y a pas de contenu à risque',
        ],        
        
    ],
];
