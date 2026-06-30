<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_type_id')->nullable()->constrained()->nullOnDelete();

            $table->json('name');                 // translatable
            $table->string('slug');
            $table->json('description')->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Visible role labels — inherited from program_type, overridable here.
            $table->json('facilitator_label')->nullable();
            $table->json('participant_label')->nullable();

            // Branding (overrides org/client where set)
            $table->string('logo')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();

            $table->string('default_locale', 5)->default('es');
            // draft | active | paused | finished | archived
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
