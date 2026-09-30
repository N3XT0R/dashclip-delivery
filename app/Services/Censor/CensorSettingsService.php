<?php

declare(strict_types=1);

namespace App\Services\Censor;

use App\Constants\Config\CensorConfigEntry;
use App\Exceptions\Censor\VideoCensorException;
use App\Services\Contracts\ConfigServiceInterface;
use Illuminate\Support\Facades\Validator;

/** Reads the editable processing settings afresh for each video. */
final readonly class CensorSettingsService
{
    public function __construct(private ConfigServiceInterface $config)
    {
    }

    /**
     * Whether an administrator has the blurring switched on, asked afresh each time.
     */
    public function isEnabled(): bool
    {
        return (bool)$this->config->get(
            CensorConfigEntry::ENABLED,
            CensorConfigEntry::CATEGORY,
            false,
            withoutCache: true,
        );
    }

    /**
     * Return validated settings without retaining an earlier worker's cached values.
     * @return array{columns: int, rows: int, frame_step: int, confidence: float, margin: float,
     *     timeout_seconds: int, threads: int}
     * @throws VideoCensorException when stored settings are outside the supported ranges
     */
    public function current(): array
    {
        $values = [];
        foreach (['columns' => 3, 'rows' => 2, 'frame_step' => 3, 'confidence' => 0.15,
            'margin' => 0.25, 'timeout_seconds' => 3600, 'threads' => 2] as $name => $default) {
            $key = 'censor_' . $name;
            $value = $this->config->get($key, CensorConfigEntry::CATEGORY, $default, withoutCache: true);
            if (Validator::make(['value' => $value], ['value' => CensorConfigEntry::RULES[$key]])->fails()) {
                throw new VideoCensorException('The video processing settings are invalid: ' . $key);
            }
            $values[$name] = is_int($default) ? (int)$value : (float)$value;
        }

        return $values;
    }
}
