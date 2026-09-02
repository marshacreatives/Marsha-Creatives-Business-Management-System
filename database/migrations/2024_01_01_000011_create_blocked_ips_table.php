<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->unique();
            $table->string('reason');
            $table->string('blocked_by')->default('agent');
            $table->boolean('is_permanent')->default(false);
            $table->timestamp('blocked_at');
            $table->timestamp('unblocked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
    }
};
