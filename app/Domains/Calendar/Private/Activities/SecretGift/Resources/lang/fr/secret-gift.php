<?php

declare(strict_types=1);

return [
    // Tabs
    'tab_my_gift' => 'Mon cadeau à préparer',
    'tab_received_gift' => 'Mon cadeau reçu',

    // States
    'not_participant' => 'Vous n\'êtes pas inscrit(e) à cette activité.',
    'waiting_for_start' => 'L\'activité n\'a pas encore commencé. Patientez encore un peu !',
    'no_assignment_yet' => 'L\'attribution des cadeaux n\'a pas encore été effectuée.',
    'gift_will_be_revealed' => 'Votre cadeau sera révélé quand l\'événement sera terminé.',
    'not_active' => 'Cette activité n\'est pas active.',
    'activity_not_active' => 'L\'activité est terminée. Vous ne pouvez plus modifier votre cadeau.',

    // Gift Preparation
    'your_recipient' => 'Votre destinataire',
    'their_preferences' => 'Ses préférences',
    'no_preferences' => 'Aucune préférence renseignée.',
    'unknown_user' => 'Utilisateur inconnu',
    'create_your_gift' => 'Créez votre cadeau',
    'mode_text' => 'Texte',
    'mode_image' => 'Image',
    'mode_sound' => 'Son',
    'text_placeholder' => 'Écrivez votre cadeau ici...',
    'current_image' => 'Image actuelle :',
    'your_gift_image' => 'Votre image cadeau',
    'upload_image' => 'Téléverser une image',
    'upload_sound' => 'Téléverser un fichier audio',
    'image_help' => 'JPG ou PNG, 5 Mo maximum',
    'sound_help' => 'MP3, 10 Mo maximum',
    'save_gift' => 'Enregistrer',
    'gift_saved' => 'Votre cadeau a bien été enregistré !',
    'time_remaining' => 'Temps restant : ',

    // Gift Reveal
    'gift_from' => 'Cadeau de la part de...',
    'your_gift' => 'Votre cadeau',
    'no_gift_received' => 'Malheureusement, aucun cadeau n\'a été déposé pour vous.',
    'gift_image' => 'Image cadeau',
    'gift_sound' => 'Message audio',
    'download_image' => 'Télécharger l\'image',
    'download_sound' => 'Télécharger le son',
    'browser_no_support' => 'Votre navigateur ne supporte pas l\'élément audio.',

    // Enrolment (join / edit preferences / leave / participant list)
    'enrolment' => [
        'join_title' => 'Inscrivez-vous à cet échange de cadeaux',
        'join_button' => 'Je m\'inscris',
        'preferences_label' => 'Vos préférences',
        'preferences_hint' => 'Seule la personne qui vous offrira un cadeau verra ces informations.',
        'save_preferences' => 'Enregistrer mes préférences',
        'leave_button' => 'Me désinscrire',
        'leave_confirm_title' => 'Vous désinscrire de cette activité ?',
        'leave_confirm_body' => 'Votre inscription et vos préférences seront supprimées. Vous pourrez vous réinscrire tant que les inscriptions sont ouvertes.',
        'leave_confirm_cancel' => 'Annuler',
        'leave_confirm_confirm' => 'Confirmer la désinscription',
        'registration_closed' => 'Les inscriptions sont fermées.',
        'registration_closed_participant' => 'Les inscriptions sont fermées : vos préférences ne sont plus modifiables.',
        'participants_title' => 'Les inscrit(e)s',
        'participants_alone' => 'Vous êtes pour le moment la seule personne inscrite.',
    ],

    // Preferences template (default content for subscription)
    'preferences_template' => '<p><strong>Ce que j\'aime :</strong></p><p></p><p><strong>Ce que je n\'aime pas :</strong></p><p></p><p><strong>Fanart autorisé :</strong> Oui / Non</p><p><strong>Genres préférés :</strong></p><p></p><p><strong>Autres informations :</strong></p><p></p>',

    // Admin configuration panel
    'config' => [
        'section_title' => 'Cadeau surprise',
        'registration_ends_at' => 'Fin des inscriptions',
        'registration_hint' => 'Les inscriptions ouvrent à l\'ouverture de l\'activité et ferment à cette date, au plus tard au début de l\'activité.',

        // Shuffle panel
        'shuffle_title' => 'Attribution des cadeaux',
        'participants_count' => ':count inscrit(e)s',
        'participants_empty' => 'Personne n\'est inscrit(e) pour le moment.',
        'shuffle_button' => 'Lancer le tirage',
        'already_shuffled' => 'Le tirage a déjà été effectué : chaque inscrit(e) a son destinataire.',
        'not_shuffled_yet' => 'Le tirage n\'a pas encore été effectué.',
        'shuffle_disabled_not_enough' => 'Il faut au moins 2 inscrit(e)s pour lancer le tirage.',
        'shuffle_disabled_active' => 'L\'activité a commencé : le tirage ne peut plus être relancé.',
        'shuffle_confirm_title' => 'Lancer le tirage ?',
        'shuffle_confirm_body' => 'Attention : le tirage supprime toutes les attributions existantes et les cadeaux déjà déposés (textes, images et sons) sont définitivement perdus. Les inscriptions seront closes.',
        'shuffle_confirm_cancel' => 'Annuler',
        'shuffle_confirm_confirm' => 'Lancer le tirage',
    ],

    // Flash messages
    'flash' => [
        'joined' => 'Vous êtes bien inscrit(e) à cette activité !',
        'preferences_saved' => 'Vos préférences ont bien été enregistrées.',
        'left' => 'Vous n\'êtes plus inscrit(e) à cette activité.',
        'shuffled' => 'Le tirage a été effectué pour :count participant(e)s.',
        'shuffle_not_enough_participants' => 'Il faut au moins 2 inscrit(e)s pour lancer le tirage.',
    ],

    // Validation messages
    'validation' => [
        'preferences_max' => 'Vos préférences sont trop longues (maximum 65 535 caractères).',
        'registration_ends_before_preview_start' => 'La fin des inscriptions ne peut pas précéder l\'ouverture de l\'activité.',
        'registration_ends_after_activity_start' => 'La fin des inscriptions ne peut pas dépasser le début de l\'activité.',
        'gift_text_max' => 'Le texte du cadeau est trop long (maximum 65 535 caractères).',
        'gift_image_mimes' => 'L\'image doit être au format JPG ou PNG.',
        'gift_image_max' => 'L\'image ne doit pas dépasser 5 Mo.',
        'gift_sound_mimes' => 'Le fichier audio doit être au format MP3.',
        'gift_sound_max' => 'Le fichier audio ne doit pas dépasser 10 Mo.',
    ],
];
