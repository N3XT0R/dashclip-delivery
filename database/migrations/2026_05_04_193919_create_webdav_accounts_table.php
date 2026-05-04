<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webdav_accounts', function (Blueprint $table): void {
            $table->id();

            // Credentials
            $table->string('username')->unique();
            $table->text('password_encrypted');

            // State
            $table->boolean('enabled')->default(true);

            // Optional relation to application user
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Display name
            $table->string('display_name')->nullable();

            // Meta / extensibility
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webdav_accounts');
    }
};
