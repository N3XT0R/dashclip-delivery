<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('passport_scope_grants', static function (Blueprint $table) {
            $table->dropIndex('passport_scope_grant_unique');
            $table->unique(
                [
                    'tokenable_type',
                    'tokenable_id',
                    'resource_id',
                    'action_id',
                    'context_client_id',
                ],
                'passport_scope_grant_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('passport_scope_grants', static function (Blueprint $table) {
            $table->dropIndex('passport_scope_grant_unique');
            $table->unique(
                [
                    'tokenable_type',
                    'tokenable_id',
                    'resource_id',
                    'action_id',
                ],
                'passport_scope_grant_unique'
            );
        });
    }
};
