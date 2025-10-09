<?php

use App\Enum\MailStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('mail_logs');
        Schema::create('mail_logs', static function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->string('to');
            $table->string('subject')->nullable();
            $table->string('status')->default(MailStatus::Sent->value);
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_logs');
    }
};
