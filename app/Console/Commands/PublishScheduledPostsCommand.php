<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enum\Blog\PostStatusEnum;
use App\Models\PostTranslation;
use Illuminate\Console\Command;

final class PublishScheduledPostsCommand extends Command
{
    protected $signature = 'blog:publish-scheduled';
    protected $description = 'Publish blog translations whose scheduled release time has arrived';

    /** Publish due translations through model events so public caches are invalidated. */
    public function handle(): int
    {
        PostTranslation::query()->where('status', PostStatusEnum::SCHEDULED)->where('published_at', '<=', now())
            ->eachById(fn (PostTranslation $translation) => $translation->update(['status' => PostStatusEnum::PUBLISHED]));
        return self::SUCCESS;
    }
}
