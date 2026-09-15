<?php

declare(strict_types=1);

/**
 * Weekly editorial queue: one bilingual article per week, starting a week after the seeder runs.
 * The article bodies live next to this file in posts/<key>.<locale>.md, the cover artwork in
 * images/covers/<key>.webp. Order matters: it defines the publication week of each article.
 * Categories and tags are addressed by their initial slug, so existing records are reused.
 */
return [
    'categories' => [
        'news' => ['icon' => 'news', 'translations' => [
            'de' => ['name' => 'Neuigkeiten', 'slug' => 'neuigkeiten', 'description' => 'Neue Funktionen und Entwicklungen rund um DashClip Delivery.'],
            'en' => ['name' => 'News', 'slug' => 'news', 'description' => 'New features and developments at DashClip Delivery.'],
        ]],
        'dashcam-knowledge' => ['icon' => 'camera', 'translations' => [
            'de' => ['name' => 'Dashcam-Wissen', 'slug' => 'dashcam-wissen', 'description' => 'Verständliche Grundlagen rund um Dashcams und ihre Aufnahmen.'],
            'en' => ['name' => 'Dashcam knowledge', 'slug' => 'dashcam-knowledge', 'description' => 'Accessible explanations of dashcams and their recordings.'],
        ]],
        'submission-tips' => ['icon' => 'video', 'translations' => [
            'de' => ['name' => 'Tipps zum Einsenden', 'slug' => 'tipps-zum-einsenden', 'description' => 'Orientierung vom ersten Upload bis zur Auswahl der passenden Kanäle.'],
            'en' => ['name' => 'Submission tips', 'slug' => 'submission-tips', 'description' => 'Guidance from your first upload to choosing the right channels.'],
        ]],
    ],
    'tags' => [
        'dashclip-delivery' => [
            'de' => ['name' => 'DashClip Delivery', 'slug' => 'dashclip-delivery'],
            'en' => ['name' => 'DashClip Delivery', 'slug' => 'dashclip-delivery'],
        ],
        'updates' => [
            'de' => ['name' => 'Updates', 'slug' => 'updates'],
            'en' => ['name' => 'Updates', 'slug' => 'updates'],
        ],
        'getting-started' => [
            'de' => ['name' => 'Erste Schritte', 'slug' => 'erste-schritte'],
            'en' => ['name' => 'Getting started', 'slug' => 'getting-started'],
        ],
        'dashcams' => [
            'de' => ['name' => 'Dashcams', 'slug' => 'dashcams'],
            'en' => ['name' => 'Dashcams', 'slug' => 'dashcams'],
        ],
        'clip-submission' => [
            'de' => ['name' => 'Clips einsenden', 'slug' => 'clips-einsenden'],
            'en' => ['name' => 'Submitting clips', 'slug' => 'submitting-clips'],
        ],
        'account' => [
            'de' => ['name' => 'Konto', 'slug' => 'konto'],
            'en' => ['name' => 'Account', 'slug' => 'account'],
        ],
        'channel-operators' => [
            'de' => ['name' => 'Kanalbetreiber', 'slug' => 'kanalbetreiber'],
            'en' => ['name' => 'Channel operators', 'slug' => 'channel-operators'],
        ],
        'evidence' => [
            'de' => ['name' => 'Beweissicherung', 'slug' => 'beweissicherung'],
            'en' => ['name' => 'Securing evidence', 'slug' => 'securing-evidence'],
        ],
        'data-protection' => [
            'de' => ['name' => 'Datenschutz', 'slug' => 'datenschutz'],
            'en' => ['name' => 'Data protection', 'slug' => 'data-protection'],
        ],
        'buying-guide' => [
            'de' => ['name' => 'Kaufberatung', 'slug' => 'kaufberatung'],
            'en' => ['name' => 'Buying guide', 'slug' => 'buying-guide'],
        ],
        'setup' => [
            'de' => ['name' => 'Einbau und Einstellungen', 'slug' => 'einbau-und-einstellungen'],
            'en' => ['name' => 'Setup', 'slug' => 'setup'],
        ],
        'video-quality' => [
            'de' => ['name' => 'Videoqualität', 'slug' => 'videoqualitaet'],
            'en' => ['name' => 'Video quality', 'slug' => 'video-quality'],
        ],
        'europe' => [
            'de' => ['name' => 'Unterwegs in Europa', 'slug' => 'unterwegs-in-europa'],
            'en' => ['name' => 'Travelling in Europe', 'slug' => 'travelling-in-europe'],
        ],
    ],
    'articles' => [
        'first-upload' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'getting-started', 'clip-submission'],
            'translations' => [
                'de' => [
                    'slug' => 'dein-erster-upload',
                    'title' => 'Dein erster Upload: Schritt für Schritt zur ersten Einsendung',
                    'excerpt' => 'Vom Anlegen des Kontos bis zur fertigen Einsendung: Dieser Durchlauf zeigt dir jeden Schritt, den du für deinen ersten Clip brauchst, und was du dabei selbst entscheidest.',
                    'meta_title' => 'Dein erster Upload bei DashClip Delivery | DashClip Delivery',
                    'meta_description' => 'Schritt für Schritt zur ersten Einsendung: Konto anlegen, Clip hochladen, Kanäle auswählen und den Überblick behalten.',
                ],
                'en' => [
                    'slug' => 'your-first-upload',
                    'title' => 'Your first upload: a step by step walkthrough',
                    'excerpt' => 'From creating an account to a finished submission: this walkthrough covers every step your first clip needs, and the points where the decision stays with you.',
                    'meta_title' => 'Your first upload at DashClip Delivery | DashClip Delivery',
                    'meta_description' => 'A step by step walkthrough: create an account, upload a clip, pick channels and keep track of what happens next.',
                ],
            ],
        ],
        'why-a-dashcam' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'evidence'],
            'translations' => [
                'de' => [
                    'slug' => 'warum-eine-dashcam-wichtig-ist',
                    'title' => 'Warum eine Dashcam wichtig ist: Beweise, die sonst fehlen',
                    'excerpt' => 'Nach einem Unfall steht oft Aussage gegen Aussage. Eine Aufnahme zeigt, was wirklich passiert ist, und kann eine Auseinandersetzung deutlich abkürzen.',
                    'meta_title' => 'Warum eine Dashcam wichtig ist | DashClip Delivery',
                    'meta_description' => 'Aussage gegen Aussage nach dem Unfall: Was eine Dashcam-Aufnahme leisten kann und wo ihre Grenzen liegen.',
                ],
                'en' => [
                    'slug' => 'why-a-dashcam-matters',
                    'title' => 'Why a dashcam matters: the evidence that is otherwise missing',
                    'excerpt' => 'After a collision it is often one account against another. A recording shows what actually happened and can shorten a dispute considerably.',
                    'meta_title' => 'Why a dashcam matters | DashClip Delivery',
                    'meta_description' => 'One account against another after a crash: what dashcam footage can do, and where its limits are.',
                ],
            ],
        ],
        'good-submissions' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission', 'video-quality'],
            'translations' => [
                'de' => [
                    'slug' => 'gute-einsendungen',
                    'title' => 'Gute Einsendungen: Länge, Kontext und was Kanäle brauchen',
                    'excerpt' => 'Ein guter Clip ist nicht der längste, sondern der verständlichste. Woran Kanalbetreiber erkennen, ob sie mit deiner Aufnahme arbeiten können.',
                    'meta_title' => 'Gute Einsendungen: Länge und Kontext | DashClip Delivery',
                    'meta_description' => 'Länge, Vorlauf, Kontext und Bildqualität: Woran Kanäle erkennen, ob sie mit deinem Clip arbeiten können.',
                ],
                'en' => [
                    'slug' => 'what-makes-a-good-submission',
                    'title' => 'What makes a good submission: length, context and what channels need',
                    'excerpt' => 'A good clip is not the longest one, it is the clearest one. Here is how channel operators decide whether they can work with your footage.',
                    'meta_title' => 'What makes a good submission | DashClip Delivery',
                    'meta_description' => 'Length, lead-in, context and image quality: how channels judge whether they can work with your clip.',
                ],
            ],
        ],
        'dashcam-data-protection' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'data-protection'],
            'translations' => [
                'de' => [
                    'slug' => 'dashcam-und-datenschutz',
                    'title' => 'Dashcam und Datenschutz: was in Deutschland gilt',
                    'excerpt' => 'Die Kamera selbst ist nicht verboten, die anlasslose Daueraufzeichnung dagegen problematisch. Was der Bundesgerichtshof entschieden hat und was daraus für dich folgt.',
                    'meta_title' => 'Dashcam und Datenschutz in Deutschland | DashClip Delivery',
                    'meta_description' => 'Anlasslose Daueraufnahme, kurze Schleifen und die Abwägung vor Gericht: die Rechtslage rund um Dashcams in Deutschland.',
                ],
                'en' => [
                    'slug' => 'dashcams-and-data-protection',
                    'title' => 'Dashcams and data protection: the rules in Germany',
                    'excerpt' => 'The camera itself is not banned, but recording everything without cause is a problem. What the Federal Court of Justice decided and what follows from it.',
                    'meta_title' => 'Dashcams and data protection in Germany | DashClip Delivery',
                    'meta_description' => 'Continuous recording, short loops and the balancing test in court: how German law treats dashcam footage.',
                ],
            ],
        ],
        'choosing-a-dashcam' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'buying-guide'],
            'translations' => [
                'de' => [
                    'slug' => 'die-passende-dashcam-finden',
                    'title' => 'Die passende Dashcam finden: worauf es wirklich ankommt',
                    'excerpt' => 'Auflösung ist nur eine von vielen Zahlen. Welche Eigenschaften im Alltag den Unterschied machen und welche du getrost ignorieren kannst.',
                    'meta_title' => 'Die passende Dashcam finden | DashClip Delivery',
                    'meta_description' => 'Auflösung, Blickwinkel, Stromversorgung, Speicher: die Kaufkriterien, die im Alltag wirklich zählen.',
                ],
                'en' => [
                    'slug' => 'choosing-the-right-dashcam',
                    'title' => 'Choosing the right dashcam: what actually counts',
                    'excerpt' => 'Resolution is only one number among many. Which properties make a difference in daily use, and which ones you can safely ignore.',
                    'meta_title' => 'Choosing the right dashcam | DashClip Delivery',
                    'meta_description' => 'Resolution, field of view, power supply, storage: the buying criteria that actually matter day to day.',
                ],
            ],
        ],
        'account-setup' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'account', 'getting-started'],
            'translations' => [
                'de' => [
                    'slug' => 'dein-konto-einrichten',
                    'title' => 'Dein Konto einrichten: Profil, Benachrichtigungen, Sprache',
                    'excerpt' => 'Ein paar Einstellungen zu Beginn ersparen dir später Sucherei. Was in deinem Profil steht, welche E-Mails du bekommst und wie du die Sprache festlegst.',
                    'meta_title' => 'Konto einrichten bei DashClip Delivery | DashClip Delivery',
                    'meta_description' => 'Profil, Benachrichtigungen und Spracheinstellung: die Grundeinstellungen deines Kontos im Überblick.',
                ],
                'en' => [
                    'slug' => 'setting-up-your-account',
                    'title' => 'Setting up your account: profile, notifications, language',
                    'excerpt' => 'A few settings at the start save you searching later. What your profile holds, which emails you receive and how to pick your language.',
                    'meta_title' => 'Setting up your DashClip Delivery account | DashClip Delivery',
                    'meta_description' => 'Profile, notifications and language: an overview of the account settings worth getting right early.',
                ],
            ],
        ],
        'mounting-and-alignment' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'einbau-und-ausrichtung',
                    'title' => 'Einbau und Ausrichtung: damit die Aufnahme brauchbar ist',
                    'excerpt' => 'Die beste Kamera nützt wenig, wenn sie in den Himmel filmt oder die Sicht verstellt. Wo die Kamera hingehört und wie du sie ausrichtest.',
                    'meta_title' => 'Dashcam einbauen und ausrichten | DashClip Delivery',
                    'meta_description' => 'Position hinter dem Spiegel, Neigung, Kabelführung und Sichtfeld: so wird die Aufnahme brauchbar.',
                ],
                'en' => [
                    'slug' => 'mounting-and-alignment',
                    'title' => 'Mounting and alignment: making the footage usable',
                    'excerpt' => 'The best camera helps little if it films the sky or blocks your view. Where the camera belongs and how to line it up.',
                    'meta_title' => 'Mounting and aligning a dashcam | DashClip Delivery',
                    'meta_description' => 'Position behind the mirror, tilt, cable routing and field of view: how to make the footage usable.',
                ],
            ],
        ],
        'choosing-the-clip-section' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission', 'video-quality'],
            'translations' => [
                'de' => [
                    'slug' => 'den-richtigen-ausschnitt-waehlen',
                    'title' => 'Den richtigen Ausschnitt wählen: vor, während, nach dem Ereignis',
                    'excerpt' => 'Wie viel Vorlauf braucht eine Szene, damit sie verständlich bleibt? Eine praktische Faustregel für den Zuschnitt deiner Clips.',
                    'meta_title' => 'Den richtigen Ausschnitt wählen | DashClip Delivery',
                    'meta_description' => 'Vorlauf, Ereignis, Nachlauf: wie du deinen Clip so zuschneidest, dass die Szene verständlich bleibt.',
                ],
                'en' => [
                    'slug' => 'choosing-the-right-section',
                    'title' => 'Choosing the right section: before, during and after the event',
                    'excerpt' => 'How much lead-in does a scene need to stay understandable? A practical rule of thumb for trimming your clips.',
                    'meta_title' => 'Choosing the right section of a clip | DashClip Delivery',
                    'meta_description' => 'Lead-in, event, aftermath: how to trim a clip so the scene still makes sense to a viewer.',
                ],
            ],
        ],
        'after-a-crash' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'evidence'],
            'translations' => [
                'de' => [
                    'slug' => 'nach-dem-unfall-aufnahme-sichern',
                    'title' => 'Nach dem Unfall: Aufnahme sichern und richtig einsetzen',
                    'excerpt' => 'In den ersten Minuten nach einem Unfall zählt die Reihenfolge. Was zuerst kommt, wann du die Aufnahme sicherst und wem du sie zeigst.',
                    'meta_title' => 'Nach dem Unfall: Aufnahme sichern | DashClip Delivery',
                    'meta_description' => 'Absichern, Hilfe leisten, Aufnahme sichern: die richtige Reihenfolge nach einem Unfall mit Dashcam.',
                ],
                'en' => [
                    'slug' => 'after-a-crash-securing-the-footage',
                    'title' => 'After a crash: securing the footage and using it properly',
                    'excerpt' => 'In the first minutes after a crash the order of things matters. What comes first, when to secure the footage and who gets to see it.',
                    'meta_title' => 'After a crash: securing dashcam footage | DashClip Delivery',
                    'meta_description' => 'Make the scene safe, help the injured, then secure the recording: the right order after a crash.',
                ],
            ],
        ],
        'loop-recording' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'loop-aufnahme-verstehen',
                    'title' => 'Loop-Aufnahme verstehen: warum kurze Schleifen wichtig sind',
                    'excerpt' => 'Die Schleife ist kein technisches Detail, sondern der Kern datensparsamer Aufzeichnung. Wie sie funktioniert und welche Länge sinnvoll ist.',
                    'meta_title' => 'Loop-Aufnahme bei der Dashcam verstehen | DashClip Delivery',
                    'meta_description' => 'Wie die Aufnahmeschleife funktioniert, welche Länge sinnvoll ist und warum sie für den Datenschutz zählt.',
                ],
                'en' => [
                    'slug' => 'understanding-loop-recording',
                    'title' => 'Understanding loop recording: why short loops matter',
                    'excerpt' => 'The loop is not a technical detail, it is the core of recording sparingly. How it works and which segment length makes sense.',
                    'meta_title' => 'Understanding dashcam loop recording | DashClip Delivery',
                    'meta_description' => 'How the recording loop works, which segment length makes sense and why it matters for data protection.',
                ],
            ],
        ],
        'discovering-channels' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'getting-started'],
            'translations' => [
                'de' => [
                    'slug' => 'kanaele-entdecken',
                    'title' => 'Kanäle entdecken: welcher passt zu deinem Clip',
                    'excerpt' => 'Nicht jeder Kanal sucht dasselbe Material. Wie du dir ein Bild von einem Kanal machst, bevor du ihm deinen Clip anbietest.',
                    'meta_title' => 'Kanäle entdecken und auswählen | DashClip Delivery',
                    'meta_description' => 'Ausrichtung, Tonfall und Themen: wie du den Kanal findest, zu dem dein Clip wirklich passt.',
                ],
                'en' => [
                    'slug' => 'discovering-channels',
                    'title' => 'Discovering channels: which one suits your clip',
                    'excerpt' => 'Not every channel is looking for the same material. How to get a feel for a channel before you offer it your clip.',
                    'meta_title' => 'Discovering and choosing channels | DashClip Delivery',
                    'meta_description' => 'Focus, tone and recurring topics: how to find the channel your clip actually fits.',
                ],
            ],
        ],
        'memory-cards' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'buying-guide'],
            'translations' => [
                'de' => [
                    'slug' => 'speicherkarten-fuer-dashcams',
                    'title' => 'Speicherkarten: warum die falsche Karte alles kostet',
                    'excerpt' => 'Eine Dashcam schreibt pausenlos. Günstige Karten halten das nicht lange aus, und der Ausfall fällt meist erst auf, wenn die Aufnahme fehlt.',
                    'meta_title' => 'Speicherkarten für Dashcams | DashClip Delivery',
                    'meta_description' => 'Dauerschreiblast, Kartenklassen und Kapazität: welche Speicherkarte in eine Dashcam gehört und wie oft du sie prüfst.',
                ],
                'en' => [
                    'slug' => 'memory-cards-for-dashcams',
                    'title' => 'Memory cards: why the wrong card costs you everything',
                    'excerpt' => 'A dashcam writes without pause. Cheap cards do not survive that for long, and the failure usually surfaces when the footage is missing.',
                    'meta_title' => 'Memory cards for dashcams | DashClip Delivery',
                    'meta_description' => 'Constant write load, speed classes and capacity: which memory card belongs in a dashcam and how often to check it.',
                ],
            ],
        ],
        'file-formats' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission', 'video-quality'],
            'translations' => [
                'de' => [
                    'slug' => 'dateiformate-und-aufloesung',
                    'title' => 'Dateiformate und Auflösung: was beim Hochladen zählt',
                    'excerpt' => 'Wandeln, verkleinern, neu rendern: Vieles davon schadet mehr, als es hilft. Was du mit der Datei aus der Kamera tun solltest und was nicht.',
                    'meta_title' => 'Dateiformate und Auflösung beim Upload | DashClip Delivery',
                    'meta_description' => 'Originaldatei, Formate und unnötiges Neukodieren: was beim Hochladen deines Clips wirklich zählt.',
                ],
                'en' => [
                    'slug' => 'file-formats-and-resolution',
                    'title' => 'File formats and resolution: what counts when uploading',
                    'excerpt' => 'Converting, shrinking, re-rendering: most of it does more harm than good. What to do with the file from your camera, and what to leave alone.',
                    'meta_title' => 'File formats and resolution when uploading | DashClip Delivery',
                    'meta_description' => 'The original file, common formats and needless re-encoding: what actually counts when you upload a clip.',
                ],
            ],
        ],
        'dashcam-in-winter' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'dashcam-im-winter',
                    'title' => 'Dashcam im Winter: Scheibe, Beschlag, Sicht',
                    'excerpt' => 'Kälte, beschlagene Scheiben und tief stehende Sonne setzen jeder Aufnahme zu. Was im Winter hilft, damit die Bilder brauchbar bleiben.',
                    'meta_title' => 'Dashcam im Winter nutzen | DashClip Delivery',
                    'meta_description' => 'Beschlag, Eis, Kälte und Blendung: was im Winter hilft, damit Dashcam-Aufnahmen brauchbar bleiben.',
                ],
                'en' => [
                    'slug' => 'dashcams-in-winter',
                    'title' => 'Dashcams in winter: windscreen, fogging, visibility',
                    'excerpt' => 'Cold, fogged glass and a low sun all work against your footage. What helps in winter to keep the images usable.',
                    'meta_title' => 'Using a dashcam in winter | DashClip Delivery',
                    'meta_description' => 'Fogging, ice, cold and glare: what helps keep dashcam footage usable through the winter months.',
                ],
            ],
        ],
        'capacitor-or-battery' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'buying-guide'],
            'translations' => [
                'de' => [
                    'slug' => 'kondensator-oder-akku',
                    'title' => 'Kondensator oder Akku: was bei Frost und Hitze zählt',
                    'excerpt' => 'Im Sommer wird es hinter der Scheibe sehr heiß, im Winter sehr kalt. Welcher Energiespeicher das besser übersteht und woran du den Unterschied merkst.',
                    'meta_title' => 'Kondensator oder Akku in der Dashcam | DashClip Delivery',
                    'meta_description' => 'Hitze, Frost und Lebensdauer: warum der Energiespeicher einer Dashcam über ihre Zuverlässigkeit entscheidet.',
                ],
                'en' => [
                    'slug' => 'capacitor-or-battery',
                    'title' => 'Capacitor or battery: what counts in frost and heat',
                    'excerpt' => 'Behind the windscreen it gets very hot in summer and very cold in winter. Which energy store survives that better, and how you notice the difference.',
                    'meta_title' => 'Capacitor or battery in a dashcam | DashClip Delivery',
                    'meta_description' => 'Heat, frost and service life: why the energy store decides how reliable a dashcam really is.',
                ],
            ],
        ],
        'for-channel-operators' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'channel-operators'],
            'translations' => [
                'de' => [
                    'slug' => 'fuer-kanalbetreiber',
                    'title' => 'Für Kanalbetreiber: Einsendungen empfangen und verwalten',
                    'excerpt' => 'Wie ein Kanal auf der Plattform arbeitet: Angebote sichten, herunterladen, zurückgeben und den Überblick über eingegangenes Material behalten.',
                    'meta_title' => 'Für Kanalbetreiber: Einsendungen verwalten | DashClip Delivery',
                    'meta_description' => 'Angebote sichten, herunterladen, zurückgeben: wie Kanalbetreiber eingehende Einsendungen verwalten.',
                ],
                'en' => [
                    'slug' => 'for-channel-operators',
                    'title' => 'For channel operators: receiving and managing submissions',
                    'excerpt' => 'How a channel works on the platform: reviewing offers, downloading material, returning what does not fit and keeping an overview.',
                    'meta_title' => 'For channel operators: managing submissions | DashClip Delivery',
                    'meta_description' => 'Reviewing offers, downloading material and returning what does not fit: the channel operator side of the platform.',
                ],
            ],
        ],
        'resolution-and-bitrate' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'video-quality'],
            'translations' => [
                'de' => [
                    'slug' => 'aufloesung-bitrate-bildrate',
                    'title' => 'Auflösung, Bitrate, Bildrate: was die Zahlen bedeuten',
                    'excerpt' => 'Drei Werte entscheiden über die Qualität einer Aufnahme, und der bekannteste davon ist nicht der wichtigste. Eine Einordnung ohne Fachchinesisch.',
                    'meta_title' => 'Auflösung, Bitrate und Bildrate erklärt | DashClip Delivery',
                    'meta_description' => 'Warum die Bitrate oft mehr über die Bildqualität verrät als die Auflösung, und welche Bildrate sinnvoll ist.',
                ],
                'en' => [
                    'slug' => 'resolution-bitrate-frame-rate',
                    'title' => 'Resolution, bitrate, frame rate: what the numbers mean',
                    'excerpt' => 'Three values decide the quality of a recording, and the best known of them is not the most important. An explanation without jargon.',
                    'meta_title' => 'Resolution, bitrate and frame rate explained | DashClip Delivery',
                    'meta_description' => 'Why bitrate often says more about image quality than resolution, and which frame rate makes sense.',
                ],
            ],
        ],
        'writing-the-description' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission'],
            'translations' => [
                'de' => [
                    'slug' => 'die-beschreibung-schreiben',
                    'title' => 'Die Beschreibung: was Kanäle über deinen Clip wissen müssen',
                    'excerpt' => 'Ein paar Zeilen entscheiden oft darüber, ob jemand deinen Clip überhaupt öffnet. Was hineingehört und was du weglassen kannst.',
                    'meta_title' => 'Die Beschreibung zum Clip schreiben | DashClip Delivery',
                    'meta_description' => 'Ort, Zeitpunkt, Situation und Besonderheiten: was in die Beschreibung deiner Einsendung gehört.',
                ],
                'en' => [
                    'slug' => 'writing-the-description',
                    'title' => 'The description: what channels need to know about your clip',
                    'excerpt' => 'A few lines often decide whether anyone opens your clip at all. What belongs in them, and what you can leave out.',
                    'meta_title' => 'Writing the description for your clip | DashClip Delivery',
                    'meta_description' => 'Place, time, situation and anything unusual: what belongs in the description of your submission.',
                ],
            ],
        ],
        'night-recordings' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'video-quality'],
            'translations' => [
                'de' => [
                    'slug' => 'nachtaufnahmen',
                    'title' => 'Nachtaufnahmen: was Sensoren wirklich leisten',
                    'excerpt' => 'Bei Dunkelheit trennt sich brauchbares Material von unbrauchbarem. Was im Dunkeln möglich ist und womit du realistisch rechnen solltest.',
                    'meta_title' => 'Dashcam-Nachtaufnahmen verstehen | DashClip Delivery',
                    'meta_description' => 'Lichtstärke, Sensorgröße und Gegenlicht: was Dashcams bei Dunkelheit leisten und wo Grenzen liegen.',
                ],
                'en' => [
                    'slug' => 'night-recordings',
                    'title' => 'Night recordings: what sensors really deliver',
                    'excerpt' => 'Darkness separates usable footage from unusable footage. What is possible at night and what you should realistically expect.',
                    'meta_title' => 'Understanding dashcam night footage | DashClip Delivery',
                    'meta_description' => 'Lens speed, sensor size and oncoming headlights: what dashcams manage after dark and where the limits are.',
                ],
            ],
        ],
        'readable-number-plates' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'video-quality', 'evidence'],
            'translations' => [
                'de' => [
                    'slug' => 'kennzeichen-lesbar-aufnehmen',
                    'title' => 'Kennzeichen lesbar aufnehmen: Blickwinkel und Abstand',
                    'excerpt' => 'Ein Kennzeichen im Bild heißt nicht, dass es lesbar ist. Welche Rolle Abstand, Blickwinkel und Bewegung dabei spielen.',
                    'meta_title' => 'Kennzeichen lesbar aufnehmen | DashClip Delivery',
                    'meta_description' => 'Abstand, Blickwinkel, Bewegungsunschärfe: warum Kennzeichen oft unlesbar bleiben und was dagegen hilft.',
                ],
                'en' => [
                    'slug' => 'readable-number-plates',
                    'title' => 'Capturing readable number plates: angle and distance',
                    'excerpt' => 'A plate in frame does not mean a plate you can read. The part distance, angle and motion play in whether it stays legible.',
                    'meta_title' => 'Capturing readable number plates | DashClip Delivery',
                    'meta_description' => 'Distance, viewing angle and motion blur: why plates often stay unreadable and what helps.',
                ],
            ],
        ],
        'offer-deadlines' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'clip-submission'],
            'translations' => [
                'de' => [
                    'slug' => 'angebotsfristen-verstehen',
                    'title' => 'Angebotsfristen verstehen: warum Angebote ablaufen',
                    'excerpt' => 'Ein Angebot an einen Kanal gilt nicht endlos. Warum das so ist, was nach Ablauf passiert und weshalb das für dich von Vorteil ist.',
                    'meta_title' => 'Angebotsfristen bei DashClip Delivery | DashClip Delivery',
                    'meta_description' => 'Warum Angebote an Kanäle zeitlich begrenzt sind und was nach Ablauf einer Frist mit deinem Clip geschieht.',
                ],
                'en' => [
                    'slug' => 'understanding-offer-deadlines',
                    'title' => 'Understanding offer deadlines: why offers expire',
                    'excerpt' => 'An offer to a channel does not last forever. Why that is, what happens once it expires and why the limit works in your favour.',
                    'meta_title' => 'Offer deadlines at DashClip Delivery | DashClip Delivery',
                    'meta_description' => 'Why offers to channels are time limited and what happens to your clip once a deadline passes.',
                ],
            ],
        ],
        'parking-mode' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'parkmodus',
                    'title' => 'Parkmodus: Überwachung im Stand und ihre Grenzen',
                    'excerpt' => 'Der Parkmodus klingt nach lückenloser Absicherung. Tatsächlich hat er technische und rechtliche Grenzen, die du kennen solltest.',
                    'meta_title' => 'Parkmodus bei der Dashcam | DashClip Delivery',
                    'meta_description' => 'Dauerstrom, Bewegungserkennung, Batterieschutz und rechtliche Grenzen des Parkmodus im Überblick.',
                ],
                'en' => [
                    'slug' => 'parking-mode',
                    'title' => 'Parking mode: watching while parked, and its limits',
                    'excerpt' => 'Parking mode sounds like seamless protection. In practice it has technical and legal limits worth knowing before you rely on it.',
                    'meta_title' => 'Dashcam parking mode | DashClip Delivery',
                    'meta_description' => 'Permanent power, motion detection, battery protection and the legal limits of parking mode.',
                ],
            ],
        ],
        'plates-and-faces-before-sending' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission', 'data-protection'],
            'translations' => [
                'de' => [
                    'slug' => 'kennzeichen-und-gesichter-vor-dem-einsenden',
                    'title' => 'Kennzeichen und Gesichter: was du vor dem Einsenden bedenkst',
                    'excerpt' => 'Auf fast jeder Aufnahme sind Menschen und Fahrzeuge zu sehen, die nichts mit der Szene zu tun haben. Wie du damit verantwortlich umgehst.',
                    'meta_title' => 'Kennzeichen und Gesichter vor dem Einsenden | DashClip Delivery',
                    'meta_description' => 'Unbeteiligte im Bild, Verpixeln und die Frage, wer am Ende entscheidet: worauf du vor dem Einsenden achtest.',
                ],
                'en' => [
                    'slug' => 'plates-and-faces-before-you-submit',
                    'title' => 'Plates and faces: what to consider before submitting',
                    'excerpt' => 'Almost every recording shows people and vehicles that have nothing to do with the scene. How to handle that responsibly.',
                    'meta_title' => 'Plates and faces before you submit | DashClip Delivery',
                    'meta_description' => 'Bystanders in frame, blurring and who decides in the end: what to weigh up before submitting a clip.',
                ],
            ],
        ],
        'impact-sensor' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'erschuetterungssensor-einstellen',
                    'title' => 'Den Erschütterungssensor richtig einstellen',
                    'excerpt' => 'Zu empfindlich, und die Karte ist voller geschützter Dateien. Zu unempfindlich, und die eine wichtige Aufnahme wird überschrieben.',
                    'meta_title' => 'Erschütterungssensor der Dashcam einstellen | DashClip Delivery',
                    'meta_description' => 'Warum die Empfindlichkeit des Sensors über Speicherplatz und gesicherte Aufnahmen entscheidet.',
                ],
                'en' => [
                    'slug' => 'setting-the-impact-sensor',
                    'title' => 'Setting the impact sensor correctly',
                    'excerpt' => 'Too sensitive and the card fills with protected files. Not sensitive enough and the one recording that mattered gets overwritten.',
                    'meta_title' => 'Setting a dashcam impact sensor | DashClip Delivery',
                    'meta_description' => 'Why sensor sensitivity decides both your free storage and whether important footage survives.',
                ],
            ],
        ],
        'front-rear-interior' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'buying-guide'],
            'translations' => [
                'de' => [
                    'slug' => 'front-heck-innenraum',
                    'title' => 'Front, Heck, Innenraum: welche Perspektive wann',
                    'excerpt' => 'Eine zweite Kamera verdoppelt nicht einfach den Nutzen. Welche Perspektive in welcher Situation wirklich etwas beiträgt.',
                    'meta_title' => 'Front, Heck oder Innenraum | DashClip Delivery',
                    'meta_description' => 'Auffahrunfall, Spurwechsel, Fahrgastraum: welche Kameraperspektive in welcher Situation zählt.',
                ],
                'en' => [
                    'slug' => 'front-rear-interior',
                    'title' => 'Front, rear, interior: which view when',
                    'excerpt' => 'A second camera does not simply double the benefit. Which perspective actually contributes something in which situation.',
                    'meta_title' => 'Front, rear or interior camera | DashClip Delivery',
                    'meta_description' => 'Rear-end collisions, lane changes, passenger compartment: which camera view counts in which situation.',
                ],
            ],
        ],
        'after-the-download' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'channel-operators'],
            'translations' => [
                'de' => [
                    'slug' => 'was-nach-dem-download-passiert',
                    'title' => 'Was mit deinem Clip nach dem Download passiert',
                    'excerpt' => 'Ein Download ist noch keine Veröffentlichung. Was ein Kanal danach tut, wie lange das dauert und woran du den Stand erkennst.',
                    'meta_title' => 'Was nach dem Download deines Clips passiert | DashClip Delivery',
                    'meta_description' => 'Sichtung, Schnitt, Veröffentlichung oder Ablage: was nach dem Download eines Clips durch einen Kanal geschieht.',
                ],
                'en' => [
                    'slug' => 'what-happens-after-the-download',
                    'title' => 'What happens to your clip after the download',
                    'excerpt' => 'A download is not a publication. What a channel does next, how long that takes and how you can tell where things stand.',
                    'meta_title' => 'What happens after your clip is downloaded | DashClip Delivery',
                    'meta_description' => 'Review, editing, publication or shelving: what a channel does after downloading a clip.',
                ],
            ],
        ],
        'power-supply' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'stromversorgung',
                    'title' => 'Stromversorgung: Bordsteckdose, Festeinbau, Zusatzakku',
                    'excerpt' => 'Wie die Kamera mit Strom versorgt wird, entscheidet über Parkmodus, Kabelsalat und die Belastung der Fahrzeugbatterie.',
                    'meta_title' => 'Stromversorgung für die Dashcam | DashClip Delivery',
                    'meta_description' => 'Bordsteckdose, Festeinbau oder Zusatzakku: Vor- und Nachteile der drei Wege zur Stromversorgung.',
                ],
                'en' => [
                    'slug' => 'power-supply',
                    'title' => 'Power supply: socket, hardwiring, separate battery',
                    'excerpt' => 'How the camera gets its power decides parking mode, cable clutter and how much load the vehicle battery carries.',
                    'meta_title' => 'Powering your dashcam | DashClip Delivery',
                    'meta_description' => 'Accessory socket, hardwiring or a separate battery pack: the trade-offs of each way to power a dashcam.',
                ],
            ],
        ],
        'audio-in-the-clip' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission', 'data-protection'],
            'translations' => [
                'de' => [
                    'slug' => 'ton-im-clip',
                    'title' => 'Ton im Clip: wann er hilft und wann er stört',
                    'excerpt' => 'Ton kann eine Szene erklären oder sie unbrauchbar machen. Warum das Mikrofon oft besser aus bleibt und wann es sich lohnt.',
                    'meta_title' => 'Ton im Dashcam-Clip | DashClip Delivery',
                    'meta_description' => 'Gespräche im Fahrzeug, Hintergrundgeräusche und Persönlichkeitsrechte: wann Ton im Clip sinnvoll ist.',
                ],
                'en' => [
                    'slug' => 'audio-in-your-clip',
                    'title' => 'Audio in your clip: when it helps and when it hurts',
                    'excerpt' => 'Sound can explain a scene or make it unusable. Why the microphone is often better left off, and when it earns its place.',
                    'meta_title' => 'Audio in a dashcam clip | DashClip Delivery',
                    'meta_description' => 'In-car conversations, background noise and personal rights: when audio adds something to a clip.',
                ],
            ],
        ],
        'dashcams-abroad' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'europe', 'data-protection'],
            'translations' => [
                'de' => [
                    'slug' => 'dashcam-im-ausland',
                    'title' => 'Dashcam im Ausland: Regeln auf Europas Straßen',
                    'excerpt' => 'Was zu Hause erlaubt ist, kann hinter der Grenze teuer werden. Ein Überblick über Länder mit Verboten und über die sichere Grundregel für Reisen.',
                    'meta_title' => 'Dashcam im Ausland: Regeln in Europa | DashClip Delivery',
                    'meta_description' => 'Verbotsländer, Bußgelder und die sichere Grundregel: was bei der Dashcam-Nutzung auf Reisen in Europa gilt.',
                ],
                'en' => [
                    'slug' => 'dashcams-abroad',
                    'title' => 'Dashcams abroad: the rules on European roads',
                    'excerpt' => 'What is allowed at home can get expensive across the border. An overview of countries with bans and the safe rule of thumb for travelling.',
                    'meta_title' => 'Dashcams abroad: rules across Europe | DashClip Delivery',
                    'meta_description' => 'Countries with bans, fines and the safe rule of thumb for using a dashcam while travelling in Europe.',
                ],
            ],
        ],
        'rescuing-footage' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'evidence'],
            'translations' => [
                'de' => [
                    'slug' => 'aufnahme-vor-dem-ueberschreiben-sichern',
                    'title' => 'Aufnahmen sichern, bevor die Schleife sie überschreibt',
                    'excerpt' => 'Die Schleife arbeitet gnadenlos weiter. Was du in den ersten Minuten tust, entscheidet darüber, ob die Aufnahme morgen noch existiert.',
                    'meta_title' => 'Aufnahme vor dem Überschreiben sichern | DashClip Delivery',
                    'meta_description' => 'Kamera stoppen, Datei schützen, Karte entnehmen: so rettest du eine Aufnahme vor der Aufnahmeschleife.',
                ],
                'en' => [
                    'slug' => 'rescuing-footage-before-it-is-overwritten',
                    'title' => 'Rescuing footage before the loop overwrites it',
                    'excerpt' => 'The loop keeps going regardless. What you do in the first few minutes decides whether the recording still exists tomorrow.',
                    'meta_title' => 'Rescuing footage before it is overwritten | DashClip Delivery',
                    'meta_description' => 'Stop the camera, protect the file, remove the card: how to save a recording from the loop.',
                ],
            ],
        ],
        'notifications' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'account'],
            'translations' => [
                'de' => [
                    'slug' => 'benachrichtigungen-im-griff',
                    'title' => 'Benachrichtigungen im Griff: E-Mails, die wirklich helfen',
                    'excerpt' => 'Welche Nachrichten die Plattform verschickt, warum sie verschickt werden und wie du dafür sorgst, dass sie dich auch erreichen.',
                    'meta_title' => 'Benachrichtigungen verstehen und einstellen | DashClip Delivery',
                    'meta_description' => 'Welche E-Mails die Plattform verschickt, was sie bedeuten und wie du sicherstellst, dass sie ankommen.',
                ],
                'en' => [
                    'slug' => 'keeping-notifications-useful',
                    'title' => 'Keeping notifications useful: emails that actually help',
                    'excerpt' => 'Which messages the platform sends, why it sends them and how to make sure they reach you rather than a spam folder.',
                    'meta_title' => 'Understanding and tuning notifications | DashClip Delivery',
                    'meta_description' => 'Which emails the platform sends, what they mean and how to make sure they actually arrive.',
                ],
            ],
        ],
        'footage-for-insurers' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'evidence'],
            'translations' => [
                'de' => [
                    'slug' => 'aufnahmen-bei-der-versicherung',
                    'title' => 'Aufnahmen bei der Versicherung einreichen',
                    'excerpt' => 'Eine Aufnahme kann die Regulierung beschleunigen. Wie du sie übergibst, was du dokumentierst und was du dabei nicht vergessen solltest.',
                    'meta_title' => 'Dashcam-Aufnahmen bei der Versicherung | DashClip Delivery',
                    'meta_description' => 'Originaldatei, Zeitstempel und Begleitinformationen: wie du eine Aufnahme bei der Versicherung einreichst.',
                ],
                'en' => [
                    'slug' => 'submitting-footage-to-insurers',
                    'title' => 'Submitting footage to your insurer',
                    'excerpt' => 'Footage can speed up a claim considerably. How to hand it over, what to document alongside it and what not to forget.',
                    'meta_title' => 'Dashcam footage and your insurer | DashClip Delivery',
                    'meta_description' => 'Original file, timestamps and supporting notes: how to submit a recording to an insurer.',
                ],
            ],
        ],
        'choosing-several-channels' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission'],
            'translations' => [
                'de' => [
                    'slug' => 'mehrere-kanaele-auswaehlen',
                    'title' => 'Mehrere Kanäle gleichzeitig: wie du sinnvoll auswählst',
                    'excerpt' => 'Mehr Kanäle heißt nicht automatisch mehr Chancen. Wie du eine Auswahl triffst, die zu deinem Clip passt.',
                    'meta_title' => 'Mehrere Kanäle sinnvoll auswählen | DashClip Delivery',
                    'meta_description' => 'Warum eine gezielte Auswahl mehrerer Kanäle besser funktioniert als die größtmögliche Liste.',
                ],
                'en' => [
                    'slug' => 'choosing-several-channels',
                    'title' => 'Several channels at once: choosing sensibly',
                    'excerpt' => 'More channels does not automatically mean more chances. How to put together a selection that suits your clip.',
                    'meta_title' => 'Choosing several channels sensibly | DashClip Delivery',
                    'meta_description' => 'Why a deliberate selection of channels works better than ticking the longest possible list.',
                ],
            ],
        ],
        'footage-for-the-police' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'evidence'],
            'translations' => [
                'de' => [
                    'slug' => 'aufnahmen-bei-der-polizei',
                    'title' => 'Aufnahmen bei der Polizei vorlegen',
                    'excerpt' => 'Wann eine Aufnahme zur Anzeige gehört, wie du sie übergibst und warum du das Original niemals bearbeiten solltest.',
                    'meta_title' => 'Dashcam-Aufnahmen bei der Polizei vorlegen | DashClip Delivery',
                    'meta_description' => 'Original statt Bearbeitung, Zeitangaben und Anzeigeerstattung: wie du eine Aufnahme der Polizei übergibst.',
                ],
                'en' => [
                    'slug' => 'presenting-footage-to-the-police',
                    'title' => 'Presenting footage to the police',
                    'excerpt' => 'When a recording belongs in a report, how to hand it over and why you should never edit the original file.',
                    'meta_title' => 'Presenting dashcam footage to the police | DashClip Delivery',
                    'meta_description' => 'The original rather than an edit, accurate timings and filing a report: how to hand footage to the police.',
                ],
            ],
        ],
        'publishing-footage' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'data-protection'],
            'translations' => [
                'de' => [
                    'slug' => 'aufnahmen-veroeffentlichen',
                    'title' => 'Aufnahmen veröffentlichen: was erlaubt ist',
                    'excerpt' => 'Zwischen sichern und veröffentlichen liegt ein großer Unterschied. Was beim Teilen einer Aufnahme zu beachten ist.',
                    'meta_title' => 'Dashcam-Aufnahmen veröffentlichen | DashClip Delivery',
                    'meta_description' => 'Persönlichkeitsrechte, erkennbare Personen und Kennzeichen: was beim Veröffentlichen einer Aufnahme gilt.',
                ],
                'en' => [
                    'slug' => 'publishing-footage',
                    'title' => 'Publishing footage: what is allowed',
                    'excerpt' => 'There is a wide gap between securing a recording and publishing it. What to consider before sharing one publicly.',
                    'meta_title' => 'Publishing dashcam footage | DashClip Delivery',
                    'meta_description' => 'Personal rights, identifiable people and number plates: what applies when you publish a recording.',
                ],
            ],
        ],
        'platform-privacy' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'data-protection'],
            'translations' => [
                'de' => [
                    'slug' => 'sicherheit-und-datenschutz-auf-der-plattform',
                    'title' => 'Sicherheit und Datenschutz auf der Plattform',
                    'excerpt' => 'Wer sieht deinen Clip, wer darf ihn herunterladen und was passiert mit deinen Daten? Ein Blick hinter die Zugriffsregeln.',
                    'meta_title' => 'Sicherheit und Datenschutz auf der Plattform | DashClip Delivery',
                    'meta_description' => 'Zugriffsrechte, Sichtbarkeit deiner Clips und der Umgang mit deinen Daten auf DashClip Delivery.',
                ],
                'en' => [
                    'slug' => 'security-and-privacy-on-the-platform',
                    'title' => 'Security and privacy on the platform',
                    'excerpt' => 'Who sees your clip, who may download it and what happens to your data? A look at how access is controlled.',
                    'meta_title' => 'Security and privacy on the platform | DashClip Delivery',
                    'meta_description' => 'Access rights, who can see your clips and how your data is handled at DashClip Delivery.',
                ],
            ],
        ],
        'blurring-faces-and-plates' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'data-protection'],
            'translations' => [
                'de' => [
                    'slug' => 'gesichter-und-kennzeichen-unkenntlich-machen',
                    'title' => 'Gesichter und Kennzeichen unkenntlich machen',
                    'excerpt' => 'Unkenntlich machen klingt einfach, ist aber leicht falsch gemacht. Worauf es ankommt, damit aus dem Verpixeln kein Scheinschutz wird.',
                    'meta_title' => 'Gesichter und Kennzeichen unkenntlich machen | DashClip Delivery',
                    'meta_description' => 'Verpixeln, nachziehen, prüfen: wie unkenntlich gemachte Aufnahmen tatsächlich unkenntlich bleiben.',
                ],
                'en' => [
                    'slug' => 'blurring-faces-and-plates',
                    'title' => 'Blurring faces and number plates',
                    'excerpt' => 'Blurring sounds simple and is easily done badly. What matters so that a blur is real protection rather than the appearance of it.',
                    'meta_title' => 'Blurring faces and number plates | DashClip Delivery',
                    'meta_description' => 'Blur, track, verify: how to make sure an anonymised recording really stays anonymised.',
                ],
            ],
        ],
        'why-channels-decline' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission', 'channel-operators'],
            'translations' => [
                'de' => [
                    'slug' => 'warum-kanaele-ablehnen',
                    'title' => 'Warum ein Kanal ablehnt: die häufigsten Gründe',
                    'excerpt' => 'Eine Absage ist selten persönlich gemeint. Die häufigsten inhaltlichen Gründe und was du beim nächsten Clip anders machen kannst.',
                    'meta_title' => 'Warum Kanäle Clips ablehnen | DashClip Delivery',
                    'meta_description' => 'Bildqualität, fehlender Kontext, doppelte Themen: die häufigsten Gründe für eine Absage und was hilft.',
                ],
                'en' => [
                    'slug' => 'why-channels-decline',
                    'title' => 'Why a channel declines: the most common reasons',
                    'excerpt' => 'A rejection is rarely personal. The most common editorial reasons, and what you can do differently with your next clip.',
                    'meta_title' => 'Why channels decline clips | DashClip Delivery',
                    'meta_description' => 'Image quality, missing context, repeated topics: the most common reasons for a rejection and what helps.',
                ],
            ],
        ],
        'location-data' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'data-protection'],
            'translations' => [
                'de' => [
                    'slug' => 'standortdaten',
                    'title' => 'Standortdaten: Nutzen und Risiko',
                    'excerpt' => 'Viele Kameras schreiben Position und Geschwindigkeit ins Bild. Das hilft bei der Einordnung und verrät zugleich mehr, als dir lieb sein kann.',
                    'meta_title' => 'Standortdaten in Dashcam-Aufnahmen | DashClip Delivery',
                    'meta_description' => 'Position, Geschwindigkeit und Zeitstempel im Bild: wann Standortdaten helfen und wann sie zum Risiko werden.',
                ],
                'en' => [
                    'slug' => 'location-data',
                    'title' => 'Location data: the benefit and the risk',
                    'excerpt' => 'Many cameras stamp position and speed into the image. That helps place a scene and reveals more than you may intend.',
                    'meta_title' => 'Location data in dashcam footage | DashClip Delivery',
                    'meta_description' => 'Position, speed and timestamps in frame: when location data helps and when it becomes a risk.',
                ],
            ],
        ],
        'camera-apps' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'funkverbindung-und-app',
                    'title' => 'Funkverbindung und App: bequem, aber nicht sorglos',
                    'excerpt' => 'Aufnahmen aufs Telefon zu holen ist praktisch. Worauf du bei Zugangsdaten, Übertragung und Aktualisierungen achten solltest.',
                    'meta_title' => 'Dashcam per Funkverbindung und App nutzen | DashClip Delivery',
                    'meta_description' => 'Zugangsdaten ändern, Übertragung absichern, Aktualisierungen einspielen: der sichere Umgang mit Kamera-Apps.',
                ],
                'en' => [
                    'slug' => 'wireless-and-app-access',
                    'title' => 'Wireless and app access: convenient, not carefree',
                    'excerpt' => 'Pulling recordings onto your phone is practical. What to watch for around credentials, transfer and updates.',
                    'meta_title' => 'Using a dashcam over wireless and apps | DashClip Delivery',
                    'meta_description' => 'Change the credentials, secure the transfer, install updates: handling camera apps safely.',
                ],
            ],
        ],
        'bulk-upload' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'clip-submission'],
            'translations' => [
                'de' => [
                    'slug' => 'mehrere-clips-auf-einmal',
                    'title' => 'Mehrere Clips auf einmal: Sammelupload und Archivdatei',
                    'excerpt' => 'Wenn nach einer längeren Fahrt mehrere Szenen zusammenkommen, lohnt sich der Sammelweg. Wie du ihn nutzt und was du vorbereiten solltest.',
                    'meta_title' => 'Mehrere Clips auf einmal hochladen | DashClip Delivery',
                    'meta_description' => 'Sammelupload und Archivdateien: wie du mehrere Clips auf einmal einsendest und den Überblick behältst.',
                ],
                'en' => [
                    'slug' => 'uploading-several-clips-at-once',
                    'title' => 'Several clips at once: bulk upload and archives',
                    'excerpt' => 'When a longer drive produces several scenes, the bulk route pays off. How to use it and what to prepare beforehand.',
                    'meta_title' => 'Uploading several clips at once | DashClip Delivery',
                    'meta_description' => 'Bulk upload and archive files: how to submit several clips at once and keep track of them.',
                ],
            ],
        ],
        'camera-software-updates' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'aktualisierungen-der-kamerasoftware',
                    'title' => 'Aktualisierungen der Kamerasoftware: warum sie zählen',
                    'excerpt' => 'Kaum jemand aktualisiert seine Dashcam. Dabei beheben die Aktualisierungen oft genau die Fehler, die im Ernstfall die Aufnahme kosten.',
                    'meta_title' => 'Dashcam-Software aktuell halten | DashClip Delivery',
                    'meta_description' => 'Warum Aktualisierungen der Kamerasoftware Abstürze verhindern und wie du sie gefahrlos einspielst.',
                ],
                'en' => [
                    'slug' => 'camera-software-updates',
                    'title' => 'Camera software updates: why they count',
                    'excerpt' => 'Hardly anyone updates their dashcam. Yet updates often fix exactly the faults that cost you a recording when it matters.',
                    'meta_title' => 'Keeping dashcam software current | DashClip Delivery',
                    'meta_description' => 'Why camera software updates prevent crashes and freezes, and how to install them safely.',
                ],
            ],
        ],
        'slow-connections' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission'],
            'translations' => [
                'de' => [
                    'slug' => 'hochladen-ueber-langsame-verbindungen',
                    'title' => 'Hochladen über langsame Verbindungen',
                    'excerpt' => 'Große Dateien und schmale Leitungen vertragen sich schlecht. Was hilft, damit ein Upload auch unterwegs ankommt.',
                    'meta_title' => 'Clips über langsame Verbindungen hochladen | DashClip Delivery',
                    'meta_description' => 'Dateigröße, Zeitpunkt und Geduld: wie ein Upload auch über eine schmale Leitung zuverlässig ankommt.',
                ],
                'en' => [
                    'slug' => 'uploading-over-slow-connections',
                    'title' => 'Uploading over slow connections',
                    'excerpt' => 'Large files and narrow pipes do not get along. What helps an upload finish even when you are on the road.',
                    'meta_title' => 'Uploading clips over slow connections | DashClip Delivery',
                    'meta_description' => 'File size, timing and patience: how to get an upload through a narrow connection reliably.',
                ],
            ],
        ],
        'heat-cold-humidity' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'hitze-kaelte-feuchtigkeit',
                    'title' => 'Hitze, Kälte, Feuchtigkeit: was der Technik zusetzt',
                    'excerpt' => 'Hinter der Windschutzscheibe herrschen Bedingungen, für die kaum ein Gerät gebaut ist. Was das mit deiner Kamera macht.',
                    'meta_title' => 'Hitze, Kälte und Feuchtigkeit an der Dashcam | DashClip Delivery',
                    'meta_description' => 'Sommerhitze, Frost und Kondenswasser: was die Umgebungsbedingungen mit einer Dashcam anstellen.',
                ],
                'en' => [
                    'slug' => 'heat-cold-and-humidity',
                    'title' => 'Heat, cold and humidity: what wears the hardware down',
                    'excerpt' => 'Behind a windscreen the conditions are harsher than most devices are built for. What that does to your camera over time.',
                    'meta_title' => 'Heat, cold and humidity around a dashcam | DashClip Delivery',
                    'meta_description' => 'Summer heat, frost and condensation: what ambient conditions do to a dashcam over time.',
                ],
            ],
        ],
        'dashcam-on-a-motorcycle' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'dashcam-am-motorrad',
                    'title' => 'Dashcam am Motorrad: was anders ist',
                    'excerpt' => 'Vibration, Wetter und fehlender Innenraum stellen andere Anforderungen. Worauf es bei einer Kamera am Zweirad ankommt.',
                    'meta_title' => 'Dashcam am Motorrad nutzen | DashClip Delivery',
                    'meta_description' => 'Vibration, Wetterschutz und Stromversorgung: was eine Kamera am Motorrad von einer im Auto unterscheidet.',
                ],
                'en' => [
                    'slug' => 'dashcams-on-a-motorcycle',
                    'title' => 'Dashcams on a motorcycle: what is different',
                    'excerpt' => 'Vibration, weather and the lack of a cabin make different demands. What matters for a camera on two wheels.',
                    'meta_title' => 'Using a dashcam on a motorcycle | DashClip Delivery',
                    'meta_description' => 'Vibration, weather sealing and power: how a motorcycle camera differs from one in a car.',
                ],
            ],
        ],
        'two-languages' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'account'],
            'translations' => [
                'de' => [
                    'slug' => 'plattform-auf-deutsch-und-englisch',
                    'title' => 'Die Plattform auf Deutsch und Englisch nutzen',
                    'excerpt' => 'Wie die Sprachauswahl funktioniert, was übersetzt ist und was bewusst in der Sprache bleibt, in der es eingegeben wurde.',
                    'meta_title' => 'Die Plattform auf Deutsch und Englisch | DashClip Delivery',
                    'meta_description' => 'Sprachauswahl, übersetzte Oberfläche und Inhalte in der Originalsprache: wie die Zweisprachigkeit funktioniert.',
                ],
                'en' => [
                    'slug' => 'using-the-platform-in-two-languages',
                    'title' => 'Using the platform in German and English',
                    'excerpt' => 'How the language switch works, what is translated and what deliberately stays in the language it was entered in.',
                    'meta_title' => 'The platform in German and English | DashClip Delivery',
                    'meta_description' => 'Language switching, a translated interface and content kept in its original language: how bilingual use works.',
                ],
            ],
        ],
        'dashcam-in-a-motorhome' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'europe'],
            'translations' => [
                'de' => [
                    'slug' => 'dashcam-im-wohnmobil',
                    'title' => 'Dashcam im Wohnmobil und auf langen Reisen',
                    'excerpt' => 'Lange Etappen, wechselnde Länder und Standzeiten auf dem Stellplatz: Worauf es bei einer Kamera im Reisemobil besonders ankommt.',
                    'meta_title' => 'Dashcam im Wohnmobil nutzen | DashClip Delivery',
                    'meta_description' => 'Lange Etappen, Landeswechsel und Standzeiten: was bei einer Dashcam im Reisemobil zu bedenken ist.',
                ],
                'en' => [
                    'slug' => 'dashcams-in-a-motorhome',
                    'title' => 'Dashcams in a motorhome and on long trips',
                    'excerpt' => 'Long legs, changing countries and days parked on a pitch: what matters most for a camera in a motorhome.',
                    'meta_title' => 'Using a dashcam in a motorhome | DashClip Delivery',
                    'meta_description' => 'Long legs, border crossings and long stays parked: what to consider for a dashcam in a motorhome.',
                ],
            ],
        ],
        'combining-cameras' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission', 'video-quality'],
            'translations' => [
                'de' => [
                    'slug' => 'clips-aus-mehreren-kameras',
                    'title' => 'Clips aus mehreren Kameras kombinieren',
                    'excerpt' => 'Zwei Perspektiven erzählen mehr als eine, wenn sie zusammenpassen. Wie du Front und Heck sinnvoll zu einer Einsendung verbindest.',
                    'meta_title' => 'Clips aus mehreren Kameras kombinieren | DashClip Delivery',
                    'meta_description' => 'Zeitgleiche Aufnahmen aus Front und Heck: wie du mehrere Perspektiven zu einer Einsendung verbindest.',
                ],
                'en' => [
                    'slug' => 'combining-clips-from-several-cameras',
                    'title' => 'Combining clips from several cameras',
                    'excerpt' => 'Two perspectives tell more than one, provided they line up. How to combine front and rear footage into a single submission.',
                    'meta_title' => 'Combining clips from several cameras | DashClip Delivery',
                    'meta_description' => 'Simultaneous front and rear recordings: how to turn several perspectives into one coherent submission.',
                ],
            ],
        ],
        'dashcam-in-a-company-car' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'data-protection'],
            'translations' => [
                'de' => [
                    'slug' => 'dashcam-im-firmenwagen',
                    'title' => 'Dashcam im Firmenwagen: was zu beachten ist',
                    'excerpt' => 'Im Dienstwagen sitzt nicht nur der Fahrer mit im Bild, sondern auch das Arbeitsverhältnis. Warum hier andere Fragen zu klären sind.',
                    'meta_title' => 'Dashcam im Firmenwagen | DashClip Delivery',
                    'meta_description' => 'Zustimmung, Mitbestimmung und Zweckbindung: was bei einer Dashcam im Dienstwagen zu klären ist.',
                ],
                'en' => [
                    'slug' => 'dashcams-in-a-company-car',
                    'title' => 'Dashcams in a company car: what to consider',
                    'excerpt' => 'In a company vehicle the employment relationship travels along. Why a camera raises different questions there.',
                    'meta_title' => 'Dashcams in a company car | DashClip Delivery',
                    'meta_description' => 'Consent, staff representation and purpose limitation: what to settle before fitting a camera to a company car.',
                ],
            ],
        ],
        'dashcam-in-a-rental-car' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'europe'],
            'translations' => [
                'de' => [
                    'slug' => 'dashcam-im-mietwagen',
                    'title' => 'Dashcam im Mietwagen',
                    'excerpt' => 'Ein fremdes Fahrzeug, ein fremdes Land und ein Mietvertrag: Worauf du achtest, wenn du deine Kamera mit in den Urlaub nimmst.',
                    'meta_title' => 'Dashcam im Mietwagen nutzen | DashClip Delivery',
                    'meta_description' => 'Mietvertrag, Befestigung ohne Spuren und Regeln vor Ort: die Dashcam im Mietwagen richtig einsetzen.',
                ],
                'en' => [
                    'slug' => 'dashcams-in-a-rental-car',
                    'title' => 'Dashcams in a rental car',
                    'excerpt' => 'Someone else vehicle, another country and a rental agreement: what to watch for when your camera comes on holiday.',
                    'meta_title' => 'Using a dashcam in a rental car | DashClip Delivery',
                    'meta_description' => 'Rental terms, mounting without marks and local rules: using a dashcam in a rental car properly.',
                ],
            ],
        ],
        'reporting-problems' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'account'],
            'translations' => [
                'de' => [
                    'slug' => 'fehler-melden-und-hilfe-bekommen',
                    'title' => 'Wenn etwas schiefgeht: Fehler melden und Hilfe bekommen',
                    'excerpt' => 'Ein abgebrochener Upload, eine fehlende Nachricht, ein unerwarteter Status: Welche Angaben eine schnelle Klärung möglich machen.',
                    'meta_title' => 'Fehler melden und Hilfe bekommen | DashClip Delivery',
                    'meta_description' => 'Was du bei einer Störung festhalten solltest, damit sich der Fehler schnell nachvollziehen und beheben lässt.',
                ],
                'en' => [
                    'slug' => 'reporting-problems-and-getting-help',
                    'title' => 'When something goes wrong: reporting problems and getting help',
                    'excerpt' => 'An upload that stops, a message that never arrives, a status you did not expect: which details make a quick answer possible.',
                    'meta_title' => 'Reporting problems and getting help | DashClip Delivery',
                    'meta_description' => 'What to note down when something breaks, so the problem can be traced and fixed quickly.',
                ],
            ],
        ],
        'near-misses' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'evidence'],
            'translations' => [
                'de' => [
                    'slug' => 'beinaheunfaelle',
                    'title' => 'Beinaheunfälle: warum sie trotzdem wertvoll sind',
                    'excerpt' => 'Nichts ist passiert, und genau das macht die Aufnahme interessant. Was Beinaheunfälle zeigen und wofür sie taugen.',
                    'meta_title' => 'Beinaheunfälle auf Dashcam-Aufnahmen | DashClip Delivery',
                    'meta_description' => 'Warum Aufnahmen von Beinaheunfällen lehrreich sind und welchen Wert sie über den Einzelfall hinaus haben.',
                ],
                'en' => [
                    'slug' => 'near-misses',
                    'title' => 'Near misses: why they are valuable anyway',
                    'excerpt' => 'Nothing happened, and that is exactly what makes the recording interesting. What near misses show and what they are good for.',
                    'meta_title' => 'Near misses on dashcam footage | DashClip Delivery',
                    'meta_description' => 'Why recordings of near misses are instructive and what value they carry beyond the single incident.',
                ],
            ],
        ],
        'time-and-place' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission', 'evidence'],
            'translations' => [
                'de' => [
                    'slug' => 'uhrzeit-und-ort',
                    'title' => 'Uhrzeit und Ort: warum Metadaten zählen',
                    'excerpt' => 'Eine falsch gestellte Uhr entwertet eine gute Aufnahme. Warum Zeit und Ort zur Einsendung gehören und wie du sie richtig festhältst.',
                    'meta_title' => 'Uhrzeit und Ort bei Einsendungen | DashClip Delivery',
                    'meta_description' => 'Warum eine korrekt gestellte Uhr und eine saubere Ortsangabe deine Einsendung deutlich wertvoller machen.',
                ],
                'en' => [
                    'slug' => 'time-and-place',
                    'title' => 'Time and place: why metadata counts',
                    'excerpt' => 'A clock set wrong devalues good footage. Why time and place belong with a submission and how to record them properly.',
                    'meta_title' => 'Time and place in submissions | DashClip Delivery',
                    'meta_description' => 'Why a correctly set clock and a clear location make your submission considerably more valuable.',
                ],
            ],
        ],
        'wildlife-and-stone-chips' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'evidence'],
            'translations' => [
                'de' => [
                    'slug' => 'wildunfall-steinschlag-marderschaden',
                    'title' => 'Wildunfall, Steinschlag, Marderschaden',
                    'excerpt' => 'Nicht jeder Schaden entsteht im Verkehr mit anderen. Wo eine Aufnahme auch bei Schäden ohne Gegner weiterhilft.',
                    'meta_title' => 'Wildunfall, Steinschlag und Marderschaden | DashClip Delivery',
                    'meta_description' => 'Schäden ohne Unfallgegner: wann eine Aufnahme bei Wildunfall, Steinschlag oder Tierschäden hilft.',
                ],
                'en' => [
                    'slug' => 'wildlife-stone-chips-and-animal-damage',
                    'title' => 'Wildlife collisions, stone chips and animal damage',
                    'excerpt' => 'Not every loss involves another road user. Where footage still helps when there is no other party to point at.',
                    'meta_title' => 'Wildlife, stone chips and animal damage | DashClip Delivery',
                    'meta_description' => 'Damage with no other party involved: when footage helps after wildlife collisions, stone chips or animal damage.',
                ],
            ],
        ],
        'tailgating' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'evidence'],
            'translations' => [
                'de' => [
                    'slug' => 'draengeln-und-noetigung',
                    'title' => 'Drängeln und Nötigung: was eine Aufnahme zeigen kann',
                    'excerpt' => 'Dichtes Auffahren ist schwer zu beschreiben und leicht zu filmen. Was eine Aufnahme belegen kann und was sie offen lässt.',
                    'meta_title' => 'Drängeln und Nötigung auf Aufnahmen | DashClip Delivery',
                    'meta_description' => 'Abstand, Dauer und Geschwindigkeit: was eine Aufnahme bei dichtem Auffahren zeigen kann und was nicht.',
                ],
                'en' => [
                    'slug' => 'tailgating-and-intimidation',
                    'title' => 'Tailgating: what a recording can show',
                    'excerpt' => 'Close following is hard to describe and easy to film. What a recording can establish, and what it leaves open.',
                    'meta_title' => 'Tailgating on dashcam footage | DashClip Delivery',
                    'meta_description' => 'Distance, duration and speed: what footage of close following can show, and what it cannot.',
                ],
            ],
        ],
        'search-and-find' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'getting-started'],
            'translations' => [
                'de' => [
                    'slug' => 'suchen-und-finden',
                    'title' => 'Suchen und finden: Beiträge, Kanäle, Angebote',
                    'excerpt' => 'Je mehr zusammenkommt, desto wichtiger wird das Wiederfinden. Welche Suchwege es gibt und wofür sich welcher eignet.',
                    'meta_title' => 'Suchen und finden auf der Plattform | DashClip Delivery',
                    'meta_description' => 'Artikelsuche, Kategorien und die Übersicht deiner Angebote: die Suchwege der Plattform im Überblick.',
                ],
                'en' => [
                    'slug' => 'search-and-find',
                    'title' => 'Search and find: articles, channels, offers',
                    'excerpt' => 'The more that accumulates, the more finding things again matters. Which search routes exist and what each one suits.',
                    'meta_title' => 'Search and find on the platform | DashClip Delivery',
                    'meta_description' => 'Article search, categories and your offer overview: the ways to find things again on the platform.',
                ],
            ],
        ],
        'blocked-lanes' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'evidence'],
            'translations' => [
                'de' => [
                    'slug' => 'falschparker-und-rettungsgasse',
                    'title' => 'Falschparker und Rettungsgasse: filmen und melden',
                    'excerpt' => 'Eine blockierte Rettungsgasse oder ein zugeparkter Radweg sind schnell gefilmt. Was du damit tun kannst und was du besser lässt.',
                    'meta_title' => 'Falschparker und Rettungsgasse filmen | DashClip Delivery',
                    'meta_description' => 'Blockierte Rettungsgasse, zugeparkter Radweg: was eine Aufnahme leisten kann und wo Zurückhaltung angebracht ist.',
                ],
                'en' => [
                    'slug' => 'blocked-lanes-and-illegal-parking',
                    'title' => 'Blocked lanes and illegal parking: filming and reporting',
                    'excerpt' => 'A blocked emergency lane or an obstructed cycle path is quickly filmed. What you can do with that, and what to leave alone.',
                    'meta_title' => 'Filming blocked lanes and illegal parking | DashClip Delivery',
                    'meta_description' => 'Blocked emergency lanes and obstructed cycle paths: what footage achieves and where restraint is wiser.',
                ],
            ],
        ],
        'your-clip-archive' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission'],
            'translations' => [
                'de' => [
                    'slug' => 'dein-clip-archiv',
                    'title' => 'Dein Clip-Archiv: Dateinamen und Ordnung',
                    'excerpt' => 'Nach ein paar Monaten weiß niemand mehr, was in Datei 20260912_141233 steckt. Eine einfache Ordnung, die das verhindert.',
                    'meta_title' => 'Dein Clip-Archiv ordnen | DashClip Delivery',
                    'meta_description' => 'Dateinamen, Ordnerstruktur und eine kurze Notiz je Clip: so bleibt dein Archiv auch nach Monaten nutzbar.',
                ],
                'en' => [
                    'slug' => 'your-clip-archive',
                    'title' => 'Your clip archive: file names and order',
                    'excerpt' => 'After a few months nobody remembers what sits in file 20260912_141233. A simple scheme that prevents exactly that.',
                    'meta_title' => 'Organising your clip archive | DashClip Delivery',
                    'meta_description' => 'File names, folder structure and a short note per clip: keeping an archive usable months later.',
                ],
            ],
        ],
        'what-the-camera-misses' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'video-quality'],
            'translations' => [
                'de' => [
                    'slug' => 'was-die-kamera-nicht-sieht',
                    'title' => 'Was die Kamera nicht sieht: Grenzen jeder Aufnahme',
                    'excerpt' => 'Eine Aufnahme wirkt objektiv, zeigt aber immer nur einen Ausschnitt. Welche Verzerrungen dabei entstehen und was daraus folgt.',
                    'meta_title' => 'Grenzen von Dashcam-Aufnahmen | DashClip Delivery',
                    'meta_description' => 'Blickwinkel, Verzerrung und toter Winkel: warum eine Aufnahme nie die ganze Situation abbildet.',
                ],
                'en' => [
                    'slug' => 'what-the-camera-does-not-see',
                    'title' => 'What the camera does not see: the limits of any recording',
                    'excerpt' => 'Footage feels objective, yet it only ever shows a section. Which distortions come with that and what follows from them.',
                    'meta_title' => 'The limits of dashcam footage | DashClip Delivery',
                    'meta_description' => 'Field of view, distortion and blind spots: why a recording never captures the whole situation.',
                ],
            ],
        ],
        'two-cameras-in-sync' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'zwei-kameras-synchron-nutzen',
                    'title' => 'Zwei Kameras synchron nutzen',
                    'excerpt' => 'Zwei Geräte, zwei Uhren, zwei Dateiablagen: Wie du dafür sorgst, dass die Aufnahmen später zusammenpassen.',
                    'meta_title' => 'Zwei Dashcams synchron betreiben | DashClip Delivery',
                    'meta_description' => 'Uhrzeit abgleichen, Dateien zuordnen, Aufnahmen zusammenführen: zwei Kameras sinnvoll parallel nutzen.',
                ],
                'en' => [
                    'slug' => 'running-two-cameras-in-sync',
                    'title' => 'Running two cameras in sync',
                    'excerpt' => 'Two devices, two clocks, two sets of files: how to make sure the recordings still line up afterwards.',
                    'meta_title' => 'Running two dashcams in sync | DashClip Delivery',
                    'meta_description' => 'Align the clocks, match the files, merge the footage: running two cameras in parallel without confusion.',
                ],
            ],
        ],
        'reading-offer-status' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'clip-submission'],
            'translations' => [
                'de' => [
                    'slug' => 'angebotsstatus-lesen',
                    'title' => 'Rückmeldungen richtig lesen: Zusage, Absage, Rückläufer',
                    'excerpt' => 'In deiner Übersicht steht mehr, als es auf den ersten Blick scheint. Was die einzelnen Zustände bedeuten und wann du selbst aktiv wirst.',
                    'meta_title' => 'Angebotsstatus richtig lesen | DashClip Delivery',
                    'meta_description' => 'Zusage, Absage, Rückläufer und abgelaufene Angebote: was die Zustände in deiner Übersicht bedeuten.',
                ],
                'en' => [
                    'slug' => 'reading-offer-status',
                    'title' => 'Reading responses correctly: accepted, declined, returned',
                    'excerpt' => 'Your overview says more than it appears to at first glance. What each state means and when it is your turn to act.',
                    'meta_title' => 'Reading offer status correctly | DashClip Delivery',
                    'meta_description' => 'Accepted, declined, returned and expired: what the states in your overview actually mean.',
                ],
            ],
        ],
        'archiving-footage' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'data-protection'],
            'translations' => [
                'de' => [
                    'slug' => 'aufnahmen-archivieren',
                    'title' => 'Aufnahmen archivieren: Ordnung, Sicherung, Löschfristen',
                    'excerpt' => 'Alles aufzuheben ist keine Strategie. Wie lange du Aufnahmen behältst, wo du sie sicherst und wann du sie löschst.',
                    'meta_title' => 'Dashcam-Aufnahmen archivieren | DashClip Delivery',
                    'meta_description' => 'Sicherung, Aufbewahrungsdauer und Löschen: ein tragfähiger Umgang mit gesicherten Aufnahmen.',
                ],
                'en' => [
                    'slug' => 'archiving-footage',
                    'title' => 'Archiving footage: order, backups, deletion',
                    'excerpt' => 'Keeping everything is not a strategy. How long to keep recordings, where to back them up and when to delete them.',
                    'meta_title' => 'Archiving dashcam footage | DashClip Delivery',
                    'meta_description' => 'Backups, retention periods and deletion: a workable approach to the recordings you have secured.',
                ],
            ],
        ],
        'first-five-submissions' => [
            'category' => 'submission-tips',
            'tags' => ['clip-submission', 'getting-started'],
            'translations' => [
                'de' => [
                    'slug' => 'deine-ersten-fuenf-einsendungen',
                    'title' => 'Deine ersten fünf Einsendungen: ein Fahrplan',
                    'excerpt' => 'Aller Anfang braucht keine Perfektion, sondern Routine. Ein Fahrplan, mit dem die ersten Einsendungen sicher gelingen.',
                    'meta_title' => 'Deine ersten fünf Einsendungen | DashClip Delivery',
                    'meta_description' => 'Ein praktischer Fahrplan für die ersten fünf Einsendungen, von der Auswahl bis zur Rückmeldung.',
                ],
                'en' => [
                    'slug' => 'your-first-five-submissions',
                    'title' => 'Your first five submissions: a plan',
                    'excerpt' => 'Getting started needs routine rather than perfection. A plan that carries your first submissions safely through.',
                    'meta_title' => 'Your first five submissions | DashClip Delivery',
                    'meta_description' => 'A practical plan for your first five submissions, from picking a clip to reading the response.',
                ],
            ],
        ],
        'dashcam-myths' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'data-protection'],
            'translations' => [
                'de' => [
                    'slug' => 'dashcam-mythen',
                    'title' => 'Dashcam-Mythen im Faktencheck',
                    'excerpt' => 'Dashcams sind verboten, Aufnahmen zählen nie, eine Kamera schützt vor allem: Sechs verbreitete Behauptungen und was davon stimmt.',
                    'meta_title' => 'Dashcam-Mythen im Faktencheck | DashClip Delivery',
                    'meta_description' => 'Sechs verbreitete Behauptungen über Dashcams, geprüft und richtiggestellt.',
                ],
                'en' => [
                    'slug' => 'dashcam-myths',
                    'title' => 'Dashcam myths, fact checked',
                    'excerpt' => 'Dashcams are banned, footage never counts, a camera protects you from everything: six common claims and what is left of them.',
                    'meta_title' => 'Dashcam myths fact checked | DashClip Delivery',
                    'meta_description' => 'Six widespread claims about dashcams, checked and put straight.',
                ],
            ],
        ],
        'camera-positioning' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'kamera-richtig-positionieren',
                    'title' => 'Die Kamera richtig positionieren: Sichtfeld und Vorschriften',
                    'excerpt' => 'Die Scheibe ist kein beliebiger Montageplatz. Wo die Kamera sitzen darf, ohne deine Sicht oder die Vorschriften zu verletzen.',
                    'meta_title' => 'Dashcam richtig positionieren | DashClip Delivery',
                    'meta_description' => 'Sichtfeld des Fahrers, Wischerbereich und Vorschriften: wo eine Dashcam an der Scheibe sitzen darf.',
                ],
                'en' => [
                    'slug' => 'positioning-the-camera',
                    'title' => 'Positioning the camera: field of view and regulations',
                    'excerpt' => 'The windscreen is not an arbitrary mounting surface. Where a camera may sit without blocking your view or breaking the rules.',
                    'meta_title' => 'Positioning a dashcam correctly | DashClip Delivery',
                    'meta_description' => 'Driver field of view, wiper area and regulations: where a dashcam may sit on the windscreen.',
                ],
            ],
        ],
        'monthly-dashcam-check' => [
            'category' => 'dashcam-knowledge',
            'tags' => ['dashcams', 'setup'],
            'translations' => [
                'de' => [
                    'slug' => 'monatlicher-dashcam-check',
                    'title' => 'Deine Dashcam-Routine: der monatliche Kurzcheck',
                    'excerpt' => 'Fünf Minuten im Monat verhindern die böse Überraschung im Ernstfall. Eine kurze Liste, die du jedes Mal gleich abarbeitest.',
                    'meta_title' => 'Der monatliche Dashcam-Check | DashClip Delivery',
                    'meta_description' => 'Aufnahme prüfen, Uhrzeit abgleichen, Karte kontrollieren: der Kurzcheck für einmal im Monat.',
                ],
                'en' => [
                    'slug' => 'your-monthly-dashcam-check',
                    'title' => 'Your dashcam routine: the monthly quick check',
                    'excerpt' => 'Five minutes a month prevent the nasty surprise when it counts. A short list to work through the same way every time.',
                    'meta_title' => 'The monthly dashcam check | DashClip Delivery',
                    'meta_description' => 'Check the footage, verify the clock, inspect the card: a five minute routine once a month.',
                ],
            ],
        ],
        'blog-review' => [
            'category' => 'news',
            'tags' => ['dashclip-delivery', 'updates'],
            'translations' => [
                'de' => [
                    'slug' => 'ein-jahr-blog',
                    'title' => 'Ein Jahr Blog: Rückblick und Ausblick',
                    'excerpt' => 'Was in diesem Jahr an Themen zusammengekommen ist, welche Beiträge am häufigsten gelesen wurden und wie es weitergeht.',
                    'meta_title' => 'Ein Jahr DashClip-Blog | DashClip Delivery',
                    'meta_description' => 'Rückblick auf ein Jahr Beiträge zu Plattform, Einsendungen und Dashcam-Wissen, dazu ein Ausblick.',
                ],
                'en' => [
                    'slug' => 'a-year-of-the-blog',
                    'title' => 'A year of the blog: looking back and ahead',
                    'excerpt' => 'Which topics came together over the year, which articles were read most and where things go from here.',
                    'meta_title' => 'A year of the DashClip blog | DashClip Delivery',
                    'meta_description' => 'Looking back on a year of articles about the platform, submissions and dashcam knowledge, plus what is next.',
                ],
            ],
        ],
    ],
];
