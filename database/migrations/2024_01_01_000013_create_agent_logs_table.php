<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_logs', function (Blueprint $table) {
            $table->id();
            $table->string('check_type');
            $table->string('status');
            $table->integer('alerts_found')->default(0);
            $table->integer('ips_blocked')->default(0);
            $table->text('details')->nullable();
            $table->decimal('execution_time_ms', 10, 2)->nullable();
            $table->timestamp('started_at');
            $table->timestamps();

            $table->index('check_type');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_logs');
    }
};
