<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Stored as a varchar rather than text because MySQL cannot build a
            // unique index on a TEXT column without a prefix length. Real push
            // endpoints (FCM, Mozilla autopush, WNS) sit well under 500 chars.
            $table->string('endpoint', 500);

            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding')->default('aes128gcm');
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // A push endpoint identifies one browser profile, so it must be
            // unique across the whole table rather than per user.
            $table->unique('endpoint');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
