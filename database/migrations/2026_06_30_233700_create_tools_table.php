<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reusable resource library. A "material" is just a tool with a type.
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->json('name');                 // translatable
            $table->json('description')->nullable();
            $table->string('type')->default('other');     // pdf, video, google_form, workbook, guide…
            $table->string('category')->nullable();

            $table->string('file_path')->nullable();       // uploaded file
            $table->string('external_url')->nullable();    // link (Drive, Forms, YouTube…)

            // admin | facilitator | participant | both | all
            $table->string('visibility')->default('all');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
