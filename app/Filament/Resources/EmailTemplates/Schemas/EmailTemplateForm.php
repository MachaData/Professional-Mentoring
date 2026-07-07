<?php

namespace App\Filament\Resources\EmailTemplates\Schemas;

use App\Services\TemplateRenderer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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

                Section::make('Imagen de cabecera')
                    ->schema([
                        FileUpload::make('header_image')->label('Imagen de cabecera / banner')
                            ->image()->imageEditor()
                            ->disk('public')->directory('email-templates')->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Se muestra centrada en la parte superior del correo. Para imágenes dentro del texto, usa el botón de imagen del editor.'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Section::make('Contenido (ES / EN)')
                            ->schema([
                                Text::make('Variables disponibles: '.$vars),
                                TextInput::make('subject.es')->label('Asunto (ES)')->required()
                                    ->live(onBlur: true),
                                self::bodyEditor('body.es', 'Cuerpo (ES)'),
                                TextInput::make('subject.en')->label('Subject (EN)')->required()
                                    ->live(onBlur: true),
                                self::bodyEditor('body.en', 'Body (EN)'),
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

    /** Rich text editor for an email body (bold, lists, headings, links, images). */
    protected static function bodyEditor(string $name, string $label): RichEditor
    {
        return RichEditor::make($name)->label($label)->required()
            ->live(onBlur: true)
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'link'],
                ['h2', 'h3'],
                ['alignStart', 'alignCenter', 'alignEnd'],
                ['blockquote', 'bulletList', 'orderedList'],
                ['attachFiles'],
                ['undo', 'redo'],
            ])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsVisibility('public')
            ->helperText('Da formato con la barra: negrita, listas, títulos, enlaces e imágenes. Inserta variables como {{user_name}} escribiéndolas en el texto.');
    }
}
