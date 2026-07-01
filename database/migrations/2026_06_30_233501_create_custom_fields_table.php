<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A dynamic field definition. It attaches to exactly one scope:
        // a session, a stage, a whole program, or a reusable form template.
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('form_template_id')->nullable()->constrained()->nullOnDelete();

            $table->json('label');                 // translatable
            $table->string('name');                // machine key within its scope
            $table->string('field_type');          // see App\Enums\FieldType

            $table->json('placeholder')->nullable();  // translatable
            $table->json('help_text')->nullable();    // translatable
            $table->json('options_json')->nullable(); // choices for select/radio/checkbox…
            $table->text('default_value')->nullable();

            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible_to_participant')->default(false);
            $table->boolean('is_internal')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('active'); // active | inactive
            $table->timestamps();

            $table->index(['session_id', 'sort_order']);
            $table->index(['form_template_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
