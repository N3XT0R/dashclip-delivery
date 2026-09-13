<?php

declare(strict_types=1);

namespace App\Enum\Blog;

enum PostStatusEnum: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';
    case RETRACTED = 'retracted';
}
