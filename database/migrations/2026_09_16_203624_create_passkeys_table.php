<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('passkeys', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('authenticatable_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('credential_id', 64)->unique();
            $table->json('data');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passkeys');
    }
};
