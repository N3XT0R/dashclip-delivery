<?php

declare(strict_types=1);

namespace App\Support\Changelog;

use DOMDocument;
use Illuminate\Support\Facades\File;
use MarkWalet\Changelog\Adapters\XmlFeatureAdapter;
use MarkWalet\Changelog\Feature;

/**
 * Writes change fragments with escaped messages.
 *
 * The package adapter passes the message as raw element content, so an "&" or "<" in a message
 * breaks or truncates the fragment. Messages are written as text nodes here instead.
 */
final class EscapedXmlFeatureAdapter extends XmlFeatureAdapter
{
    /**
     * @param string $path
     * @param Feature $feature
     * @return void
     */
    public function write(string $path, Feature $feature): void
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->createElement('feature');

        foreach ($feature->changes() as $change) {
            $element = $document->createElement('change');
            $element->setAttribute('type', $change->type());
            $element->appendChild($document->createTextNode($change->message()));
            $root->appendChild($element);
        }

        $document->appendChild($root);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, (string)$document->saveXML());
    }
}
