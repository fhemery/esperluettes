<?php

return [
    'display_name_changed' => [
        // Example: "Ancien → Nouveau (id=123)"
        'summary' => ":old → :new (id=:id)",
    ],
    'avatar_changed' => [
        'summary' => "Avatar modifié",
        'summary_deleted' => "Avatar supprimé",
    ],
    'bio_updated' => [
        'summary' => "Biographie mise à jour",
    ],
    'avatar_moderated' => [
        'summary' => "Avatar supprimé par la modération (userId = :userId)",
    ],
];
