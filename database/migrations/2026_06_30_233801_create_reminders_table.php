<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('email_template_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            // Own copy (used when no template is linked). Translatable.
            $table->json('subject')->nullable();
            $table->json('message')->nullable();

            $table->string('recipient_type')->default('participant'); // facilitator|participant|both|admin

            // Scheduling relative to each session's window.
            $table->string('anchor')->default('session_start'); // session_start | session_end
            $table->string('timing')->default('before');        // before | after | on
            $table->unsignedInteger('days')->default(0);

            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
