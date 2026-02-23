<?php

return [
    'panels' => [
        'general' => 'Informations générales',
        'presentation' => 'Présentation',
        'details' => 'Détails',
        'audience' => 'Audience',
        'misc' => 'Divers',
    ],
    'cover' => [
        'label' => 'Couverture',
        'modal_description' => 'Choisissez une couverture pour votre histoire. Naviguez entre les onglets pour accéder aux différentes options.',
        'change' => 'Changer la couverture',
        'modal_title' => 'Choisir une couverture',
        'tab_default' => 'Par défaut',
        'tab_themed' => 'Par genre',
        'tab_custom' => 'Personnelle',
        'default_description' => 'La couverture par défaut du Jardin. Une Esperluette neutre, sur un fond neutre.',
        'themed_description' => 'Choisissez une couverture correspondant à un genre de votre histoire. Le titre et le pseudo seront automatiquement ajoutés en haut de la couverture après sauvegarde.',
        'themed_select_genre' => 'Genre',
        'themed_no_genres' => 'Aucune couverture disponible pour les genres sélectionnés',
        'select_default' => 'Sélectionner la couverture par défaut',
        'select' => 'Sélectionner',
        'cancel' => 'Annuler',
        'custom_description' => 'Importez votre propre image de couverture. Elle sera redimensionnée automatiquement.',
        'custom_ai_warning' => 'Les couvertures générées par IA ne sont pas autorisées.',
        'custom_upload_label' => 'Image de couverture',
        'custom_dimensions' => 'Dimensions recommandées : 900×1200px',
        'custom_rights_label' => 'Je confirme que cette image est libre de droits, et n\'est pas générée par IA.',
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
        'placeholder' => '-- Type --',
        'help' => 'Choisissez la catégorie de votre histoire',
    ],
    'audience' => [
        'label' => 'Audience',
        'placeholder' => '-- Audience --',
        'help' => 'Choisissez le public visé par votre histoire',
        'note_single_select' => 'Sélection unique requise',
    ],
    'copyright' => [
        'label' => 'Copyright',
        'placeholder' => '-- Copyright --',
        'help' => 'Sélectionnez le régime de droits pour cette histoire',
    ],
    'status' => [
        'label' => 'Statut',
        'placeholder' => '-- Statut --',
        'help' => "Choisissez le statut d'avancement de l'histoire (facultatif)",
    ],
    'feedback' => [
        'label' => 'Retour',
        'placeholder' => '-- Retour --',
        'help' => "Choisissez une indication de retour souhaité (facultatif)",
    ],
    'trigger_warnings' => [
        'label' => 'Avertissements de contenu',
        'placeholder' => '-- Sélectionnez au moins 1 avertissement --',
        'help' => 'Sélectionnez les avertissements de contenu pertinents (thèmes centraux OU scènes marquantes, longues et explicites) ou une des autres options ci-après.',
        'listed' => 'Avertissements listés',
        'listed_help' => "Sélectionnez et listez les avertissements pertinents",
        'no_tw' => 'Aucun avertissement',
        'no_tw_help' => "Mon histoire n'a pas d'avertissements de contenu",
        'unspoiled' => 'Avertissements non dévoilés',
        'unspoiled_help' => "Mon histoire contient des avertissements, mais je ne souhaite pas les dévoiler",
        'tooltips' => [
            'no_tw' => "L'auteurice indique qu'il n'y a pas d'avertissements de contenu",
            'unspoiled' => "L'auteurice indique qu'il existe des avertissements, mais préfère ne pas les dévoiler",
            'listed' => 'Avertissements listés',
        ],
        'form_options' => [
            'listed' => 'Oui',
            'no_tw' => 'Aucun',
            'unspoiled' => 'Non dévoilés',
        ],
        'tw_disclosure_placeholder' => 'Avertissements ?',
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
    ],
    'no_results' => 'Aucun résultat',
    'coming_soon' => 'À venir',
    'is_complete' => [
        'label' => 'Cette histoire est terminée',
    ],
    'is_excluded_from_events' => [
        'label' => 'Exclure cette histoire des activités et évènements',
        'help' => 'Si vous cochez cette case, votre histoire ne pourra être sélectionnée dans les futurs évènements du site, ni être utilisée sur le Discord pour des jeux ou activités. Les citations courtes restent autorisées.',
    ],
];
