<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Align sort_order with the session number so every list is shown in
        // program order (Sesión 1, 2, 3…). Fixes existing rows whose sort_order
        // stayed at the column default (0) after being created from the admin.
        DB::table('sessions')
            ->whereNotNull('number')
            ->update(['sort_order' => DB::raw('number')]);
    }

    public function down(): void
    {
        // No-op: the previous sort_order values are not recoverable and 0 was the
        // buggy state we are correcting.
    }
};
