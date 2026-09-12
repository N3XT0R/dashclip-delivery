<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Services\PageService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Page extends Component
{
    public string $html;

    /**
     * Embed maintained page content beneath an existing heading when requested.
     */
    public function __construct(PageService $service, public string $slug, bool $embedded = false)
    {
        $this->html = $service->getHtml($slug) ?? '';
        if ($embedded) {
            $this->html = preg_replace_callback(
                '/<(\/?)(h)([1-6])(\b[^>]*)>/i',
                static fn (array $heading): string => '<'.$heading[1].'h'.min(6, (int) $heading[3] + 2).$heading[4].'>',
                $this->html,
            ) ?? $this->html;
        }
    }

    public function render(): View
    {
        return view('components.page');
    }
}
