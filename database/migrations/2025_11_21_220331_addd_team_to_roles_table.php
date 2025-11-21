<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', static function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'team_id')) {
                $table->foreignId('team_id')
                    ->after('id')
                    ->nullable()
                    ->constrained('teams')
                    ->nullOnDelete();
            } else {
                $table->bigInteger('team_id')->nullable()->change();
                $table->foreign('team_id')
                    ->references('id')
                    ->on('teams')
                    ->nullOnDelete();
            }
        });

        Schema::table('model_has_roles', static function (Blueprint $table) {
            if (!Schema::hasColumn('model_has_roles', 'team_id')) {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('role_id')
                    ->constrained('teams')
                    ->nullOnDelete();
            } else {
                $table->bigInteger('team_id')->nullable()->change();
                $table->foreign('team_id')
                    ->references('id')
                    ->on('teams')
                    ->nullOnDelete();
            }
        });

        Schema::table('model_has_permissions', static function (Blueprint $table) {
            if (!Schema::hasColumn('model_has_permissions', 'team_id')) {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('permission_id')
                    ->constrained('teams')
                    ->nullOnDelete();
            } else {
                $table->bigInteger('team_id')->nullable()->change();
                $table->foreign('team_id')
                    ->references('id')
                    ->on('teams')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_has_permissions', static function (Blueprint $table) {
            if (Schema::hasColumn('model_has_permissions', 'team_id')) {
                if (Schema::hasIndex('model_has_permissions', 'model_has_permissions_team_id_foreign')) {
                    $table->dropForeign('model_has_permissions_team_id_foreign');
                }

                $table->dropColumn('team_id');
            }
        });

        Schema::table('model_has_roles', static function (Blueprint $table) {
            if (Schema::hasIndex('model_has_roles', 'model_has_roles_team_id_foreign')) {
                $table->dropForeign('model_has_roles_team_id_foreign');
            }
            $table->dropColumn('team_id');
        });

        Schema::table('roles', static function (Blueprint $table) {
            if (Schema::hasIndex('roles', 'roles_team_id_foreign')) {
                $table->dropForeign('roles_team_id_foreign');
            }
            $table->dropColumn('team_id');
        });
    }
};
