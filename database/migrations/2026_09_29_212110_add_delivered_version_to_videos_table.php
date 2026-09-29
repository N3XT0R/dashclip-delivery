<?php

declare(strict_types=1);

use App\Enum\Video\DeliveredVersionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers which of the two files of a video is the one that gets handed out, and when the
 * submitter last changed that.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table): void {
            $table->string('delivered_version')
                ->default(DeliveredVersionEnum::ORIGINAL->value)
                ->after('source_path');
            $table->timestamp('version_switched_at')->nullable()->after('delivered_version');
        });

        // a video that kept an original aside is handing out the blurred copy
        DB::table('videos')
            ->whereNotNull('source_path')
            ->update(['delivered_version' => DeliveredVersionEnum::BLURRED->value]);
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table): void {
            $table->dropColumn(['delivered_version', 'version_switched_at']);
        });
    }
};
