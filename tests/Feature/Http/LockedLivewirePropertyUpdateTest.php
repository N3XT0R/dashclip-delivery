<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Tests\TestCase;

/**
 * A client update of a locked component property is a rejected request, not a server error.
 */
final class LockedLivewirePropertyUpdateTest extends TestCase
{
    public function testUpdateOfLockedPropertyIsRejectedAsBadRequestWithoutBeingReported(): void
    {
        Exceptions::fake();
        Route::post('/_test/locked-property', static function (): never {
            throw new CannotUpdateLockedPropertyException('discoveredSchemaNames');
        });

        $this->postJson('/_test/locked-property')->assertBadRequest();

        Exceptions::assertNothingReported();
    }
}
