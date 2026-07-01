<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A facilitator's record of running one session with one participant.
        Schema::create('session_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // FK constraint to assignments is added in the Assignments phase; keep
            // a nullable column now so the data model is ready.
            $table->unsignedBigInteger('assignment_id')->nullable()->index();

            $table->foreignId('session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facilitator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained('users')->nullOnDelete();

            // pending | draft | completed | rescheduled | cancelled | expired
            $table->string('status')->default('pending');
            $table->date('real_session_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['session_id', 'assignment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_records');
    }
};
