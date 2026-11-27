@extends('layouts.app')

@section('title', __('my_offers.export.gone.title'))
@section('description', __('my_offers.export.gone.body'))

@section('content')
    <section class="mb-6">
        <h1 class="text-2xl font-semibold mb-4">{{ __('my_offers.export.gone.title') }}</h1>
        <p class="mb-4">{{ __('my_offers.export.gone.body') }}</p>
        <p>
            <a href="/standard" class="underline">{{ __('my_offers.export.gone.back') }}</a>
        </p>
    </section>
@endsection
