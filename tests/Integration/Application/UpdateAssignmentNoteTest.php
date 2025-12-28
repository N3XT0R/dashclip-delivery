<?php

declare(strict_types=1);

namespace Tests\Integration\Application;

use App\Application\Assignment\UpdateAssignmentNote;
use App\Models\Activity;
use App\Models\Assignment;
use App\Models\User;
use Tests\DatabaseTestCase;

final class UpdateAssignmentNoteTest extends DatabaseTestCase
{
    public function testUpdatesAssignmentNoteWithCauser(): void
    {
        config(['activitylog.activity_model' => Activity::class]);

        $assignment = Assignment::factory()->create(['note' => 'old-note']);
        $user = User::factory()->create();

        $useCase = $this->app->make(UpdateAssignmentNote::class);
        $useCase->handle($assignment, 'new-note', $user);

        $updatedAssignment = $assignment->fresh();
        $this->assertSame('new-note', $updatedAssignment->note);

        $activity = Activity::query()
            ->where('description', 'Assignment note updated by channel')
            ->where('subject_id', $assignment->getKey())
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($user->getKey(), $activity->causer_id);
        $this->assertSame(Assignment::class, $activity->subject_type);

        $properties = $activity->properties->toArray();
        $this->assertSame($assignment->getKey(), $properties['assignment_id']);
        $this->assertSame($assignment->channel_id, $properties['channel_id']);
        $this->assertSame($assignment->video->original_name, $properties['video_name']);
        $this->assertArrayHasKey('note_updated_at', $properties);
    }

    public function testUpdatesAssignmentNoteWithNullUser(): void
    {
        config(['activitylog.activity_model' => Activity::class]);

        $assignment = Assignment::factory()->create(['note' => 'remove-me']);

        $useCase = $this->app->make(UpdateAssignmentNote::class);
        $useCase->handle($assignment, 'updated-without-user');

        $this->assertSame('updated-without-user', $assignment->fresh()->note);

        $activity = Activity::query()
            ->where('description', 'Assignment note updated by channel')
            ->where('subject_id', $assignment->getKey())
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertNull($activity->causer_id);

        $properties = $activity->properties->toArray();
        $this->assertSame($assignment->getKey(), $properties['assignment_id']);
        $this->assertSame($assignment->channel_id, $properties['channel_id']);
        $this->assertSame($assignment->video->original_name, $properties['video_name']);
        $this->assertArrayHasKey('note_updated_at', $properties);
    }
}
