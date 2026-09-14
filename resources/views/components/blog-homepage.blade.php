@if($articles->isNotEmpty())
<section class="public-width py-10">
    <x-public.section-heading eyebrow="Blog" :title="__('blog.from_the_blog')">{{ __('blog.intro') }}</x-public.section-heading>
    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">@foreach($articles as $article)<x-public.blog.card :article="$article" compact />@endforeach</div>
    <div class="mt-6"><x-public.button :href="route(app()->getLocale() === 'en' ? 'blog.en.index' : 'blog.index')">{{ __('blog.all') }}</x-public.button></div>
</section>
@endif
