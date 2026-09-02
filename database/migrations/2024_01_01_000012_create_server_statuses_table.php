<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('server_name');
            $table->decimal('cpu_usage', 5, 2);
            $table->decimal('ram_usage', 5, 2);
            $table->decimal('ram_total_gb', 8, 2);
            $table->decimal('ram_used_gb', 8, 2);
            $table->decimal('disk_usage', 5, 2);
            $table->decimal('disk_total_gb', 8, 2);
            $table->decimal('disk_used_gb', 8, 2);
            $table->decimal('load_average_1', 6, 2);
            $table->decimal('load_average_5', 6, 2);
            $table->decimal('load_average_15', 6, 2);
            $table->integer('active_connections')->default(0);
            $table->integer('total_processes')->default(0);
            $table->integer('uptime_seconds')->default(0);
            $table->json('services_status')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index('checked_at');
            $table->index('server_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_statuses');
    }
};
