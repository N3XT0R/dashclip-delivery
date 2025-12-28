<?php

declare(strict_types=1);

namespace Tests\Integration\Application;

use App\Application\Offer\ReturnAssignment;
use App\Models\Assignment;
use App\Models\User;
use App\Repository\AssignmentRepository;
use App\Services\AssignmentService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\DatabaseTestCase;

final class ReturnAssignmentTest extends DatabaseTestCase
{
    public function testReturnsEachAssignmentUsingService(): void
    {
        $assignments = Assignment::factory()->count(2)->create();

        $assignmentService = $this->fakeAssignmentService();
        $this->instance(AssignmentService::class, $assignmentService);

        $useCase = $this->app->make(ReturnAssignment::class);
        $useCase->handle(collect($assignments));

        $this->assertCount(2, $assignmentService->returnedAssignments);
        $this->assertSame($assignments[0]->getKey(), $assignmentService->returnedAssignments[0]->getKey());
        $this->assertSame($assignments[1]->getKey(), $assignmentService->returnedAssignments[1]->getKey());
    }

    public function testIgnoresNonAssignmentValues(): void
    {
        $assignment = Assignment::factory()->create();

        $assignmentService = $this->fakeAssignmentService();
        $this->instance(AssignmentService::class, $assignmentService);

        $useCase = $this->app->make(ReturnAssignment::class);
        $useCase->handle(new Collection([
            $assignment,
            'not-an-assignment',
            123,
            null,
        ]));

        $this->assertCount(1, $assignmentService->returnedAssignments);
        $this->assertSame($assignment->getKey(), $assignmentService->returnedAssignments[0]->getKey());
    }

    public function testHandlesEmptyCollectionWithoutCallingService(): void
    {
        $assignmentService = $this->fakeAssignmentService();
        $this->instance(AssignmentService::class, $assignmentService);

        $useCase = $this->app->make(ReturnAssignment::class);
        $useCase->handle(collect());

        $this->assertCount(0, $assignmentService->returnedAssignments);
    }

    private function fakeAssignmentService(): AssignmentService
    {
        $repository = Mockery::mock(AssignmentRepository::class);

        return new readonly class($repository) extends AssignmentService {
            /** @var Collection<int, Assignment> */
            public Collection $returnedAssignments;

            public function __construct(AssignmentRepository $repository)
            {
                parent::__construct($repository);
                $this->returnedAssignments = new Collection();
            }

            public function returnAssignment(Assignment $assignment, ?User $user = null): bool
            {
                $this->returnedAssignments->push($assignment);

                return true;
            }
        };
    }
}
