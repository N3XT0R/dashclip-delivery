@extends('layouts.app')
@section('indexable', '1')
@section('title', $title.' | DashClip Delivery')
@section('description', $description)
@section('content')
    <article class="editorial rounded-xl border border-border bg-panel p-6 sm:p-10">
        @isset($html)
            <div lang="en">{!! $html !!}</div>
        @else
            <h1>{{ $title }}</h1>
            <div lang="en" class="whitespace-pre-wrap font-mono text-sm">{{ $text }}</div>
        @endisset
    </article>
@endsection
