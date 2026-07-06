<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-program, per-role welcome popup shown once in the portal.
        Schema::create('welcome_popups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // facilitator | participant
            $table->boolean('enabled')->default(true);
            $table->json('title')->nullable();   // translatable
            $table->json('body')->nullable();    // translatable
            $table->string('video_url')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('welcome_popups');
    }
};
