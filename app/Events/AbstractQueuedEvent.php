<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An event that is handled in the background and only once the change it reports is committed.
 * What it carries is up to the event itself, which keeps its own named property.
 */
abstract class AbstractQueuedEvent implements ShouldQueue, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;
}
