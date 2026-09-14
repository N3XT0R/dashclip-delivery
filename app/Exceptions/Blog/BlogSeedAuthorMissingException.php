<?php

declare(strict_types=1);

namespace App\Exceptions\Blog;

final class BlogSeedAuthorMissingException extends BlogException
{
    /** Explain the prerequisite without creating an account or assigning new privileges. */
    public static function forInitialContent(): self
    {
        return new self('BlogContentSeeder requires an existing user with the super_admin role for the web guard. No blog content was created.');
    }
}
