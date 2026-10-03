<?php

return [
    'errors' => [
        'not_allowed' => 'Vous ne pouvez pas annoter ce contenu.',
        'invalid' => 'Une annotation est invalide : vérifiez qu\'elle n\'est pas vide et qu\'elle ne dépasse pas la longueur autorisée.',
        'stale' => 'Cette annotation n\'existe plus. Retirez-la de vos modifications.',
        'reply_not_processable' => 'Seule une annotation principale peut être marquée comme traitée.',
        'body_blank' => 'Votre annotation est vide.',
        'body_too_long' => 'Votre annotation est trop longue (:max caractères maximum).',
        'highlight_too_long' => 'Le passage sélectionné est trop long (:max caractères maximum).',
        'highlight_multi_block' => 'La sélection doit rester dans un même bloc de texte.',
    ],
    'toolbar_button' => [
        'label' => 'Annoter',
        'title' => 'Annoter ce passage',
    ],
    'form' => [
        'title' => 'Annoter le passage',
        'body_label' => 'Votre annotation',
        'save' => 'Enregistrer',
        'cancel' => 'Annuler',
    ],
    'banner' => [
        'text' => '{1} :count annotation, écrivez votre commentaire pour la sauvegarder|[2,*] :count annotations, écrivez votre commentaire pour les sauvegarder',
        'show' => 'Voir les annotations',
    ],
    'drafts_modal' => [
        'title' => 'Vos annotations en attente',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'close' => 'Fermer',
        'empty' => 'Aucune annotation en attente.',
    ],
    'button' => '{1} :count annotation|[2,*] :count annotations',
    'server_modal' => [
        'title' => 'Annotations',
        'close' => 'Fermer',
        'loading' => 'Chargement…',
        'empty' => 'Aucune annotation.',
        'processed' => 'Traitée',
        'mark_processed' => 'Marquer comme traitée',
        'mark_unprocessed' => 'Marquer comme non traitée',
        'delete' => 'Supprimer l\'annotation',
        'load_error' => 'Impossible de charger les annotations. Réessayez plus tard.',
        'action_error' => 'L\'action n\'a pas pu aboutir. Réessayez.',
    ],
];
