<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Associates a tool with a program, a stage or a session (any one target).
        Schema::create('tool_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['program_id']);
            $table->index(['session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_relations');
    }
};
