<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Follow-up history per dupla: contacts and actions the team logs with a
        // mentor or mentee (called, wrote, no answer, support, follow-up…). Kept
        // so coordinators, admins and the read-only client can review what was
        // done and nothing gets lost.
        Schema::create('assignment_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            // Person contacted (facilitator or participant of the dupla). Null = general/ambos.
            $table->foreignId('contacted_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Who registered the action (kept even if that user is later removed).
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('contact_type');            // call|message|no_response|support|followup|meeting|other
            $table->string('status')->default('done'); // done|pending|in_progress|no_response
            $table->text('comment')->nullable();
            $table->date('contacted_at')->nullable();  // when the contact happened
            $table->timestamps();

            $table->index(['assignment_id', 'contacted_at']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_followups');
    }
};
