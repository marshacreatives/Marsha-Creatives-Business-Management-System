<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->decimal('expense', 12, 2)->default(0);
            $table->decimal('cost', 12, 2)->default(0);
            $table->enum('status', ['in_progress', 'completed', 'cancelled'])->default('in_progress');
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_jobs');
    }
};
