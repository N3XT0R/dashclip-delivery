<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Clip;
use App\Models\User;
use App\Services\ClipService;
use Tests\DatabaseTestCase;

class ClipServiceTest extends DatabaseTestCase
{
    protected ClipService $clipService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clipService = $this->app->make(ClipService::class);
    }

    public function testAssignUserFromSubmittedBySetsUserId(): void
    {
        $user = User::factory()->create();
        $clip = Clip::factory()->create(['user_id' => null]);

        $result = $this->clipService->assignUserFromSubmittedBy($clip, $user);

        $this->assertTrue($result);
        $this->assertSame($user->id, $clip->fresh()->user_id);
    }

    public function testAssignUploaderIfPossibleAssignsUserWhenDisplayNameMatches(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'submitted_name' => 'John Doe',
        ]);

        $clip = Clip::factory()->create([
            'submitted_by' => 'John Doe',
            'user_id' => null,
        ]);

        $result = $this->clipService->assignUploaderIfPossible($clip);

        $this->assertTrue($result);
        $this->assertSame($user->id, $clip->fresh()->user_id);
    }

    public function testAssignUploaderIfPossibleWorksWithNameNeedingTrim(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'submitted_name' => 'Jane Doe',
        ]);

        $clip = Clip::factory()->create([
            'submitted_by' => '   Jane Doe   ',
            'user_id' => null,
        ]);

        $result = $this->clipService->assignUploaderIfPossible($clip);

        $this->assertTrue($result);
        $this->assertSame($user->id, $clip->fresh()->user_id);
    }

    public function testAssignUploaderIfPossibleReturnsFalseWhenSubmittedByEmpty(): void
    {
        $clip = Clip::factory()->create([
            'submitted_by' => '',
            'user_id' => null,
        ]);

        $result = $this->clipService->assignUploaderIfPossible($clip);

        $this->assertFalse($result);
        $this->assertNull($clip->fresh()->user_id);
    }

    public function testAssignUploaderIfPossibleReturnsFalseWhenUserNotFound(): void
    {
        $clip = Clip::factory()->create([
            'submitted_by' => 'Unknown User',
            'user_id' => null,
        ]);

        $result = $this->clipService->assignUploaderIfPossible($clip);

        $this->assertFalse($result);
        $this->assertNull($clip->fresh()->user_id);
    }
}
