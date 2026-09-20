<?php

declare(strict_types=1);

use Database\Seeders\ReleaseNewsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Publish the 4.10.0 release news with the deployment, since deployments only run migrations.
     */
    public function up(): void
    {
        app(ReleaseNewsSeeder::class)->run('4.10.0');
    }

    /**
     * Published articles are editorial content and stay in place.
     */
    public function down(): void
    {
    }
};
