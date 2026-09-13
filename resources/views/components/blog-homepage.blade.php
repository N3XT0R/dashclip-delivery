@if($articles->isNotEmpty())
<section class="public-section public-width">
    <x-public.section-heading eyebrow="Blog" :title="__('blog.from_the_blog')">{{ __('blog.intro') }}</x-public.section-heading>
    <div class="grid gap-6 md:grid-cols-3">@foreach($articles as $article)<x-public.blog.card :article="$article" />@endforeach</div>
    <div class="mt-8"><x-public.button :href="route(app()->getLocale() === 'en' ? 'blog.en.index' : 'blog.index')">{{ __('blog.all') }}</x-public.button></div>
</section>
@endif
