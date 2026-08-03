<?php

return [
    // The notification group every Calendar activity shares. It is registered
    // by the first activity that notifies (the quote contest); the label is
    // the domain's, so a second activity reuses it rather than adding a group.
    'groups' => [
        'calendar' => 'Calendrier',
    ],
];
