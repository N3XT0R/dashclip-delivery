<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class QueueEventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Log successful jobs
        Queue::after(static function (JobProcessed $event) {
            $job = $event->job;
            $payload = $job->payload();

            activity()
                ->withProperties([
                    'job_id' => $job->getJobId(),
                    'name' => $job->getName(),
                    'job' => $payload['displayName'] ?? get_class($job),
                    'queue' => $job->getQueue(),
                    'status' => 'completed',
                ])
                ->log('Job completed');
        });

        // Log failed jobs
        Queue::failing(static function (JobFailed $event) {
            $job = $event->job;
            $payload = $job->payload();

            activity()
                ->withProperties([
                    'job_id' => $job->getJobId(),
                    'name' => $job->getName(),
                    'job' => $payload['displayName'] ?? get_class($job),
                    'queue' => $job->getQueue(),
                    'status' => 'failed',
                    'exception' => $event->exception?->getMessage(),
                ])
                ->log('Job failed');
        });
    }
}
