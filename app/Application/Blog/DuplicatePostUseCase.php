<?php

declare(strict_types=1);

namespace App\Application\Blog;

use App\Models\Post;
use App\Enum\Blog\PostStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class DuplicatePostUseCase
{
    /** Copy content and tags into unpublished translations with unique new slugs. */
    public function execute(Post $post): Post
    {
        Gate::authorize('replicate', $post);
        return DB::transaction(function () use ($post): Post {
            $copy = $post->replicate();
            $copy->save();
            $copy->tags()->sync($post->tags()->pluck('blog_tags.id'));
            foreach ($post->translations as $translation) {
                $duplicate = $translation->replicate();
                $duplicate->post_id = $copy->id;
                $duplicate->slug = Str::limit($translation->slug, 160, '').'-'.Str::lower(Str::random(10));
                $duplicate->status = PostStatusEnum::DRAFT;
                $duplicate->published_at = null;
                $duplicate->save();
            }
            return $copy;
        });
    }
}
