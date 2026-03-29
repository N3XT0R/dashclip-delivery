@php
    /** @var array{processingStatus: \App\Enum\ProcessingStatusEnum|null, ingestStatus: \App\DTO\Ingest\IngestStatusDto|null} $state */
    $state = $getState();

    $processingStatus = $state['processingStatus'] ?? null;
    $ingestStatus = $state['ingestStatus'] ?? null;

    $processingStatusLabel = $processingStatus !== null
        ? __('status.processing_status.' . $processingStatus->value)
        : __('status.processing_status.unknown');

    $processingStatusColor = match ($processingStatus?->value) {
        'completed' => 'success',
        'failed' => 'danger',
        'running' => 'info',
        default => 'gray',
    };
@endphp

@if ($ingestStatus)
    <div class="space-y-4">

        {{-- Header --}}
        <div>
            <div class="text-sm font-semibold text-gray-900">
                {{ __('ingest.status.heading') }}
            </div>

            <div class="text-sm text-gray-600">
                {{ __('ingest.status.description') }}
            </div>
        </div>

        {{-- Status + Progress Summary --}}
        <div class="flex items-center gap-3">
            <x-filament::badge :color="$processingStatusColor">
                {{ $processingStatusLabel }}
            </x-filament::badge>

            <span class="text-sm text-gray-700">
                {{ __('ingest.status.progress_label', [
                    'completed' => $ingestStatus->completedSteps,
                    'total' => $ingestStatus->totalSteps,
                    'percent' => $ingestStatus->progressPercent,
                ]) }}
            </span>
        </div>

        {{-- Progress Bar --}}
        <div class="space-y-1">
            <div class="flex justify-between text-sm text-gray-900">
                <span>{{ __('ingest.status.progress') }}</span>
                <span>{{ $ingestStatus->progressPercent }}%</span>
            </div>

            <div class="h-2 overflow-hidden rounded-full bg-gray-200">
                <div
                    class="h-2 rounded-full bg-primary-600"
                    style="width: {{ $ingestStatus->progressPercent }}%;"
                ></div>
            </div>
        </div>

        {{-- Steps --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            @foreach ($ingestStatus->steps as $step)
                @php
                    $translatedStep = __('ingest.steps.' . $step->name);
                    $stepLabel = $translatedStep !== 'ingest.steps.' . $step->name
                        ? $translatedStep
                        : $step->name;

                    $stepColor = match ($step->status) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'running' => 'info',
                        default => 'gray',
                    };
                @endphp

                <div
                    class="flex items-center justify-between px-4 py-3 {{ !$loop->last ? 'border-b border-gray-200' : '' }}">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $stepLabel }}
                        </div>

                        @if ($step->isCurrent)
                            <div class="text-xs text-gray-500">
                                {{ __('ingest.status.current') }}
                            </div>
                        @endif
                    </div>

                    <x-filament::badge :color="$stepColor" size="sm">
                        {{ __('ingest.step_status.' . $step->status) }}
                    </x-filament::badge>
                </div>
            @endforeach
        </div>

    </div>
@endif
