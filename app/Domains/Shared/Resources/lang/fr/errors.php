<?php

return [
    '404' => [
        'title' => 'Page introuvable',
        'description' => "La page que vous recherchez n'existe pas ou n'est pas accessible.",
        'guest_additional_description' => "Si vous pensez avoir accès à cette page, connectez-vous avec le bouton ci-dessous"
    ],
    '419' => [
        'title' => 'Session expirée',
        'description' => "Votre session a changé ou a expiré. Si vous étiez en train d’envoyer un formulaire, revenez à la page précédente pour récupérer vos données.",
    ],
    '500' => [
        'title' => 'Une erreur est survenue',
        'description' => "Notre système a enregistré l’erreur et l’a transmise aux administrateurs.",
        'additional_description' => "Si cela se reproduit, vous pouvez également nous le signaler sur Discord.",
    ],
    'actions' => [
        'back' => '← Retour',
        'back_home' => 'Accueil',
        'login_to_continue' => 'Se connecter pour continuer',
        'go_to_dashboard' => 'Tableau de bord',
    ],
];

