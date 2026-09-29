<?php

declare(strict_types=1);

return [
    'blur_now' => [
        'action' => 'Kennzeichen unkenntlich machen',
        'confirm' => 'Wir suchen die Kennzeichen in diesem Video und verpixeln sie. Das dauert je '
            . 'nach Länge einige Minuten und läuft im Hintergrund. Dein Original bleibt erhalten, '
            . 'und du kannst danach jederzeit zwischen beiden Fassungen wechseln.',
        'queued' => 'Das Video wird im Hintergrund bearbeitet. Du bekommst Bescheid, sobald es fertig ist.',
    ],
    'blur_done' => [
        'title' => 'Kennzeichen unkenntlich gemacht',
        'body' => 'Für :video wird ab jetzt die verpixelte Fassung ausgeliefert. Das Original bleibt erhalten.',
    ],
    'blur_failed' => [
        'title' => 'Unkenntlich machen fehlgeschlagen',
        'body' => 'Für :video hat es nicht geklappt. Die ausgelieferte Fassung ist unverändert.',
    ],
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
