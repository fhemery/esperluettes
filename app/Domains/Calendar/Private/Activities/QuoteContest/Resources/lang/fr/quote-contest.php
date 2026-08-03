<?php

return [
    'tab_my_quotes' => 'Mes citations',
    'tab_votes' => 'Votes',
    'tab_results' => 'Résultats',

    'phase' => [
        'before_start' => 'Les soumissions ouvriront le :date.',
        'before_start_undated' => 'Les soumissions ne sont pas encore ouvertes.',
        'submissions' => 'Les soumissions sont ouvertes jusqu\'au :date.',
        'interlude' => 'Les soumissions sont closes. Les votes ouvriront le :date.',
        'voting' => 'Les soumissions sont closes : les votes sont en cours.',
        'ended' => 'Le concours est terminé.',
    ],

    'my_quotes' => [
        'no_categories' => 'Ce concours n\'a pas encore de catégorie : il n\'y a rien à quoi soumettre pour l\'instant.',
        'categories_title' => 'Catégories',
        'your_entry' => 'Votre citation',
        'no_entry' => 'Vous n\'avez rien soumis dans cette catégorie.',
        'picker_title' => 'Votre carnet de citations',
        'picker_empty' => 'Votre carnet de citations est vide : surlignez un passage dans un chapitre pour commencer à en collectionner.',
        'filter_label' => 'Filtrer vos citations',
        'filter_placeholder' => 'Un passage, une histoire, un chapitre…',
        'ineligible_prefix' => 'Non éligible :',

        'select_quote' => 'Choisir cette citation',
        'selected_quote' => 'Citation choisie',
        'pick_one_first' => 'Choisissez d\'abord une citation dans votre carnet, plus bas.',
        'submit' => 'Soumettre la citation choisie',
        'replace' => 'Remplacer par la citation choisie',
        'withdraw' => 'Retirer du concours',
        'replace_confirm_title' => 'Remplacer votre citation',
        'replace_confirm_body' => 'Dans « :category », cette citation est actuellement en lice :',
        'replace_confirm_new' => 'Elle sera définitivement remplacée par celle que vous venez de choisir :',
        'replace_confirm_cancel' => 'Annuler',
        'replace_confirm_confirm' => 'Remplacer',
    ],

    'votes' => [
        'opens_at' => 'Les votes ouvriront le :date.',
        'opens_undated' => 'Les votes ne sont pas encore ouverts.',
        'open_until' => 'Les votes sont ouverts jusqu\'au :date.',
        'open_undated' => 'Les votes sont ouverts.',
        'closed' => 'Les votes sont clos. Vous retrouvez ci-dessous la citation que vous avez choisie ; les résultats ne sont pas publiés ici.',
        'no_categories' => 'Ce concours n\'a pas de catégorie : il n\'y a rien à départager.',
        'no_entries' => 'Aucune citation n\'a été soumise dans cette catégorie.',
        'voted' => 'Vous avez voté',
        'not_voted' => 'Vous n\'avez pas voté',
        'choose_entry' => 'Voter pour cette citation',
        // A prefix, with the names printed beside it: they are data, not part
        // of the sentence — the same shape as Story's own `by`.
        'authors_by' => 'Par',
        'cast' => 'Voter',
        'change' => 'Modifier mon vote',
    ],

    'ineligible' => [
        'private_story' => 'Histoire privée',
        'excluded_from_events' => 'Histoire exclue des événements',
    ],

    'config' => [
        'section_title' => 'Concours de citations',
        'timeline_hint' => 'Le concours suit les dates de l\'activité : les soumissions ouvrent à son début, les votes ferment à sa fin.',
        'submissions_start_at' => 'Début des soumissions',
        'submissions_end_at' => 'Fin des soumissions',
        'votes_start_at' => 'Début des votes',
        'votes_end_at' => 'Fin des votes',
        'mirrored_hint' => 'Repris des dates de l\'activité, non modifiable ici.',

        'categories_title' => 'Catégories',
        'categories_after_save' => 'Les catégories pourront être ajoutées une fois l\'activité enregistrée.',
        'categories_empty' => 'Aucune catégorie pour l\'instant : le concours reste un brouillon valide, mais les participants n\'auront rien à quoi soumettre.',
        'category_title' => 'Titre',
        'category_description' => 'Description',
        'category_position' => 'Ordre',
        'category_entries_count' => 'Citations soumises : :count',
        'add_category_title' => 'Ajouter une catégorie',
        'add_category' => 'Ajouter',
        'save_category' => 'Enregistrer',
        'delete_category' => 'Supprimer',
        'delete_confirm_title' => 'Supprimer la catégorie',
        'delete_confirm_body' => 'La catégorie « :title » sera définitivement supprimée. Une catégorie qui contient déjà des citations ne peut pas être supprimée.',
        'delete_confirm_cancel' => 'Annuler',
        'delete_confirm_confirm' => 'Supprimer',
    ],

    'flash' => [
        'category_created' => 'Catégorie ajoutée.',
        'category_updated' => 'Catégorie mise à jour.',
        'category_deleted' => 'Catégorie supprimée.',
        'entry_submitted' => 'Votre citation est en lice.',
        'entry_withdrawn' => 'Votre citation a été retirée du concours.',
        'vote_cast' => 'Votre vote a été enregistré.',
        'category_not_empty' => 'Impossible de supprimer cette catégorie : elle contient déjà des citations, retirées ou non.',
    ],

    'validation' => [
        'invalid_date' => 'Cette date n\'est pas valide.',
        'submissions_end_before_activity_start' => 'La fin des soumissions ne peut pas précéder le début de l\'activité.',
        'votes_start_before_submissions_end' => 'Le début des votes ne peut pas précéder la fin des soumissions.',
        'votes_start_after_activity_end' => 'Le début des votes ne peut pas dépasser la fin de l\'activité.',
        'category_title_required' => 'Le titre de la catégorie est obligatoire.',
        'category_title_max' => 'Le titre de la catégorie ne peut pas dépasser 160 caractères.',
        'category_description_max' => 'La description de la catégorie ne peut pas dépasser 2000 caractères.',
        'category_position_integer' => 'L\'ordre doit être un nombre entier.',
        'category_required' => 'Choisissez une catégorie.',
        'quote_required' => 'Choisissez une citation à soumettre.',
        'entry_required' => 'Choisissez la citation pour laquelle vous votez.',
    ],
];
