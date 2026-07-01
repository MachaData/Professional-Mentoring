@php $locale = app()->getLocale(); @endphp

@if($tools->isNotEmpty())
    <h2 class="text-lg font-semibold mb-3">{{ __('Materiales y herramientas') }}</h2>
    <div class="bg-white rounded-xl border border-gray-200 divide-y mb-8">
        @foreach ($tools as $tool)
            <a href="{{ $tool->url() }}" target="_blank" rel="noopener"
               class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition">
                <div>
                    <div class="font-medium">{{ $tool->getTranslation('name', $locale) }}</div>
                    <div class="text-sm text-gray-500">{{ $tool->getTranslation('description', $locale) }}</div>
                </div>
                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ $tool->type }}</span>
            </a>
        @endforeach
    </div>
@endif

@if($surveys->isNotEmpty())
    <h2 class="text-lg font-semibold mb-3">{{ __('Encuestas') }}</h2>
    <div class="bg-white rounded-xl border border-gray-200 divide-y mb-8">
        @foreach ($surveys as $survey)
            <a href="{{ $survey->external_url }}" target="_blank" rel="noopener"
               class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition">
                <div>
                    <div class="font-medium">{{ $survey->getTranslation('name', $locale) }}</div>
                    <div class="text-sm text-gray-500">{{ $survey->getTranslation('description', $locale) }}</div>
                </div>
                <span class="text-xs px-3 py-1 rounded-full text-white" style="background: var(--brand)">{{ __('Abrir') }}</span>
            </a>
        @endforeach
    </div>
@endif
