@extends('layouts.app')
@section('content_language', 'de')
@section('title', 'Nicht verwendete Videos | '.$channel->name)
@section('description', 'Nicht benötigte Clips für eine erneute Verteilung freigeben.')
@section('robots', 'noindex, nofollow')
@section('subtitle', 'Batch #'.$batch->id)
@section('content')
    <section class="mx-auto max-w-3xl">
        <h1 class="mb-5 text-3xl font-bold">Nicht verwendete Videos</h1>
        <p class="mb-8 text-muted">Gib nicht benötigte Clips von {{ $channel->name }} für eine erneute Verteilung frei.</p>
        @if (session('success'))<div class="flash flash--ok" role="status">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="flash flash--err" role="alert">{{ session('error') }}</div>@endif
        @if ($items->isEmpty())
            <p class="panel">Es gibt aktuell keine heruntergeladenen Videos, die du freigeben könntest.</p>
        @else
            <form method="POST" action="{{ $postUrl }}" class="panel">
                @csrf
                <fieldset>
                    <legend class="mb-4 font-semibold">Clips zur Rückgabe auswählen</legend>
                    <ul class="divide-y divide-border">
                        @foreach ($items as $assignment)
                            <li>
                                <label class="flex min-h-14 cursor-pointer items-center gap-3 py-3">
                                    <input type="checkbox" name="assignment_ids[]" value="{{ $assignment->id }}" class="size-5 shrink-0 accent-orange-700">
                                    <span class="min-w-0 break-words">{{ $assignment->video->original_name ?: basename($assignment->video->path) }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </fieldset>
                <x-public.button type="submit" class="mt-6">Ausgewählte Videos freigeben</x-public.button>
            </form>
        @endif
    </section>
@endsection
