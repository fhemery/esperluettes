<?php

return [
    'story_created' => [
        'summary' => "Histoire :title (id: :id) créée (:visibility).",
    ],
    'story_updated' => [
        'summary' => "Histoire :title (id: :id) mise à jour.",
    ],
    'story_deleted' => [
        'summary' => "Histoire :title (id: :id) supprimée.",
    ],
    'chapter_created_published' => [
        'summary' => "Chapitre :title (id: :id) créé (publié) pour l'histoire :storyId.",
    ],
    'chapter_created_unpublished' => [
        'summary' => "Chapitre :title (id: :id) créé (non publié) pour l'histoire :storyId.",
    ],
    'chapter_updated' => [
        'summary' => "Chapitre :title (id: :id) mis à jour pour l'histoire :storyId.",
    ],
    'chapter_published' => [
        'summary' => "Chapitre :title (id: :id) publié pour l'histoire :storyId.",
    ],
    'chapter_unpublished' => [
        'summary' => "Chapitre :title (id: :id) dépublié pour l'histoire :storyId.",
    ],
    'chapter_deleted' => [
        'summary' => "Chapitre :title (id: :id) supprimé pour l'histoire :storyId.",
    ],
    'story_visibility_changed' => [
        'summary' => "Histoire :title (id: :id) visibilité changée : :old → :new.",
    ],
    'story_moderated_as_private' => [
        'summary' => "Histoire rendue privée par la modération (id: :id, :title).",
    ],
    'story_summary_moderated' => [
        'summary' => "Résumé vidé par la modération (id: :id, :title).",
    ],
    'chapter_content_moderated' => [
        'summary' => "Texte vidé par la modération (id: :id, :title).",
    ],
    'chapter_unpublished_by_moderation' => [
        'summary' => "Chapitre dépublié par la modération (id: :id, :title).",
    ],
];
