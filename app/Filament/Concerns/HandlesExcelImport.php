<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Shared "Importar Excel" plumbing for list pages. Keeps the upload disk and the
 * read disk in sync (the previous bug: file stored on the default `local` disk but
 * read from `public`), reads the sheet straight from the storage disk so it works
 * on local and remote/S3 filesystems (e.g. Railway), and turns any failure into a
 * friendly notification instead of a 500.
 */
trait HandlesExcelImport
{
    /** Disk the uploaded spreadsheet is stored to and read from. */
    protected string $importDisk = 'local';

    protected function importFileUploadField(): FileUpload
    {
        return FileUpload::make('file')
            ->label('Archivo Excel')
            ->acceptedFileTypes([
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel', 'text/csv',
            ])
            ->disk($this->importDisk)
            ->directory('imports')
            ->visibility('private')
            ->storeFiles()
            ->required();
    }

    /**
     * Run an import, reading the uploaded file from the disk it was saved to.
     *
     * @param  array<string,mixed>  $data  the action form state (expects `file`)
     * @param  object  $import  a Maatwebsite import exposing a public int $imported
     */
    protected function runExcelImport(array $data, object $import, string $successLabel): void
    {
        $path = $data['file'] ?? null;

        if (! is_string($path) || $path === '' || ! Storage::disk($this->importDisk)->exists($path)) {
            Notification::make()
                ->title('No se pudo leer el archivo')
                ->body('El archivo subido no está disponible. Vuelve a subirlo e inténtalo de nuevo.')
                ->danger()
                ->send();

            return;
        }

        try {
            Excel::import($import, $path, $this->importDisk);
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Error al importar')
                ->body('Revisa que el archivo use la plantilla y las columnas correctas. Detalle: '.$e->getMessage())
                ->danger()
                ->send();

            return;
        } finally {
            Storage::disk($this->importDisk)->delete($path);
        }

        Notification::make()
            ->title($successLabel.': '.$import->imported)
            ->success()
            ->send();
    }
}
