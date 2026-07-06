<?php

namespace App\Filament\Pages;

use App\Models\Assignment;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Program;
use App\Models\User;
use App\Services\BulkNotificationSender;
use App\Services\NotificationAudienceResolver;
use App\Services\TemplateRenderer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class SendNotification extends Page
{
    protected string $view = 'filament.pages.send-notification';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Enviar notificación';

    protected static ?string $title = 'Enviar notificación';

    protected static string|\UnitEnum|null $navigationGroup = 'Comunicaciones';

    protected static ?int $navigationSort = 3;

    /** Coordinators, org-admins and superadmins can send notifications. */
    public static function canAccess(): bool
    {
        return auth()->user()?->canSuperviseDuplas() ?? false;
    }

    protected function currentOrgId(): ?int
    {
        $user = auth()->user();

        return $user?->isSuperadmin() ? null : $user?->organization_id;
    }

    protected function getHeaderActions(): array
    {
        return [$this->composeAction()];
    }

    public function composeAction(): Action
    {
        $vars = collect(TemplateRenderer::availableVariables())
            ->map(fn ($v) => '{{'.$v.'}}')->implode(', ');

        return Action::make('compose')
            ->label('Redactar notificación')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->modalWidth('3xl')
            ->modalSubmitActionLabel('Enviar')
            ->schema([
                Section::make('Destinatarios')
                    ->schema([
                        Select::make('audience')->label('Enviar a')->required()->live()
                            ->options([
                                'all' => 'Todos (mentores, mentees y coordinadores)',
                                'facilitators' => 'Solo mentores',
                                'participants' => 'Solo mentees',
                                'coordinators' => 'Solo coordinadores',
                                'user' => 'Un usuario específico',
                                'duplas' => 'Duplas seleccionadas',
                                'filter' => 'Duplas por estado (atrasadas, sin inicio…)',
                            ]),

                        Select::make('program_id')->label('Programa (opcional)')
                            ->options(fn () => $this->programOptions())
                            ->searchable()->placeholder('Todos los programas')
                            ->visible(fn (Get $get) => in_array($get('audience'), ['all', 'facilitators', 'participants', 'filter'], true)),

                        Select::make('user_id')->label('Usuario')
                            ->searchable()->required()
                            ->getSearchResultsUsing(fn (string $search) => $this->userSearch($search))
                            ->getOptionLabelUsing(fn ($value) => optional(User::find($value))->name)
                            ->visible(fn (Get $get) => $get('audience') === 'user'),

                        Select::make('assignment_ids')->label('Duplas')
                            ->multiple()->searchable()->required()
                            ->options(fn () => $this->assignmentOptions())
                            ->visible(fn (Get $get) => $get('audience') === 'duplas'),

                        CheckboxList::make('filters')->label('Estado de las duplas')->required()
                            ->options([
                                'atrasadas' => 'Atrasadas (sesión vencida o por debajo del calendario)',
                                'sin_inicio' => 'Sin inicio (ninguna sesión completada)',
                                'sesion_pendiente' => 'Con sesión pendiente (ventana abierta)',
                                'encuesta_pendiente' => 'Con encuesta pendiente',
                            ])
                            ->helperText('«Encuesta pendiente» es aproximado: sesión con encuesta cuyo registro aún no se completa (las encuestas externas no reportan quién respondió).')
                            ->visible(fn (Get $get) => $get('audience') === 'filter'),

                        Select::make('dupla_recipients')->label('Dentro de cada dupla, enviar a')
                            ->options([
                                'both' => 'Mentor y mentee',
                                'mentor' => 'Solo mentor',
                                'mentee' => 'Solo mentee',
                            ])->default('both')->required()
                            ->visible(fn (Get $get) => in_array($get('audience'), ['duplas', 'filter'], true)),
                    ]),

                Section::make('Contenido')
                    ->schema([
                        Select::make('template_id')->label('Usar plantilla de correo (opcional)')
                            ->options(fn () => $this->templateOptions())
                            ->searchable()->live()->placeholder('Escribir mensaje libre')
                            ->helperText('Si eliges una plantilla, se usa su asunto y cuerpo.'),

                        Text::make('Variables disponibles: '.$vars),

                        TextInput::make('subject')->label('Asunto')
                            ->required(fn (Get $get) => blank($get('template_id')))
                            ->visible(fn (Get $get) => blank($get('template_id'))),

                        Textarea::make('body')->label('Mensaje')->rows(8)
                            ->required(fn (Get $get) => blank($get('template_id')))
                            ->visible(fn (Get $get) => blank($get('template_id')))
                            ->helperText('Admite Markdown, incluidas imágenes: ![texto](URL).'),
                    ]),
            ])
            ->action(fn (array $data) => $this->dispatchNotification($data));
    }

    protected function dispatchNotification(array $data): void
    {
        $orgId = $this->currentOrgId();
        $targets = app(NotificationAudienceResolver::class)->resolve($data, $orgId);

        if ($targets->isEmpty()) {
            Notification::make()
                ->warning()
                ->title('Sin destinatarios')
                ->body('Ningún usuario coincide con la audiencia seleccionada (o no tienen correo).')
                ->send();

            return;
        }

        $result = app(BulkNotificationSender::class)->send($targets, [
            'template_id' => $data['template_id'] ?? null,
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
        ], $orgId);

        Notification::make()
            ->success()
            ->title('Notificación enviada')
            ->body("Enviados: {$result['sent']}".($result['failed'] ? " · Fallidos: {$result['failed']}" : ''))
            ->send();
    }

    /** @return array<int,string> */
    protected function programOptions(): array
    {
        $locale = app()->getLocale();

        return Program::query()->get()
            ->mapWithKeys(fn (Program $p) => [$p->id => $p->getTranslation('name', $locale)])
            ->all();
    }

    /** @return array<int,string> */
    protected function userSearch(string $search): array
    {
        $orgId = $this->currentOrgId();

        return User::query()
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ->orderBy('name')->limit(50)
            ->get()->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} · {$u->email}"])
            ->all();
    }

    /** @return array<int,string> */
    protected function assignmentOptions(): array
    {
        $locale = app()->getLocale();

        return Assignment::query()
            ->with(['facilitator:id,name', 'participant:id,name', 'program:id,name'])
            ->get()
            ->mapWithKeys(fn (Assignment $a) => [
                $a->id => trim(($a->facilitator?->name ?? '—').' ↔ '.($a->participant?->name ?? '—')
                    .' · '.($a->program?->getTranslation('name', $locale) ?? '')),
            ])
            ->all();
    }

    /** @return array<int,string> */
    protected function templateOptions(): array
    {
        return EmailTemplate::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** Recent broadcast sends for the info panel. */
    public function recentBroadcasts(): Collection
    {
        $orgId = $this->currentOrgId();

        return EmailLog::query()
            ->where('type', 'broadcast')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->latest('id')->limit(15)->get();
    }
}
