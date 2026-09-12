@props(['headline' => ''])
<section class="editorial mx-auto max-w-2xl rounded-xl border border-border bg-panel p-6 sm:p-10">
    <h1 class="mb-6 text-3xl font-bold">{{ $headline }}</h1>
    {{ $slot }}
</section>
