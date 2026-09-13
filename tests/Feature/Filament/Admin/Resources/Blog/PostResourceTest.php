<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin\Resources\Blog;

use App\Filament\Admin\Resources\Blog\PostResource;
use App\Filament\Admin\Resources\Blog\PostCategoryResource;
use App\Filament\Admin\Resources\Blog\PostTagResource;
use App\Filament\Admin\Resources\Blog\PostResource\Pages\EditPost;
use App\Filament\Admin\Resources\Blog\PostResource\Pages\CreatePost;
use App\Models\PostCategory;
use App\Filament\Admin\Resources\Blog\PostCategoryResource\Pages\CreatePostCategory;
use App\Filament\Admin\Resources\Blog\PostCategoryResource\Pages\EditPostCategory;
use App\Filament\Admin\Resources\Blog\PostTagResource\Pages\CreatePostTag;
use App\Application\Blog\DuplicatePostUseCase;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use App\Enum\Blog\PostStatusEnum;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class PostResourceTest extends DatabaseTestCase
{
    public function testBlogPageLabelsUseExplicitSingularAndPluralTranslations(): void
    {
        $editor = User::factory()->admin()->create();
        $this->actingAs($editor);
        foreach (['de' => [['Beitrag', 'Beiträge'], ['Kategorie', 'Kategorien'], ['Tag', 'Tags']], 'en' => [['Post', 'Posts'], ['Category', 'Categories'], ['Tag', 'Tags']]] as $locale => $labels) {
            $editor->update(['locale' => $locale]);
            app()->setLocale($locale);
            foreach ([PostResource::class, PostCategoryResource::class, PostTagResource::class] as $index => $resource) {
                [$singular, $plural] = $labels[$index];
                $this->assertSame($singular, $resource::getModelLabel());
                $this->assertSame($plural, $resource::getPluralModelLabel());
                $this->get($resource::getUrl('index'))->assertOk()->assertSeeText($plural)
                    ->assertDontSeeText(['Beiträges', 'Kategoriens', 'Tagses']);
                $this->get($resource::getUrl('create'))->assertOk()
                    ->assertSeeText(__('filament-panels::resources/pages/create-record.title', ['label' => $singular]));
            }
        }
    }

    public function testEditorCanManageTaxonomiesAndCannotDeleteAnAssignedCategory(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach ([PostResource::class, PostCategoryResource::class, PostTagResource::class] as $resource) {
            $this->get($resource::getUrl('index'))->assertOk()->assertSee($resource::getUrl('create'), false);
        }
        $translation = ['locale' => 'de', 'slug' => 'technology', 'name' => 'Technik'];
        Livewire::test(CreatePostCategory::class)->fillForm(['slug' => 'technology', 'icon' => 'camera', 'translations' => [$translation]])
            ->call('create')->assertHasNoFormErrors();
        Livewire::test(CreatePostTag::class)->fillForm(['slug' => 'technology', 'translations' => [$translation]])
            ->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('blog_tag_translations', ['slug' => 'technology']);
        $category = PostCategory::query()->where('slug', 'technology')->firstOrFail();
        Post::factory()->create(['category_id' => $category->id]);
        Livewire::test(EditPostCategory::class, ['record' => $category->id])->assertActionDisabled('delete');
        $this->assertSame(1, $category->posts()->count());
    }

    public function testEditorCanCreateTranslationsAndDuplicateSlugsAreRejected(): void
    {
        $editor = User::factory()->admin()->create();
        $this->actingAs($editor);
        $translation = ['locale' => 'de', 'slug' => 'new-article', 'title' => 'New article', 'excerpt' => 'Introduction', 'content' => 'Article body', 'status' => 'draft', 'is_indexable' => true];
        $form = ['author_id' => $editor->id, 'category_id' => PostCategory::factory()->create()->id, 'translations' => [$translation, [...$translation, 'locale' => 'en']]];
        Livewire::test(CreatePost::class)->fillForm($form)->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('blog_post_translations', ['slug' => 'new-article', 'locale' => 'de']);
        $this->assertDatabaseHas('blog_post_translations', ['slug' => 'new-article', 'locale' => 'en']);
        Livewire::test(CreatePost::class)->fillForm([...$form, 'translations' => [$translation]])->call('create')->assertHasFormErrors();
        $this->assertSame(2, PostTranslation::query()->where('slug', 'new-article')->count());
    }

    public function testEditorCanOpenTranslationsAndSaveArticle(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $post = Post::factory()->create();
        PostTranslation::factory()->for($post)->create(['locale' => 'de', 'title' => 'Deutscher Titel']);
        PostTranslation::factory()->for($post)->create(['locale' => 'en', 'title' => 'English title']);
        $this->get(PostResource::getUrl('edit', ['record' => $post]))->assertOk()
            ->assertSee('Deutscher Titel')->assertSee('English title');
        Livewire::test(EditPost::class, ['record' => $post->id])->call('save')->assertHasNoFormErrors();
    }

    public function testGuestsAndUsersWithoutPermissionsCannotAccessEditorialScreensOrPreview(): void
    {
        $this->get(PostResource::getUrl('index'))->assertRedirect();
        $article = PostTranslation::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->get(PostResource::getUrl('index'))->assertForbidden();
        $this->get(route('blog.preview', $article))->assertForbidden();
    }

    public function testAuthorizedPreviewIsNoindexAndDuplicateIsAlwaysDraft(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $article = PostTranslation::factory()->published()->create();
        $this->get(route('blog.preview', $article))->assertOk()->assertSee('noindex, nofollow');
        $copy = app(DuplicatePostUseCase::class)->execute($article->post);
        $this->assertNotSame($article->post_id, $copy->id);
        $this->assertSame(PostStatusEnum::DRAFT, $copy->translations->first()->status);
        $this->assertNull($copy->translations->first()->published_at);
        $this->get('/blog/'.$copy->translations->first()->slug)->assertNotFound();
    }
}
