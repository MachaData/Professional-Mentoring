<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Links a facilitator with a participant within a program (a "dupla").
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facilitator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('users')->cascadeOnDelete();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // active|paused|finished|cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            // A participant has a single facilitator within a program.
            $table->unique(['program_id', 'participant_id']);
            $table->index(['program_id', 'facilitator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
