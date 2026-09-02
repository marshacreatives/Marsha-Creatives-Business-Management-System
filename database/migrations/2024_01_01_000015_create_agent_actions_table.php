<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_actions', function (Blueprint $table) {
            $table->id();
            $table->string('server_name')->nullable();
            $table->string('action');                 // e.g. block_ip, unblock_ip, kill_process, quarantine_file
            $table->string('resource')->nullable();   // e.g. the IP / PID / file path / process name
            $table->string('severity')->default('info');
            $table->string('status')->default('completed');
            $table->text('description')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->index('server_name');
            $table->index('action');
            $table->index('performed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_actions');
    }
};
