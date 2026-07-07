<?php

namespace Tests\Feature;

use App\Enums\FieldType;
use App\Mail\TemplatedMail;
use App\Models\CustomField;
use App\Models\EmailTemplate;
use App\Models\FormTemplate;
use App\Models\Program;
use App\Models\Session;
use App\Services\FormTemplateApplier;
use App\Services\TemplateRenderer;
use App\Services\TestEmailSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FormTemplateAndEmailTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function makeTemplateWithFields(): FormTemplate
    {
        $program = Program::firstOrFail();

        $template = FormTemplate::create([
            'organization_id' => $program->organization_id,
            'name' => 'Plantilla de prueba',
            'status' => 'active',
        ]);

        CustomField::create([
            'organization_id' => $program->organization_id,
            'form_template_id' => $template->id,
            'label' => ['es' => 'Compromisos', 'en' => 'Commitments'],
            'name' => 'commitments',
            'field_type' => FieldType::Textarea->value,
            'is_required' => true,
            'is_visible_to_participant' => true,
            'sort_order' => 2,
            'status' => 'active',
        ]);

        CustomField::create([
            'organization_id' => $program->organization_id,
            'form_template_id' => $template->id,
            'label' => ['es' => 'Tema', 'en' => 'Topic'],
            'name' => 'topic',
            'field_type' => FieldType::Text->value,
            'is_required' => false,
            'is_visible_to_participant' => false,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        return $template;
    }

    public function test_applying_a_template_copies_fields_preserving_attributes(): void
    {
        $template = $this->makeTemplateWithFields();
        $session = Session::firstOrFail();
        $session->customFields()->delete();

        $count = app(FormTemplateApplier::class)->apply($template, $session);

        $this->assertSame(2, $count);

        $fields = $session->customFields()->get();
        $this->assertCount(2, $fields);

        // Order preserved (topic sort_order 1 before commitments sort_order 2).
        $this->assertSame('topic', $fields[0]->name);
        $this->assertSame('commitments', $fields[1]->name);

        $topic = $fields->firstWhere('name', 'topic');
        $commitments = $fields->firstWhere('name', 'commitments');

        // Type, required, visibility and label preserved.
        $this->assertSame(FieldType::Text, $topic->field_type);
        $this->assertFalse($topic->is_required);
        $this->assertFalse($topic->is_visible_to_participant);

        $this->assertSame(FieldType::Textarea, $commitments->field_type);
        $this->assertTrue($commitments->is_required);
        $this->assertTrue($commitments->is_visible_to_participant);
        $this->assertSame('Compromisos', $commitments->getTranslation('label', 'es'));

        // Copies are scoped to the session and detached from the template.
        $this->assertTrue($fields->every(fn ($f) => $f->session_id === $session->id && $f->form_template_id === null));
        $this->assertSame($session->organization_id, $topic->organization_id);
    }

    public function test_replace_clears_existing_session_fields_first(): void
    {
        $template = $this->makeTemplateWithFields();
        $session = Session::firstOrFail();
        $originalCount = $session->customFields()->count();
        $this->assertGreaterThan(0, $originalCount);

        // Append keeps existing.
        app(FormTemplateApplier::class)->apply($template, $session, replace: false);
        $this->assertSame($originalCount + 2, $session->customFields()->count());

        // Replace wipes and copies only the template's 2 fields.
        app(FormTemplateApplier::class)->apply($template, $session, replace: true);
        $this->assertSame(2, $session->customFields()->count());
    }

    public function test_send_test_email_renders_sample_data_and_dispatches(): void
    {
        Mail::fake();

        $state = [
            'subject' => ['es' => 'Hola {{user_name}}', 'en' => 'Hi {{user_name}}'],
            'body' => ['es' => 'Tu programa es {{program_name}}.', 'en' => 'Your program is {{program_name}}.'],
            'header_image' => null,
        ];

        app(TestEmailSender::class)->send('destino@ejemplo.com', 'es', $state);

        Mail::assertSent(TemplatedMail::class, function ($mail) {
            return $mail->hasTo('destino@ejemplo.com')
                && str_contains($mail->renderedSubject, 'Ana Torres')
                && str_starts_with($mail->renderedSubject, '[PRUEBA]')
                && str_contains($mail->renderedBody, 'Programa de Liderazgo 2026');
        });
    }

    public function test_to_html_handles_both_rich_html_and_legacy_markdown(): void
    {
        // Rich editor content (already HTML) is returned untouched.
        $html = '<p>Hola <strong>Ana</strong></p>';
        $this->assertSame($html, TemplateRenderer::toHtml($html));

        // Legacy Markdown is converted to HTML.
        $out = TemplateRenderer::toHtml('Hola **Ana**');
        $this->assertStringContainsString('<strong>Ana</strong>', $out);

        $this->assertSame('', TemplateRenderer::toHtml(''));
    }

    public function test_email_template_header_image_url_is_absolute_or_null(): void
    {
        $program = Program::firstOrFail();

        $withImage = EmailTemplate::create([
            'organization_id' => $program->organization_id,
            'key' => 'custom',
            'name' => 'Con imagen',
            'subject' => ['es' => 'x', 'en' => 'x'],
            'body' => ['es' => 'x', 'en' => 'x'],
            'status' => 'active',
            'header_image' => 'email-templates/banner.png',
        ]);

        $this->assertStringContainsString('email-templates/banner.png', (string) $withImage->headerImageUrl());

        $withImage->header_image = null;
        $this->assertNull($withImage->headerImageUrl());
    }
}
