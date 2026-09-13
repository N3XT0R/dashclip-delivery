<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Enum\StatusEnum;
use App\Enum\TokenPurposeEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Video;
use App\Services\ActionTokenService;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\URL;
use Tests\DatabaseTestCase;

final class PublicTransactionalPagesTest extends DatabaseTestCase
{
    public function testSignedOfferAndReturnFormsKeepTheirActionsAndPrivacy(): void
    {
        $this->withoutVite();
        $batch = Batch::factory()->create(['type' => 'assign']);
        $channel = Channel::factory()->create();
        Assignment::factory()->for($batch)->for($channel)->for(Video::factory())->create(['status' => StatusEnum::QUEUED->value]);
        Assignment::factory()->for($batch)->for($channel)->for(Video::factory())->create(['status' => StatusEnum::PICKEDUP->value]);
        $this->get(route('offer.show', [$batch, $channel]))->assertForbidden();

        foreach (['offer.show' => 'zipPostUrl', 'offer.unused.show' => 'postUrl'] as $route => $actionKey) {
            $response = $this->get(URL::signedRoute($route, ['batch' => $batch, 'channel' => $channel]))->assertOk()->assertSee('noindex');
            $document = new DOMDocument();
            @$document->loadHTML($response->getContent());
            $xpath = new DOMXPath($document);
            $this->assertSame(1, $xpath->query('//h1')->length);
            $this->assertSame(0, $xpath->query('//link[@rel="canonical"] | //meta[@property="og:url"]')->length);
            $this->assertSame($response->viewData($actionKey), $xpath->query('//main//form[@method="POST"]')->item(0)->getAttribute('action'));
            $this->assertSame(1, $xpath->query('//main//form/input[@name="_token"]')->length);
            $this->assertGreaterThan(0, $xpath->query('//input[@name="assignment_ids[]"]')->length);
            $this->assertSame(0, $xpath->query('//label//video | //label//button | //label//a')->length);
        }
    }

    public function testTokenConfirmationRequiresExplicitPostAndOmitsSensitiveMetadata(): void
    {
        $this->withoutVite();
        $channel = Channel::factory()->create(['is_video_reception_paused' => true]);
        $service = $this->app->make(ActionTokenService::class);
        $purpose = TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION;
        $plainToken = $service->issue($purpose, subject: $channel, expiresAt: now()->addMonth());
        $parameters = ['purpose' => $purpose->value, 'token' => $plainToken];
        $response = $this->get(route('tokens.update', $parameters))->assertOk()->assertSee('noindex');
        $document = new DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame(0, $xpath->query('//link[@rel="canonical"] | //meta[@property="og:url"]')->length);
        $this->assertSame(route('tokens.store', $parameters), $xpath->query('//main//form[@method="POST"]')->item(0)->getAttribute('action'));
        $this->assertNotNull($service->findValid($purpose, $plainToken));
        $this->assertTrue($channel->fresh()->is_video_reception_paused);
        $this->post(route('tokens.store', $parameters))->assertOk();
        $this->assertFalse($channel->fresh()->is_video_reception_paused);
    }
}
