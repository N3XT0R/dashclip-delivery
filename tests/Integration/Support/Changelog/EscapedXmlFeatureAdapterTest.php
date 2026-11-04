<?php

declare(strict_types=1);

namespace Tests\Integration\Support\Changelog;

use App\Support\Changelog\EscapedXmlFeatureAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MarkWalet\Changelog\Adapters\FeatureAdapter;
use MarkWalet\Changelog\Change;
use MarkWalet\Changelog\Feature;
use Tests\TestCase;

final class EscapedXmlFeatureAdapterTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/changelog-' . Str::uuid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function testMessagesWithMarkupCharactersSurviveARoundTrip(): void
    {
        $adapter = $this->app->make(FeatureAdapter::class);
        $path = $this->root . '/unreleased/feature/x.xml';
        $message = "**Offers & downloads**: `<a>` -> b\n  continued";

        $adapter->write($path, new Feature([new Change('changed', $message)]));
        $changes = $adapter->read($path)->changes();

        $this->assertInstanceOf(EscapedXmlFeatureAdapter::class, $adapter);
        $this->assertSame($message, $changes[0]->message());
        $this->assertSame('changed', $changes[0]->type());
    }
}
