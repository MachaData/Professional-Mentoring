<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Domain "sessions" — the framework session table was renamed to
        // user_sessions to free this name.
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained()->nullOnDelete();

            $table->json('name');                 // translatable
            $table->unsignedInteger('number')->nullable();
            $table->json('description')->nullable();
            $table->json('objective')->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->boolean('requires_registration')->default(true);
            $table->boolean('visible_to_participant')->default(true);
            $table->string('status')->default('active'); // active | inactive | finished
            $table->timestamps();

            $table->index(['program_id', 'sort_order']);
            $table->index(['stage_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
