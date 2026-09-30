<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Censor;

use App\Constants\Config\CensorConfigEntry;
use App\Exceptions\Censor\VideoCensorException;
use App\Services\Censor\VideoCensorInterface;
use App\Services\Contracts\ConfigServiceInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\DatabaseTestCase;

final class PythonVideoCensorTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // the switch in the settings is off by default, these tests are about the processing itself
        $this->app->make(ConfigServiceInterface::class)
            ->set(CensorConfigEntry::ENABLED, true, 'censor', 'bool');
    }

    public function testNothingRunsWhileTheBlurringIsSwitchedOff(): void
    {
        $root = storage_path('framework/testing/censor-off-' . Str::uuid());
        $disk = Storage::build(['driver' => 'local', 'root' => $root]);
        $disk->put('model', 'test model');
        $disk->put('process.php', '<?php');
        config()->set('censor', [
            'enabled' => true, 'python' => PHP_BINARY,
            'script' => $root . '/process.php', 'model' => $root . '/model',
        ]);
        $this->app->make(ConfigServiceInterface::class)
            ->set(CensorConfigEntry::ENABLED, false, 'censor', 'bool');
        $this->app->forgetInstance(VideoCensorInterface::class);

        try {
            self::assertFalse($this->app->make(VideoCensorInterface::class)->isAvailable());
        } finally {
            $disk->deleteDirectory('');
        }
    }

    public function testDatabaseTimeoutStopsTheProcessingCommand(): void
    {
        $root = storage_path('framework/testing/censor-timeout-' . Str::uuid());
        $disk = Storage::build(['driver' => 'local', 'root' => $root]);
        $disk->put('model', 'test model');
        $disk->put('process.php', '<?php sleep(3);');
        config()->set('censor', [
            'enabled' => true, 'python' => PHP_BINARY,
            'script' => $root . '/process.php', 'model' => $root . '/model',
        ]);
        $this->app->forgetInstance(VideoCensorInterface::class);
        $this->app->make(ConfigServiceInterface::class)->set(CensorConfigEntry::TIMEOUT, 1, 'censor', 'int');

        try {
            $this->expectException(VideoCensorException::class);
            $this->expectExceptionMessage('Blurring took too long and was stopped.');
            $this->app->make(VideoCensorInterface::class)->censor($root . '/input', $root . '/output');
        } finally {
            $disk->deleteDirectory('');
        }
    }

    public function testResolvedProcessorUsesFreshDatabaseSettingsForEachInvocation(): void
    {
        $root = storage_path('framework/testing/censor-process-' . Str::uuid());
        $disk = Storage::build(['driver' => 'local', 'root' => $root]);
        $disk->put('model', 'test model');
        $disk->put('input', 'test video');
        $disk->put('process.php', '<?php $args = getopt("", ["input:", "output:", "model:", "columns:", "rows:", "frame-step:", "confidence:", "margin:", "threads:"]); file_put_contents($args["output"], json_encode($args)); echo json_encode(["frames" => 1, "regions" => 2]);');
        config()->set('censor', [
            'enabled' => true, 'python' => PHP_BINARY,
            'script' => $root . '/process.php', 'model' => $root . '/model',
        ]);
        $this->app->forgetInstance(VideoCensorInterface::class);
        $processor = $this->app->make(VideoCensorInterface::class);
        $config = $this->app->make(ConfigServiceInterface::class);

        try {
            $config->set(CensorConfigEntry::FRAME_STEP, 5, 'censor', 'int');
            $config->set(CensorConfigEntry::COLUMNS, 4, 'censor', 'int');
            $config->set(CensorConfigEntry::ROWS, 3, 'censor', 'int');
            $config->set(CensorConfigEntry::CONFIDENCE, 0.4, 'censor', 'float');
            $config->set(CensorConfigEntry::MARGIN, 0.5, 'censor', 'float');
            $config->set(CensorConfigEntry::THREADS, 3, 'censor', 'int');
            $result = $processor->censor($root . '/input', $root . '/output');
            $arguments = json_decode($disk->get('output'), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame('5', $arguments['frame-step']);
            self::assertSame('4', $arguments['columns']);
            self::assertSame('3', $arguments['rows']);
            self::assertSame('0.4', $arguments['confidence']);
            self::assertSame('0.5', $arguments['margin']);
            self::assertSame('3', $arguments['threads']);
            self::assertSame(2, $result->regionsBlurred);

            $config->set(CensorConfigEntry::FRAME_STEP, 8, 'censor', 'int');
            $processor->censor($root . '/input', $root . '/output');
            self::assertSame('8', json_decode($disk->get('output'), true)['frame-step']);
        } finally {
            $disk->deleteDirectory('');
        }
    }
}
