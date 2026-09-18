<?php

declare(strict_types=1);

use Database\Seeders\ReleaseNewsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Add the BSI recommendation to 4.9.0 release news that was already published by an earlier deployment.
     */
    public function up(): void
    {
        app(ReleaseNewsSeeder::class)->refreshContent('4.9.0');
    }

    /**
     * Published articles are editorial content and stay in place.
     */
    public function down(): void
    {
    }
};
