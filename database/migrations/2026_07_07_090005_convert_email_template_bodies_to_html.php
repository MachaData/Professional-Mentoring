<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // The email body is now edited with a rich text editor (HTML). Convert
        // existing Markdown bodies to HTML so they display and render correctly.
        foreach (DB::table('email_templates')->get() as $row) {
            $body = json_decode($row->body ?? '{}', true) ?: [];
            $changed = false;

            foreach ($body as $locale => $text) {
                if (is_string($text) && trim($text) !== '' && $text === strip_tags($text)) {
                    $body[$locale] = (string) Str::markdown($text);
                    $changed = true;
                }
            }

            if ($changed) {
                DB::table('email_templates')->where('id', $row->id)
                    ->update(['body' => json_encode($body)]);
            }
        }
    }

    public function down(): void
    {
        // No-op: the original Markdown source is not recoverable from the HTML.
    }
};
