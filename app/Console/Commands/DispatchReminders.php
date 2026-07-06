<?php

namespace App\Console\Commands;

use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Models\Reminder;
use App\Models\Session;
use App\Models\User;
use App\Services\TemplateRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class DispatchReminders extends Command
{
    protected $signature = 'reminders:dispatch {--date= : Override "today" (Y-m-d) for testing}';

    protected $description = 'Send scheduled reminders whose due date matches today';

    public function handle(TemplateRenderer $renderer): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $sent = 0;

        $reminders = Reminder::query()
            ->where('status', 'active')
            ->with(['program.sessions.stage', 'session', 'template'])
            ->get();

        foreach ($reminders as $reminder) {
            $sessions = $reminder->session_id
                ? collect([$reminder->session])->filter()
                : $reminder->program->sessions;

            foreach ($sessions as $session) {
                $due = $reminder->dueDateFor($session);
                if (! $due || ! $due->isSameDay($today)) {
                    continue;
                }

                $sent += $this->dispatchForSession($reminder, $session, $renderer);
            }
        }

        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }

    protected function dispatchForSession(Reminder $reminder, Session $session, TemplateRenderer $renderer): int
    {
        $sent = 0;

        foreach ($reminder->program->assignments()->where('status', 'active')->with(['facilitator', 'participant'])->get() as $assignment) {
            foreach ($this->recipients($reminder, $assignment) as $user) {
                if (! $user || ! $user->email) {
                    continue;
                }

                // Dedupe: one send per reminder+user+session.
                $already = EmailLog::where('reminder_id', $reminder->id)
                    ->where('user_id', $user->id)
                    ->where('session_id', $session->id)
                    ->exists();
                if ($already) {
                    continue;
                }

                $vars = $renderer->variables([
                    'user' => $user,
                    'program' => $reminder->program,
                    'session' => $session,
                    'assignment' => $assignment,
                ]);

                [$subjectTpl, $bodyTpl] = $this->copy($reminder);
                $subject = $renderer->render($subjectTpl, $vars);
                $body = $renderer->render($bodyTpl, $vars);
                $headerUrl = $reminder->template?->headerImageUrl();

                try {
                    Mail::to($user->email)->send(new TemplatedMail($subject, $body, $headerUrl));
                    $status = 'sent';
                    $error = null;
                    $sent++;
                } catch (\Throwable $e) {
                    $status = 'failed';
                    $error = $e->getMessage();
                }

                EmailLog::create([
                    'organization_id' => $reminder->organization_id,
                    'user_id' => $user->id,
                    'reminder_id' => $reminder->id,
                    'session_id' => $session->id,
                    'type' => 'reminder',
                    'subject' => $subject,
                    'recipient_email' => $user->email,
                    'status' => $status,
                    'sent_at' => $status === 'sent' ? now() : null,
                    'error_message' => $error,
                ]);
            }
        }

        return $sent;
    }

    /** @return array{0:string,1:string} subject, body templates */
    protected function copy(Reminder $reminder): array
    {
        $locale = app()->getLocale();

        if ($reminder->template) {
            return [
                $reminder->template->getTranslation('subject', $locale),
                $reminder->template->getTranslation('body', $locale),
            ];
        }

        return [
            $reminder->getTranslation('subject', $locale) ?: 'Recordatorio',
            $reminder->getTranslation('message', $locale) ?: '',
        ];
    }

    /** @return iterable<User> */
    protected function recipients(Reminder $reminder, $assignment): iterable
    {
        return match ($reminder->recipient_type) {
            'facilitator' => [$assignment->facilitator],
            'participant' => [$assignment->participant],
            'both' => [$assignment->facilitator, $assignment->participant],
            'admin' => User::where('organization_id', $reminder->organization_id)
                ->where('role', User::ROLE_ORG_ADMIN)->get()->all(),
            default => [],
        };
    }
}
