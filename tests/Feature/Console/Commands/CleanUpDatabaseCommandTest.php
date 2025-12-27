<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Application\Cleanup\CleanupDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class CleanUpDatabaseCommandTest extends TestCase
{
    public function testRunsCleanupServiceSuccessfully(): void
    {
        $this->mock(CleanupDatabase::class)
            ->expects('handle')
            ->once()
            ->andReturnNull();

        Log::spy();

        $this->artisan('clean:database')
            ->assertExitCode(Command::SUCCESS);

        Log::shouldNotHaveReceived('error');
    }

    public function testLogsErrorAndReturnsFailureOnException(): void
    {
        $exception = new \RuntimeException('test exception');

        $this->mock(CleanupDatabase::class)
            ->expects('handle')
            ->once()
            ->andThrow($exception);

        Log::spy();

        $this->artisan('clean:database')
            ->assertExitCode(Command::FAILURE);

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Error during database cleanup: ' . $exception->getMessage(),
                [
                    'exception' => $exception,
                ]
            );
    }

    public function testLogsErrorAndReturnsFailureOnThrowable(): void
    {
        $throwable = new \Error('unexpected error');

        $this->mock(CleanupDatabase::class)
            ->expects('handle')
            ->once()
            ->andThrow($throwable);

        Log::spy();

        $this->artisan('clean:database')
            ->assertExitCode(Command::FAILURE);

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Error during database cleanup: ' . $throwable->getMessage(),
                [
                    'exception' => $throwable,
                ]
            );
    }
}
