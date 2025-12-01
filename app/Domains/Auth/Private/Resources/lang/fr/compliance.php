<?php

return [
    'title' => 'Pas si vite !',
    'description' => '<p>Vous avez déclaré à l\'inscription avoir <strong>moins de 15 ans</strong>. La loi nous oblige donc à vous demander un formulaire rempli par votre représentant·e légal·e (soit votre parent, en général) !</p>'
        . '<p>En effet, en vertu de l\'article 45 de la loi Informatique et Libertés et de l\'article 8 du règlement (UE) 2016/679, toute personne de moins de 15 ans doit fournir la preuve que son ou sa représentant·e légal·e consent au traitement des données à caractère personnel nécessaires au bon fonctionnement du site.</p>',

    'instructions_first_line' => 'Vous devez donc télécharger le formulaire ci-dessous :',
    'download_button_text' => 'Télécharger le formulaire',

    'instructions_second_line' => 'et le téléverser dûment complété au format .pdf ci-dessous :',
    'upload_button_text' => 'Téléverser',
    'logout_button_text' => 'Se déconnecter',

    'note' => 'Notez bien que l\'équipe de modération du Jardin des Esperluettes pourra examiner le document déposé et, le trouvant incomplet ou frauduleux, sévir en conséquence.',

    // Parental authorization file upload error messages
    'parental_authorization' => [
        'attribute' => 'autorisation parentale',
        'required' => 'Le fichier d\'autorisation parentale est obligatoire.',
        'file' => 'Veuillez téléverser un fichier valide.',
        'mimes' => 'Le fichier doit être au format PDF uniquement.',
        'min' => 'Le fichier ne peut pas être vide.',
        'max' => 'Le fichier ne doit pas dépasser 5 Mo.',
        'uploaded' => 'Le téléversement du fichier a échoué. Veuillez réessayer.',
        'upload_success' => 'Le téléversement du fichier a été effectué avec succès.',
    ],
];