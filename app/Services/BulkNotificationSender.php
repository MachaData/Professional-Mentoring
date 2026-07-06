<?php

namespace App\Services;

use App\Mail\TemplatedMail;
use App\Models\Assignment;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a one-off notification to a resolved list of recipients, using either an
 * editable email template or a free-form subject/body. Renders {{variables}} per
 * recipient in their locale and logs every send in email_logs (type "broadcast").
 */
class BulkNotificationSender
{
    public function __construct(protected TemplateRenderer $renderer) {}

    /**
     * @param  Collection<int,array{user:User,assignment:?Assignment}>  $targets
     * @param  array{template_id?:int|null,subject?:string|null,body?:string|null}  $content
     * @return array{sent:int,failed:int}
     */
    public function send(Collection $targets, array $content, ?int $orgId): array
    {
        $template = ! empty($content['template_id']) ? EmailTemplate::find($content['template_id']) : null;
        $headerUrl = $template?->headerImageUrl();

        $originalLocale = app()->getLocale();
        $sent = 0;
        $failed = 0;

        foreach ($targets as $target) {
            $user = $target['user'];
            $assignment = $target['assignment'] ?? null;
            $locale = in_array($user->locale, ['es', 'en'], true) ? $user->locale : $originalLocale;
            app()->setLocale($locale);

            $vars = $this->renderer->variables([
                'user' => $user,
                'program' => $assignment?->program,
                'assignment' => $assignment,
            ]);

            $subjectTpl = $template ? $template->getTranslation('subject', $locale) : ($content['subject'] ?? '');
            $bodyTpl = $template ? $template->getTranslation('body', $locale) : ($content['body'] ?? '');

            $subject = $this->renderer->render((string) $subjectTpl, $vars);
            $body = $this->renderer->render((string) $bodyTpl, $vars);

            try {
                Mail::to($user->email)->send(new TemplatedMail($subject, $body, $headerUrl));
                $status = 'sent';
                $error = null;
                $sent++;
            } catch (\Throwable $e) {
                $status = 'failed';
                $error = $e->getMessage();
                $failed++;
            }

            EmailLog::create([
                'organization_id' => $user->organization_id ?? $orgId,
                'user_id' => $user->id,
                'type' => 'broadcast',
                'subject' => $subject,
                'recipient_email' => $user->email,
                'status' => $status,
                'sent_at' => $status === 'sent' ? now() : null,
                'error_message' => $error,
            ]);
        }

        app()->setLocale($originalLocale);

        return ['sent' => $sent, 'failed' => $failed];
    }
}
