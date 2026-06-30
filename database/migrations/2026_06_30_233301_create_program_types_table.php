<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_types', function (Blueprint $table) {
            $table->id();
            // Global catalog (organization_id null) or per-organization custom type.
            $table->foreignId('organization_id')->nullable()
                ->constrained()->nullOnDelete();

            $table->json('name');            // translatable
            $table->json('description')->nullable();

            // Default visible role labels (overridable per program). Translatable.
            $table->json('facilitator_label'); // {"es":"Mentor","en":"Mentor"}
            $table->json('participant_label'); // {"es":"Mentee","en":"Mentee"}

            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_types');
    }
};
