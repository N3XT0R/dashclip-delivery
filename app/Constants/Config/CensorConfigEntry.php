<?php

declare(strict_types=1);

namespace App\Constants\Config;

final readonly class CensorConfigEntry
{
    public const string CATEGORY = 'censor';
    public const string ENABLED = 'censor_enabled';
    public const string COLUMNS = 'censor_columns';
    public const string ROWS = 'censor_rows';
    public const string FRAME_STEP = 'censor_frame_step';
    public const string CONFIDENCE = 'censor_confidence';
    public const string MARGIN = 'censor_margin';
    public const string TIMEOUT = 'censor_timeout_seconds';
    public const string THREADS = 'censor_threads';
    public const string SEARCH_FROM = 'censor_search_from';
    public const string MARGIN_GROWTH = 'censor_margin_growth';

    /** @var array<string, list<string>> Accepted operating ranges for editable settings. */
    public const array RULES = [
        self::ENABLED => ['required', 'boolean'],
        self::COLUMNS => ['required', 'integer', 'between:1,8'],
        self::ROWS => ['required', 'integer', 'between:1,8'],
        self::FRAME_STEP => ['required', 'integer', 'between:1,60'],
        self::CONFIDENCE => ['required', 'numeric', 'between:0.01,1'],
        self::MARGIN => ['required', 'numeric', 'between:0,1'],
        self::TIMEOUT => ['required', 'integer', 'between:1,3600'],
        self::THREADS => ['required', 'integer', 'between:1,16'],
        self::SEARCH_FROM => ['required', 'numeric', 'between:0,0.9'],
        self::MARGIN_GROWTH => ['required', 'numeric', 'between:0,1'],
    ];
}
