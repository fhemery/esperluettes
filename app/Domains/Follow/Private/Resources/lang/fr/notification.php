<?php

return [
    'groups' => [
        'follow' => 'Suivi',
    ],
    'settings' => [
        'type_new_follower' => 'Une Esperluette vous suit',
        'type_new_story' => 'Une Esperluette que vous suivez a publié une nouvelle histoire',
    ],
    'new_follower' => [
        'display' => '<a href=":follower_url">:follower_name</a> vous suit',
    ],
    'new_story' => [
        'display' => '<a href=":author_url">:author_name</a> a publié une nouvelle histoire : <a href=":story_url">:story_title</a>',
    ],
];
