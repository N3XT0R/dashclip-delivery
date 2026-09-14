<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions\Blog;

use App\Exceptions\Blog\BlogException;
use App\Exceptions\Blog\TranslationMissingException;
use PHPUnit\Framework\TestCase;

final class TranslationMissingExceptionTest extends TestCase
{
    public function testMissingTranslationIdentifiesThePostAndRequestedLocale(): void
    {
        $exception = TranslationMissingException::forLocale(42, 'en');

        $this->assertInstanceOf(BlogException::class, $exception);
        $this->assertSame('Post "42" has no translation for locale "en".', $exception->getMessage());
    }
}
