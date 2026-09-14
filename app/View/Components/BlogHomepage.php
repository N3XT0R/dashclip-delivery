<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Repository\PostRepository;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class BlogHomepage extends Component
{
    public function __construct(private readonly PostRepository $posts)
    {
    }

    /** Render the same article cards used by the public blog overview. */
    public function render(): View
    {
        return view('components.blog-homepage', ['articles' => $this->posts->homepage(app()->getLocale())]);
    }
}
