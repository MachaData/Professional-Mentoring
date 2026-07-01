<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Editable email copy. Subject and body support {{variables}} and are
        // translatable. A program-scoped template overrides an org-wide one.
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('key');   // welcome | invitation | reminder | session_expired | ...
            $table->string('name');
            $table->json('subject');
            $table->json('body');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['organization_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
