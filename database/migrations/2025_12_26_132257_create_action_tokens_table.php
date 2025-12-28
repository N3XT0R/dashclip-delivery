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
        Schema::create('action_tokens', static function (Blueprint $table) {
            $table->id();

            $table->nullableMorphs('subject');

            $table->string('purpose', 50);
            $table->string('token_hash', 64);

            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->foreignId('issued_for_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique('token_hash');
            $table->index('purpose');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_tokens');
    }
};
