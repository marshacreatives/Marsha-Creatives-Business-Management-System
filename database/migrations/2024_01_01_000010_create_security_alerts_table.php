<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->enum('severity', ['critical', 'high', 'medium', 'low', 'info']);
            $table->string('source_ip')->nullable();
            $table->string('source_port')->nullable();
            $table->string('destination_port')->nullable();
            $table->string('description');
            $table->text('raw_log')->nullable();
            $table->string('action_taken')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index('severity');
            $table->index('type');
            $table->index('source_ip');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
    }
};
