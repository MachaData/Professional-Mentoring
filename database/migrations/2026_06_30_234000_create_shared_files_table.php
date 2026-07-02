<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Files/links/notes shared within a dupla (mentor <-> mentee), optionally
        // tied to a specific session. Uploaded by either party.
        Schema::create('shared_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            // pdf | word | excel | powerpoint | image | link | text | other
            $table->string('type')->default('other');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('external_url')->nullable();
            $table->text('body_text')->nullable();
            $table->timestamps();

            $table->index(['assignment_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_files');
    }
};
