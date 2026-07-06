<?php

namespace Tests\Feature;

use App\Filament\Concerns\HandlesExcelImport;
use App\Imports\UsersImport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /** Anonymous host exposing the trait's protected helper for testing. */
    private function importer(): object
    {
        return new class
        {
            use HandlesExcelImport;

            public function import(array $data, object $import, string $label): void
            {
                $this->runExcelImport($data, $import, $label);
            }
        };
    }

    public function test_import_reads_file_from_the_disk_it_was_stored_on_and_cleans_up(): void
    {
        Storage::fake('local');
        $orgId = Organization::query()->value('id');

        $csv = "nombre,correo,rol\nMaria Test,maria.test@ejemplo.com,Mentor\n";
        Storage::disk('local')->put('imports/nuevos.csv', $csv);

        $import = new UsersImport($orgId);
        $this->importer()->import(['file' => 'imports/nuevos.csv'], $import, 'Usuarios importados');

        $this->assertSame(1, $import->imported);
        $user = User::where('email', 'maria.test@ejemplo.com')->first();
        $this->assertNotNull($user);
        $this->assertSame($orgId, $user->organization_id);

        // Temp upload is removed after a successful import.
        Storage::disk('local')->assertMissing('imports/nuevos.csv');
    }

    public function test_missing_file_shows_friendly_error_and_does_not_throw(): void
    {
        Storage::fake('local');
        $orgId = Organization::query()->value('id');
        $before = User::count();

        $import = new UsersImport($orgId);

        // Must NOT raise FileNotFoundException / 500 when the file is absent.
        $this->importer()->import(['file' => 'imports/does-not-exist.xlsx'], $import, 'Usuarios importados');

        $this->assertSame(0, $import->imported);
        $this->assertSame($before, User::count());
    }
}
