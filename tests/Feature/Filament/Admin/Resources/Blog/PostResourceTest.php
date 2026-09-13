<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin\Resources\Blog;

use App\Filament\Admin\Resources\Blog\PostResource;
use App\Filament\Admin\Resources\Blog\PostResource\Pages\EditPost;
use App\Application\Blog\DuplicatePostUseCase;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use App\Enum\Blog\PostStatusEnum;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class PostResourceTest extends DatabaseTestCase
{
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
