<?php

return [
    // <reader> a ajouté <story> à sa Pile à Lire
    'story_added' => '<a href=":reader_url">:reader_name</a> a ajouté <a href=":story_url">:story_name</a> à sa Pile à Lire',
    // <author> a publié un nouveau chapitre <chapter> in <story>
    'chapter_published' => '<a href=":author_url">:author_name</a> a publié un nouveau chapitre <a href=":chapter_url">:chapter_name</a> dans <a href=":story_url">:story_name</a>',
    // <author> a retiré le chapitre <chapter> in <story>
    'chapter_unpublished' => '<a href=":author_url">:author_name</a> a retiré le chapitre <a href=":chapter_url">:chapter_name</a> dans <a href=":story_url">:story_name</a>',
];
