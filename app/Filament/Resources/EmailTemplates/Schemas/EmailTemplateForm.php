<?php

namespace App\Filament\Resources\EmailTemplates\Schemas;

use App\Services\TemplateRenderer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class EmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentUser = auth()->user();
        $vars = collect(TemplateRenderer::availableVariables())
            ->map(fn ($v) => '{{'.$v.'}}')->implode(', ');

        return $schema
            ->components([
                Section::make('Plantilla de correo')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nombre')->required(),
                        Select::make('key')->label('Tipo')
                            ->options([
                                'invitation' => 'Invitación de acceso',
                                'welcome' => 'Bienvenida',
                                'reminder_session' => 'Recordatorio de sesión',
                                'reminder_register' => 'Recordatorio de registro',
                                'session_expired' => 'Sesión vencida',
                                'program_closing' => 'Cierre de programa',
                                'custom' => 'Personalizado',
                            ])->required(),
                        Select::make('organization_id')->label('Organización')
                            ->relationship('organization', 'name')->searchable()->preload()
                            ->default(fn () => $currentUser?->organization_id)
                            ->disabled(fn () => ! ($currentUser?->isSuperadmin() ?? false))
                            ->dehydrated()->required(),
                        Select::make('program_id')->label('Programa (opcional)')
                            ->relationship('program', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record?->getTranslation('name', app()->getLocale()))
                            ->searchable()->preload()
                            ->helperText('Vacío = aplica a toda la organización.'),
                        Select::make('status')->label('Estado')
                            ->options(['active' => 'Activa', 'inactive' => 'Inactiva'])->default('active')->required(),
                    ]),

                Section::make('Imágenes')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('header_image')->label('Imagen de cabecera / banner')
                            ->image()->imageEditor()
                            ->disk('public')->directory('email-templates')->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Se muestra centrada en la parte superior del correo.'),
                        FileUpload::make('images')->label('Imágenes para insertar en el cuerpo')
                            ->image()->multiple()->reorderable()->appendFiles()
                            ->disk('public')->directory('email-templates')->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Súbelas aquí y, tras guardar, copia el Markdown ![imagen](URL) que aparece en la vista previa dentro del cuerpo del mensaje.'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Section::make('Contenido (ES / EN)')
                            ->schema([
                                Text::make('Variables disponibles: '.$vars),
                                TextInput::make('subject.es')->label('Asunto (ES)')->required()
                                    ->live(onBlur: true),
                                Textarea::make('body.es')->label('Cuerpo (ES)')->rows(8)->required()
                                    ->live(onBlur: true)
                                    ->helperText('Admite Markdown, incluidas imágenes: ![texto](URL).'),
                                TextInput::make('subject.en')->label('Subject (EN)')->required()
                                    ->live(onBlur: true),
                                Textarea::make('body.en')->label('Body (EN)')->rows(8)->required()
                                    ->live(onBlur: true),
                            ]),

                        Section::make('Vista previa')
                            ->schema([
                                Select::make('preview_locale')->label('Idioma de la vista previa')
                                    ->options(['es' => 'Español', 'en' => 'English'])
                                    ->default('es')->live()
                                    ->dehydrated(false),
                                View::make('filament.emails.preview'),
                            ]),
                    ]),
            ]);
    }
}
