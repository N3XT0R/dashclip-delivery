<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\PostTranslation;
use Illuminate\Support\Facades\DB;
use Tests\DatabaseTestCase;

final class BlogPerformanceTest extends DatabaseTestCase
{
    public function testQueryCountDoesNotGrowWithArticleCount(): void
    {
        $this->withoutVite();
        $this->withHeader('Accept-Language', 'de');
        PostTranslation::factory()->published()->count(3)->create();
        foreach (['/', '/blog'] as $url) {
            $this->get($url)->assertOk();
        }
        $before = [];
        foreach (['/', '/blog'] as $url) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->get($url)->assertOk();
            $before[$url] = count(DB::getQueryLog());
            DB::disableQueryLog();
        }
        PostTranslation::factory()->published()->count(6)->create();
        foreach (['/', '/blog'] as $url) {
            $this->get($url)->assertOk();
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->get($url)->assertOk();
            $this->assertSame($before[$url], count(DB::getQueryLog()), $url);
            DB::disableQueryLog();
        }
    }
}
