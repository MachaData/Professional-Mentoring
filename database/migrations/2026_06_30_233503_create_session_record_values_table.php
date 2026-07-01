<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One answer to one custom field within a session record. The value is
        // stored in the typed column matching the field's type.
        Schema::create('session_record_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();

            $table->text('value_text')->nullable();
            $table->decimal('value_number', 15, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->string('value_file')->nullable();
            $table->json('value_json')->nullable();  // select/multiselect/checkbox/complex

            $table->timestamps();

            $table->unique(['session_record_id', 'custom_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_record_values');
    }
};
