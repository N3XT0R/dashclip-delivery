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

<div class="space-y-4">
    <div class="flex items-center gap-3">
        <x-filament::badge :color="$processingStatusColor">
            {{ $processingStatusLabel }}
        </x-filament::badge>

        @if ($ingestStatus !== null)
            <span class="text-sm text-gray-500">
                {{ __('ingest.status.progress_label', [
                    'completed' => $ingestStatus->completedSteps,
                    'total' => $ingestStatus->totalSteps,
                    'percent' => $ingestStatus->progressPercent,
                ]) }}
            </span>
        @endif
    </div>

    @if ($ingestStatus !== null)
        <div class="space-y-2">
            <div class="flex justify-between text-sm">
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

        <div class="rounded-xl border border-gray-200 bg-white divide-y divide-gray-200">
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

                <div class="flex items-center justify-between px-4 py-3">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-950">
                            {{ $stepLabel }}
                        </span>

                        @if ($step->isCurrent)
                            <span class="text-xs text-gray-500">
                                {{ __('ingest.status.current') }}
                            </span>
                        @endif
                    </div>

                    <x-filament::badge :color="$stepColor" size="sm">
                        {{ __('ingest.step_status.' . $step->status) }}
                    </x-filament::badge>
                </div>
            @endforeach
        </div>
    @endif
</div>
