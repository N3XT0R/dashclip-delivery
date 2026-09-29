<?php

declare(strict_types=1);

return [
    'original' => [
        'label' => 'Originalfassung',
        'action' => 'Original ausliefern',
        'confirm' => 'Ab jetzt bekommen Kanäle die unbearbeitete Fassung mit sichtbaren Kennzeichen. '
            . 'Die Vorschauen werden neu erzeugt. Kanäle, die das Video bereits heruntergeladen '
            . 'haben, behalten ihre Fassung und werden per Mail über den Wechsel informiert.',
    ],
    'blurred' => [
        'label' => 'Verpixelte Fassung',
        'action' => 'Verpixelte Fassung ausliefern',
        'confirm' => 'Ab jetzt bekommen Kanäle die Fassung mit unkenntlich gemachten Kennzeichen. '
            . 'Die Vorschauen werden neu erzeugt. Kanäle, die das Video bereits heruntergeladen '
            . 'haben, behalten ihre Fassung und werden per Mail über den Wechsel informiert.',
    ],
    'switched' => 'Fassung gewechselt',
    'switched_hint' => 'Der Einsender hat am :date entschieden, welche Fassung ausgeliefert wird. '
        . 'Eine früher heruntergeladene Datei kann davon abweichen.',
    'notification' => [
        'title' => 'Fassung gewechselt',
        'body' => 'Für :video wird ab jetzt die :version ausgeliefert.',
    ],
];
