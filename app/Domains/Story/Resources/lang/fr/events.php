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
    'story_visibility_changed' => [
        'summary' => "Histoire :title (id: :id) visibilité changée : :old → :new.",
    ],
];
