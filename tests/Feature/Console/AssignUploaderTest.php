<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Console\Commands\AssignUploader;
use App\Models\Clip;
use App\Models\User;
use Illuminate\Console\Command;
use Tests\DatabaseTestCase;

final class AssignUploaderTest extends DatabaseTestCase
{
    public function testAssignsUploaderForEachClip(): void
    {
        $alice = User::factory()->create(['submitted_name' => 'AliceUploader']);
        $bob = User::factory()->create(['submitted_name' => 'BobUploader']);
        $assignedUser = User::factory()->create();

        $clipOne = Clip::factory()->submittedBy('AliceUploader')->create(['user_id' => null]);
        $clipTwo = Clip::factory()->submittedBy('BobUploader')->create(['user_id' => null]);
        $alreadyAssigned = Clip::factory()->forUser($assignedUser)->create();

        $this->artisan((new AssignUploader())->getName())
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('clips', ['id' => $clipOne->getKey(), 'user_id' => $alice->getKey()]);
        $this->assertDatabaseHas('clips', ['id' => $clipTwo->getKey(), 'user_id' => $bob->getKey()]);
        $this->assertDatabaseHas('clips', ['id' => $alreadyAssigned->getKey(), 'user_id' => $alreadyAssigned->user_id]);
    }

    public function testClipsWithoutMatchingUserRemainUnassigned(): void
    {
        $clip = Clip::factory()->submittedBy('UnknownUploader')->create(['user_id' => null]);

        $this->artisan('assign:uploader')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('clips', ['id' => $clip->getKey(), 'user_id' => null]);
    }
}
