<?php

declare(strict_types=1);

namespace Tests\Integration\Observers;

use App\Events\ActionToken\ActionTokenConsumed;
use App\Models\ActionToken;
use App\Observers\ActionTokenObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\DatabaseTestCase;

final class ActionTokenObserverTest extends DatabaseTestCase
{
    public function testRegistersEventWhenUsedAtIsSetFirstTime(): void
    {
        Event::fake();

        $observer = new ActionTokenObserver;

        $token = ActionToken::factory()->make();
        $token->setRawAttributes(['used_at' => null], true);
        $token->used_at = now();
        $token->syncChanges();

        DB::beginTransaction();
        $observer->updated($token);
        DB::commit();

        Event::assertDispatched(
            ActionTokenConsumed::class,
            static fn(ActionTokenConsumed $event) => $event->token === $token
        );
    }

    public function testDoesNotRegisterEventWhenUsedAtIsUnchanged(): void
    {
        Event::fake();

        $observer = new ActionTokenObserver;

        $token = ActionToken::factory()->make();
        $token->setRawAttributes(['used_at' => null], true);
        $token->syncChanges();

        DB::beginTransaction();
        $observer->updated($token);
        DB::commit();

        Event::assertNotDispatched(ActionTokenConsumed::class);
    }

    public function testDoesNotRegisterEventWhenUsedAtWasAlreadySet(): void
    {
        Event::fake();

        $observer = new ActionTokenObserver;

        $token = ActionToken::factory()->make();
        $token->setRawAttributes(['used_at' => now()->subMinute()], true);
        $token->used_at = now();
        $token->syncChanges();

        DB::beginTransaction();
        $observer->updated($token);
        DB::commit();

        Event::assertNotDispatched(ActionTokenConsumed::class);
    }

    public function testDoesNotRegisterEventWhenUsedAtIsNullAfterUpdate(): void
    {
        Event::fake();

        $observer = new ActionTokenObserver;

        $token = ActionToken::factory()->make();
        $token->setRawAttributes(['used_at' => now()], true);
        $token->used_at = null;
        $token->syncChanges();

        DB::beginTransaction();
        $observer->updated($token);
        DB::commit();

        Event::assertNotDispatched(ActionTokenConsumed::class);
    }
}
