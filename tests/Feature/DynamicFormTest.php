<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Session;
use App\Models\SessionRecord;
use App\Services\DynamicFormBuilder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicFormTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_builder_produces_a_component_per_active_field(): void
    {
        $session = Program::firstOrFail()->sessions()->firstOrFail();
        $fields = $session->customFields;

        $components = (new DynamicFormBuilder)->components($fields);

        $this->assertCount($fields->count(), $components);
    }

    public function test_builder_maps_field_types_to_matching_components(): void
    {
        $session = Program::firstOrFail()->sessions()->firstOrFail();
        $builder = new DynamicFormBuilder;

        $byName = $session->customFields->keyBy('name');
        $components = collect($builder->components($session->customFields))
            ->keyBy(fn ($c) => $c->getName());

        // text field -> TextInput, textarea field -> Textarea
        $this->assertInstanceOf(TextInput::class, $components["field_{$byName['topic']->id}"]);
        $this->assertInstanceOf(Textarea::class, $components["field_{$byName['summary']->id}"]);
    }

    public function test_values_round_trip_through_typed_columns(): void
    {
        $session = Program::firstOrFail()->sessions()->firstOrFail();
        $fields = $session->customFields;
        $builder = new DynamicFormBuilder;

        $record = SessionRecord::create([
            'organization_id' => $session->organization_id,
            'session_id' => $session->id,
            'status' => SessionRecord::STATUS_DRAFT,
        ]);

        $summary = $fields->firstWhere('name', 'summary');
        $agreements = $fields->firstWhere('name', 'agreements');

        $state = [
            "field_{$summary->id}" => 'Buena sesión inicial',
            "field_{$agreements->id}" => 'Acuerdo firme',
        ];

        $builder->saveValues($record, $fields, $state);

        // Stored in the right typed columns
        $this->assertDatabaseHas('session_record_values', [
            'session_record_id' => $record->id,
            'custom_field_id' => $summary->id,
            'value_text' => 'Buena sesión inicial',
        ]);

        $hydrated = $builder->stateFromRecord($record->fresh());
        $this->assertSame('Buena sesión inicial', $hydrated["field_{$summary->id}"]);
    }
}
