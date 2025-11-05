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
    // <author> a retiré l'histoire <story title> (no link)
    'story_unpublished' => '<a href=":author_url">:author_name</a> a retiré l\'histoire :story_name',
    // <author> a republié l'histoire <story title> (with link)
    'story_republished' => '<a href=":author_url">:author_name</a> a republié l\'histoire <a href=":story_url">:story_name</a>',
];
