<?php

return [
    'groups' => [
        'fanart' => 'Fanarts',
    ],
    'settings' => [
        'type_submitted' => 'Quelqu\'un a proposé un fanart sur l\'une de mes histoires',
        'type_accepted' => 'Un de mes fanarts a été accepté',
        'type_declined' => 'Un de mes fanarts a été refusé',
        'type_deleted' => 'Un de mes fanarts a été supprimé par l\'auteurice',
        'type_deleted_by_moderation' => 'Un fanart a été supprimé par la modération',
        'type_audio_replaced' => 'Mon fanart audio a été remplacé par l\'auteurice',
        'type_removed_with_content' => 'Mon fanart a disparu avec son chapitre ou son histoire',
        'type_role_requested' => 'Quelqu\'un demande à devenir fanartiste sur l\'une de mes histoires',
        'type_role_given' => 'Le rôle de fanartiste m\'a été accordé sur une histoire',
        'type_role_declined' => 'Ma demande pour devenir fanartiste a été refusée',
    ],
    'kind' => [
        'image' => 'une image',
        'audio' => 'un son',
    ],
    'submitted' => [
        'display' => '<a href=":actor_url">:actor_name</a> a proposé :kind (« :fanart_title ») sur'
            . ' <a href=":story_url">:story_title</a>',
    ],
    'accepted' => [
        'display' => 'Votre fanart « :fanart_title » a été accepté sur <a href=":story_url">:story_title</a>:comment',
    ],
    'declined' => [
        'display' => 'Votre fanart « :fanart_title » a été refusé sur <a href=":story_url">:story_title</a>:comment',
    ],
    'deleted' => [
        'display' => 'Votre fanart « :fanart_title » a été supprimé sur <a href=":story_url">:story_title</a>',
    ],
    'deleted_by_moderation' => [
        'display' => 'Le fanart « :fanart_title » a été supprimé par la modération sur'
            . ' <a href=":story_url">:story_title</a>',
    ],
    'audio_replaced' => [
        'display' => 'Votre fanart audio « :fanart_title » a été remplacé par l\'auteurice sur'
            . ' <a href=":story_url">:story_title</a> (chapitre « :chapter_title »)',
    ],
    'removed_with_content' => [
        'display' => 'Votre fanart « :fanart_title » a été supprimé avec son chapitre ou son histoire'
            . ' « :story_title »',
    ],
    'role_requested' => [
        'display' => '<a href=":actor_url">:actor_name</a> demande à devenir fanartiste sur'
            . ' <a href=":story_url">:story_title</a>.'
            . ' Vous pouvez accepter la demande via <a href=":collaborators_url">l\'écran des contributeurs</a>.',
    ],
    'role_given' => [
        'display' => 'Vous êtes désormais fanartiste sur <a href=":story_url">:story_title</a>',
    ],
    'role_declined' => [
        'display' => 'Votre demande pour devenir fanartiste sur <a href=":story_url">:story_title</a> a été refusée',
    ],
];
