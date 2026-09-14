@props(['articles'])
@if($articles->isNotEmpty())
<section class="mt-12 border-t border-border pt-10">
    <h2 class="mb-6 text-2xl font-bold">{{ __('blog.related') }}</h2>
    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">@foreach($articles as $article)<x-public.blog.card :article="$article" />@endforeach</div>
</section>
@endif
