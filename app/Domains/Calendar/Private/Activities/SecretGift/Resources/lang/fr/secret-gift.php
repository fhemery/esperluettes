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
    'text_placeholder' => 'Écrivez votre cadeau ici...',
    'current_image' => 'Image actuelle :',
    'your_gift_image' => 'Votre image cadeau',
    'upload_image' => 'Téléverser une image',
    'image_help' => 'JPG ou PNG, 5 Mo maximum',
    'save_gift' => 'Enregistrer',
    'gift_saved' => 'Votre cadeau a bien été enregistré !',

    // Gift Reveal
    'gift_from' => 'Cadeau de la part de...',
    'your_gift' => 'Votre cadeau',
    'no_gift_received' => 'Malheureusement, aucun cadeau n\'a été déposé pour vous.',
    'gift_image' => 'Image cadeau',

    // Preferences template (default content for subscription)
    'preferences_template' => '<p><strong>Ce que j\'aime :</strong></p><p></p><p><strong>Ce que je n\'aime pas :</strong></p><p></p><p><strong>Fanart autorisé :</strong> Oui / Non</p><p><strong>Genres préférés :</strong></p><p></p><p><strong>Autres informations :</strong></p><p></p>',

    // Validation messages
    'validation' => [
        'gift_text_max' => 'Le texte du cadeau est trop long (maximum 65 535 caractères).',
        'gift_image_mimes' => 'L\'image doit être au format JPG ou PNG.',
        'gift_image_max' => 'L\'image ne doit pas dépasser 5 Mo.',
    ],
];
