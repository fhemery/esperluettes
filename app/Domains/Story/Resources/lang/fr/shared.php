<?php

return [
    'panels' => [
        'general' => 'Informations générales',
        'details' => 'Détails',
        'audience' => 'Audience',
        'misc' => 'Divers',
    ],
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
    'title' => [
        'label' => 'Titre',
        'placeholder' => 'Entrez le titre de votre histoire.',
        'help' => 'En panne d\'inspiration ? Vous pourrez le changer plus tard !',
    ],
    'description' => [
        'label' => 'Résumé',
        'help' => 'Un résumé court de votre histoire, type quatrième de couverture, qui donnera envie à vos lecteurices.',
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
    'status' => [
        'label' => 'Statut',
        'placeholder' => 'Sélectionner un statut (optionnel)',
        'help' => "Choisissez le statut d'avancement de l'histoire (facultatif)",
    ],
    'feedback' => [
        'label' => 'Retour',
        'placeholder' => 'Sélectionner un retour (optionnel)',
        'help' => "Choisissez une indication de retour souhaité (facultatif)",
    ],
    'trigger_warnings' => [
        'label' => 'Avertissements de contenu',
        'placeholder' => 'Rechercher des avertissements…',
        'help' => 'Sélectionnez les avertissements de contenu pertinents (facultatif)',
    ],
    'genres' => [
        'label' => 'Genres',
        'placeholder' => 'Rechercher des genres…',
        'help' => 'Sélectionnez 1 à 3 genres qui décrivent votre histoire',
        'note_range' => 'Sélectionnez entre 1 et 3 genres',
    ],
    'required' => 'Champ requis',
    'optional' => 'Champ facultatif',
    'by' => 'Par ',
    'metrics' => [
        // Pluralized labels for meta rows
        'chapters' => '{0} Aucun chapitre|{1} :count chapitre|[2,*] :count chapitres',
        'words' => '{0} 0 mot|{1} :count mot|[2,*] :count mots',
        'words_and_signs' => [
            'label' => ':nbWords mots, :nbCharacters SEC*',
            'help' => '*SEC: Signes Espaces Comprises',
        ],
    ],
    'filters' => [
        'header' => 'Filtres'
    ]
    ,
    'no_results' => 'Aucun résultat',
    'coming_soon' => 'À venir'
];
