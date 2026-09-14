<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\Channel;
use Tests\DatabaseTestCase;

final class PublicChannelLogoTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Channel::query()->update(['show_on_homepage' => false]);
    }

    public function testAChannelWithoutALogoKeepsTheNeutralSymbol(): void
    {
        Channel::factory()->create(['name' => 'Plain channel', 'logo_path' => null]);

        $body = $this->get('/')->assertOk()->assertSee('Plain channel')->getContent();

        self::assertStringContainsString('<svg', $body);
        self::assertStringNotContainsString('channel-logos/', $body);
    }

    public function testAChannelLogoIsRenderedFromTheStoredPath(): void
    {
        Channel::factory()->create([
            'name' => 'Logo channel',
            'logo_path' => 'channel-logos/example.webp',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('Logo channel')
            ->assertSee('channel-logos/example.webp', false);
    }

    public function testTheLogoIsConstrainedToTheSameBoxAsTheNeutralSymbol(): void
    {
        Channel::factory()->create([
            'name' => 'Sized channel',
            'logo_path' => 'channel-logos/sized.webp',
        ]);

        $body = $this->get('/')->assertOk()->getContent();
        $matched = preg_match('/<img[^>]+channel-logos\/sized\.webp[^>]*>/', $body, $match);

        self::assertSame(1, $matched, 'The channel logo is not rendered as an image tag.');
        self::assertStringContainsString('size-6', $match[0]);
        self::assertStringContainsString('object-contain', $match[0]);
        self::assertStringContainsString('alt=""', $match[0]);
    }

    public function testAHiddenChannelNeverExposesItsLogo(): void
    {
        Channel::factory()->create([
            'name' => 'Hidden channel',
            'logo_path' => 'channel-logos/hidden.webp',
            'show_on_homepage' => false,
        ]);

        $this->get('/')->assertOk()
            ->assertDontSee('Hidden channel')
            ->assertDontSee('channel-logos/hidden.webp', false);
    }
}
