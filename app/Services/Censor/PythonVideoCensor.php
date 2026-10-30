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
    public function __construct(private array $config, private CensorSettingsService $settings)
    {
    }

    /**
     * Whether blurring can happen here: the machine carries the tooling, and it is switched on.
     *
     * The tooling comes from the installation, the switch from the settings, so an administrator
     * can stop it without a deployment when the machine is needed for something else.
     */
    public function isAvailable(): bool
    {
        return $this->isInstalled() && $this->settings->isEnabled();
    }

    /** Whether the machine carries the script, the model and the runtime at all. */
    public function isInstalled(): bool
    {
        return (bool)($this->config['enabled'] ?? false)
            && is_readable((string)($this->config['script'] ?? ''))
            && is_readable((string)($this->config['model'] ?? ''));
    }

    /**
     * Process a video using a fresh snapshot of the database settings.
     * @throws VideoCensorException when settings, processing or output are invalid
     */
    public function censor(string $sourcePath, string $targetPath): CensorResult
    {
        if (!$this->isAvailable()) {
            throw new VideoCensorException('Blurring is not set up on this installation.');
        }

        $settings = $this->settings->current();
        $process = new Process($this->command($sourcePath, $targetPath, $settings));
        $process->setTimeout($settings['timeout_seconds']);

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
     * @param array{columns: int, rows: int, frame_step: int, confidence: float, margin: float,
     *     timeout_seconds: int, threads: int, search_from: float, margin_growth: float} $settings
     * @return array<int, string>
     */
    private function command(string $sourcePath, string $targetPath, array $settings): array
    {
        return [
            (string)$this->config['python'],
            (string)$this->config['script'],
            '--input', $sourcePath,
            '--output', $targetPath,
            '--model', (string)$this->config['model'],
            '--columns', (string)$settings['columns'],
            '--rows', (string)$settings['rows'],
            '--frame-step', (string)$settings['frame_step'],
            '--confidence', (string)$settings['confidence'],
            '--margin', (string)$settings['margin'],
            '--threads', (string)$settings['threads'],
            '--search-from', (string)$settings['search_from'],
            '--margin-growth', (string)$settings['margin_growth'],
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
