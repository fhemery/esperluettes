<?php

return [
    'created_success' => 'Chapitre créé avec succès.',
    'updated_success' => 'Chapitre mis à jour avec succès.',
    'deleted_success' => 'Chapitre supprimé avec succès.',

    'back_to_story' => 'Retour à l\'histoire',
    'author_note' => 'Note de l\'auteur',

    'create' => [
        'title' => 'Nouveau chapitre',
        'heading' => 'Ajouter un chapitre à ":story"',
    ],

    'edit' => [
        'title' => 'Modifier : :title',
        'heading' => 'Modifier un chapitre de ":story"',
    ],

    'form' => [
        'title' => [
            'label' => 'Titre',
            'placeholder' => 'Titre du chapitre',
        ],
        'author_note' => [
            'label' => 'Note de l\'Esperluette',
            'placeholder' => 'Utilisez ces notes librement. Elles peuvent vous permettre de demander des retours sur certains points particuliers, de donner à vos lectrices et lecteurs une précision sur votre processus d\'écriture (par exemple un délai long depuis le chapitre précédent…) ou d\'ajouter des avertissements spécifiques à ce chapitre.',
        ],
        'content' => [
            'label' => 'Contenu',
        ],
        'published' => [
            'label' => 'Publié',
            'help' => [
                'label' => 'Statut de publication',
                'text' => 'Décochez si vous souhaitez garder le chapitre en brouillon, ou le cacher temporairement.',
            ],
        ],
        'cancel' => 'Annuler',
        'submit' => 'Créer le chapitre',
        'update' => 'Enregistrer les modifications',
    ],

    'sections' => [
        'chapters' => 'Chapitres',
        'add_chapter' => 'Chapitre',
    ],

    'list' => [
        'empty' => 'Aucun chapitre disponible pour le moment.',
        'draft' => 'Brouillon',
    ],
    'actions' => [
        'cancel' => 'Annuler',
        'edit' => 'Éditer le chapitre',
        'delete' => 'Supprimer le chapitre',
        'reorder' => 'Ordre',
        'save_order' => "Enregistrer l'ordre",
        'move_up' => 'Monter',
        'move_down' => 'Descendre',
        'mark_as_read' => 'Marquer comme lu',
        'marked_read' => 'Lu',
    ],
    'reorder_success' => "Ordre des chapitres enregistré avec succès.",
    'navigation' => [
        'previous' => 'Chapitre précédent',
        'next' => 'Chapitre suivant',
        'mark_read' => 'Marquer comme lu (bientôt)'
    ],
    'reads' => [
        'label' => 'Lectures',
        'tooltip' => "Nombre de lectures effectuées par des utilisateurs connectés.",
    ],
    'words' => [
        'label' => 'Mots',
        'tooltip' => "Nombre de mots de ce chapitre (contenu uniquement).",
    ],
    'no_chapter_credits_left' => 'Vous devez lire et commenter davantage pour pouvoir publier un nouveau chapitre.',
];
