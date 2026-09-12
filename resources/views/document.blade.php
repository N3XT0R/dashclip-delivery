@extends('layouts.app')
@section('title', $title.' | DashClip Delivery')
@section('description', $description)
@section('content')
    <article class="editorial rounded-xl border border-border bg-panel p-6 sm:p-10">
        @isset($html)
            {!! $html !!}
        @else
            <h1>{{ $title }}</h1>
            <div class="whitespace-pre-wrap font-mono text-sm">{{ $text }}</div>
        @endisset
    </article>
@endsection
