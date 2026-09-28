<?php

declare(strict_types=1);

return [
    'title' => 'Einstellungen',
    'description' => 'Gilt für alle Videos, die dieses Team hochlädt.',
    'censor_license_plates' => [
        'label' => 'Kennzeichen unkenntlich machen',
        'description' => 'Vor der Verteilung werden erkannte Kennzeichen automatisch verpixelt. '
            . 'Das Original bleibt erhalten. Die Erkennung ist eine Hilfe und keine Garantie, '
            . 'einzelne Kennzeichen können durchrutschen.',
    ],
    'censor_faces' => [
        'label' => 'Gesichter unkenntlich machen',
        'description' => 'Vor der Verteilung werden erkannte Gesichter automatisch verpixelt.',
    ],
];
