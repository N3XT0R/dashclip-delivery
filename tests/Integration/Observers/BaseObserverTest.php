<?php

declare(strict_types=1);

namespace Tests\Integration\Observers;

use App\Models\User;
use App\Observers\BaseObserver;
use Tests\DatabaseTestCase;

final class BaseObserverTest extends DatabaseTestCase
{
    public function testBaseObserverHooksDoNotMutateModel(): void
    {
        $model = User::factory()->make([
            'name' => 'Observer Base User',
            'email' => 'base@example.com',
        ]);

        $originalAttributes = $model->getAttributes();

        $observer = new class extends BaseObserver {
        };

        $observer->retrieved($model);
        $observer->creating($model);
        $observer->created($model);
        $observer->updating($model);
        $observer->updated($model);
        $observer->saving($model);
        $observer->saved($model);
        $observer->restoring($model);
        $observer->restored($model);
        $observer->forceDeleting($model);
        $observer->forceDeleted($model);
        $observer->replicating($model);

        $deletingResult = $observer->deleting($model);
        $observer->deleted($model);

        $this->assertSame($originalAttributes, $model->getAttributes());
        $this->assertNull($deletingResult);
    }
}
