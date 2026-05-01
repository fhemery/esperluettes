<?php

return [
    // <reader> a ajouté <story> à sa Pile à Lire
    'story_added' => '<a href=":reader_url">:reader_name</a> a ajouté <a href=":story_url">:story_name</a> à sa Pile à Lire',
    // <author> a publié un nouveau chapitre <chapter> in <story>
    'chapter_published' => '<a href=":author_url">:author_name</a> a publié un nouveau chapitre <a href=":chapter_url">:chapter_name</a> dans <a href=":story_url">:story_name</a>',
    // <author> a retiré le chapitre <chapter> in <story>
    'chapter_unpublished' => '<a href=":author_url">:author_name</a> a retiré le chapitre <a href=":chapter_url">:chapter_name</a> dans <a href=":story_url">:story_name</a>',
    // <author> a supprimé l'histoire <story title> (story title without link)
    'story_deleted' => '<a href=":author_url">:author_name</a> a supprimé l\'histoire :story_name',
     // <story title> (story title without link)
    'story_deleted_unknown_user' => 'L\'histoire :story_name a été supprimée',
    // <author> a retiré l'histoire <story title> (no link)
    'story_unpublished' => '<a href=":author_url">:author_name</a> a retiré l\'histoire :story_name',
    // <author> a republié l'histoire <story title> (with link)
    'story_republished' => '<a href=":author_url">:author_name</a> a republié l\'histoire <a href=":story_url">:story_name</a>',
    // <author> a marqué l'histoire <story title> comme terminée (with link)
    'story_completed' => '<a href=":author_url">:author_name</a> a marqué l\'histoire <a href=":story_url">:story_name</a> comme terminée',

    'settings' => [
        'group_readlist'                     => 'Pile à Lire (PAL)',
        'type_readlist_story_added'         => 'Une de mes histoires a été ajoutée à une PAL',
        'type_readlist_chapter_published'   => "Un nouveau chapitre d'une histoire de ma PAL a été publié",
        'type_readlist_chapter_unpublished' => "Un chapitre d'une histoire de ma PAL a été dépublié",
        'type_readlist_story_deleted'       => "Une histoire de ma PAL a été supprimée",
        'type_readlist_story_unpublished'   => "Une histoire de ma PAL a été dépubliée",
        'type_readlist_story_republished'   => "Une histoire de ma PAL a été republiée",
        'type_readlist_story_completed'     => "Une histoire de ma PAL a été marquée comme terminée",
    ],
];
