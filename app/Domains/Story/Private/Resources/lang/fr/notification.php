<?php

return [
    // Comment notifications
    'root_comment.posted' => '<a href=":author_url">:author_name</a> a commenté le chapitre "<a href=":chapter_url_with_comment">:chapter_name</a>"',
    'reply_comment.posted' => '<a href=":author_url">:author_name</a> a répondu à un commentaire sur le chapitre "<a href=":chapter_url_with_comment">:chapter_name</a>"',
    'root_comment.posted_with_story' => '<a href=":author_url">:author_name</a> a commenté le chapitre "<a href=":chapter_url_with_comment">:chapter_name</a>" de l\'histoire "<a href=":story_url">:story_name</a>"',
    'reply_comment.posted_with_story' => '<a href=":author_url">:author_name</a> a répondu à un commentaire sur le chapitre "<a href=":chapter_url_with_comment">:chapter_name</a>" de l\'histoire "<a href=":story_url">:story_name</a>"',

    // Chapter notifications for co-authors
    'chapter.created' => '<a href=":user_url">:user_name</a> a ajouté un nouveau chapitre "<a href=":chapter_url">:chapter_name</a>" à l\'histoire "<a href=":story_url">:story_name</a>"',
    'chapter.updated' => '<a href=":user_url">:user_name</a> a modifié le chapitre "<a href=":chapter_url">:chapter_name</a>" de l\'histoire "<a href=":story_url">:story_name</a>"',
    'chapter.deleted' => '<a href=":user_url">:user_name</a> a supprimé le chapitre ":chapter_name" de l\'histoire "<a href=":story_url">:story_name</a>"',

    // Collaborator role notifications
    'collaborator.role_given.author' => '<a href=":user_url">:user_name</a> vous a donné le rôle de co-auteurice sur l\'histoire "<a href=":story_url">:story_name</a>"',
    'collaborator.role_given.beta_reader' => '<a href=":user_url">:user_name</a> vous a donné le rôle de bêta-lecteurice sur l\'histoire "<a href=":story_url">:story_name</a>"',
    'collaborator.removed' => '<a href=":user_url">:user_name</a> vous a retiré le rôle de bêta-lecteurice sur l\'histoire "<a href=":story_url">:story_name</a>"',
    'collaborator.left' => '<a href=":user_url">:user_name</a> ne collabore plus sur l\'histoire "<a href=":story_url">:story_name</a>"',

    'settings' => [
        'group_comments'      => 'Commentaires',
        'group_collaboration' => 'Collaboration',

        'type_chapter_comment'          => 'Commentaire sur un chapitre',
        'type_chapter_root_comment'     => 'Commentaire sur votre histoire',
        'type_chapter_reply_comment'    => 'Réponse à votre commentaire',
        'type_coauthor_chapter_created' => 'Chapitre créé par un co-auteur',
        'type_coauthor_chapter_updated' => 'Chapitre modifié par un co-auteur',
        'type_coauthor_chapter_deleted' => 'Chapitre supprimé par un co-auteur',
        'type_collaborator_role_given'  => 'Rôle de collaborateur accordé',
        'type_collaborator_removed'     => 'Collaborateur retiré',
        'type_collaborator_left'        => 'Collaborateur parti',
    ],
];