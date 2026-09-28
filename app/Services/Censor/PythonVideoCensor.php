<?php

declare(strict_types=1);

namespace App\Services\Censor;

use App\Exceptions\Censor\VideoCensorException;
use App\ValueObjects\CensorResult;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Blurs number plates by handing the video to the detection script, which runs on the processor.
 */
readonly class PythonVideoCensor implements VideoCensorInterface
{
    /**
     * @param array<string, mixed> $config the censor configuration
     */
    public function __construct(private array $config)
    {
    }

    public function isAvailable(): bool
    {
        return (bool)($this->config['enabled'] ?? false)
            && is_readable((string)($this->config['script'] ?? ''))
            && is_readable((string)($this->config['model'] ?? ''));
    }

    public function censor(string $sourcePath, string $targetPath): CensorResult
    {
        if (!$this->isAvailable()) {
            throw new VideoCensorException('Blurring is not set up on this installation.');
        }

        $process = new Process($this->command($sourcePath, $targetPath));
        $process->setTimeout((float)($this->config['timeout_seconds'] ?? 3600));

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            throw new VideoCensorException('Blurring took too long and was stopped.', previous: $exception);
        }

        if (!$process->isSuccessful()) {
            Log::warning('Blurring a video failed', [
                'source' => $sourcePath,
                'exit_code' => $process->getExitCode(),
                'error' => $process->getErrorOutput(),
            ]);

            throw new VideoCensorException('The video could not be blurred.');
        }

        return $this->resultFrom($process->getOutput(), $targetPath);
    }

    /**
     * @return array<int, string>
     */
    private function command(string $sourcePath, string $targetPath): array
    {
        $tiles = $this->config['tiles'] ?? [];

        return [
            (string)$this->config['python'],
            (string)$this->config['script'],
            '--input', $sourcePath,
            '--output', $targetPath,
            '--model', (string)$this->config['model'],
            '--columns', (string)($tiles['columns'] ?? 3),
            '--rows', (string)($tiles['rows'] ?? 2),
            '--frame-step', (string)($this->config['frame_step'] ?? 3),
            '--confidence', (string)($this->config['confidence'] ?? 0.15),
            '--margin', (string)($this->config['margin'] ?? 0.25),
        ];
    }

    /**
     * The script reports what it did as one line of JSON.
     */
    private function resultFrom(string $output, string $targetPath): CensorResult
    {
        if (!is_file($targetPath) || filesize($targetPath) === 0) {
            throw new VideoCensorException('Blurring produced no usable file.');
        }

        try {
            /** @var array{frames?: int, regions?: int} $report */
            $report = json_decode(trim($output), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $report = [];
        }

        return new CensorResult(
            path: $targetPath,
            framesLookedAt: (int)($report['frames'] ?? 0),
            regionsBlurred: (int)($report['regions'] ?? 0),
        );
    }
}
