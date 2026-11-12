<?php

declare(strict_types=1);

// Prototype only: the tab bar groups the rooms. This grouping does not exist in the data model.
// Every room id must appear exactly once.
return [
    'play' => ['label' => 'Play', 'rooms' => ['navigation-room', 'crows-nest', 'soldier-dock', 'usopp-factory']],
    'learn' => ['label' => 'Learn', 'rooms' => ['figurehead', 'sick-bay', 'library']],
    'community' => ['label' => 'Crew', 'rooms' => ['helm', 'aquarium-bar', 'galley']],
];
