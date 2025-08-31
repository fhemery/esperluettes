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
        'title' => 'Modifier le chapitre',
        'heading' => 'Modifier un chapitre de ":story"',
    ],

    'form' => [
        'title' => [
            'label' => 'Titre',
            'placeholder' => 'Titre du chapitre',
        ],
        'author_note' => [
            'label' => 'Note de l\'auteur',
            'help' => 'Optionnel. 1000 caractères maximum (après suppression du HTML).',
            'note_limit' => 'Jusqu\'à 1000 caractères (après suppression du HTML).',
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
        'add_chapter' => 'Ajouter un chapitre',
    ],

    'list' => [
        'empty' => 'Aucun chapitre disponible pour le moment.',
        'draft' => 'Brouillon',
    ],
    'actions' => [
        'edit' => 'Éditer le chapitre',
        'delete' => 'Supprimer le chapitre',
        'reorder' => 'Réorganiser',
        'save_order' => "Enregistrer l'ordre",
        'cancel' => 'Annuler',
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
];
