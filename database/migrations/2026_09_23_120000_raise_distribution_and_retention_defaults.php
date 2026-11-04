<?php

declare(strict_types=1);

use App\Constants\Config\DefaultConfigEntry;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Raises the shipped defaults so a video is offered a second time and stays recoverable for a month.
 * Installations that already picked their own value keep it.
 */
return new class () extends Migration {
    /**
     * @var array<string, array{old: string, new: string}>
     */
    private const array RAISED_DEFAULTS = [
        DefaultConfigEntry::DISTRIBUTION_ROUNDS => ['old' => '1', 'new' => '2'],
        DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS => ['old' => '1', 'new' => '4'],
    ];

    public function up(): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');

        foreach (self::RAISED_DEFAULTS as $key => $values) {
            DB::table('configs')
                ->where('key', $key)
                ->where('value', $values['old'])
                ->update(['value' => $values['new'], 'updated_at' => $timestamp]);
        }
    }

    public function down(): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');

        foreach (self::RAISED_DEFAULTS as $key => $values) {
            DB::table('configs')
                ->where('key', $key)
                ->where('value', $values['new'])
                ->update(['value' => $values['old'], 'updated_at' => $timestamp]);
        }
    }
};
