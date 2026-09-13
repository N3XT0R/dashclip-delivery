<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Repository\ChannelRepository;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PublicFooter extends Component
{
    /**
     * Resolve the repository supplying publicly listed channel names.
     */
    public function __construct(private readonly ChannelRepository $channelRepository)
    {
    }

    /**
     * Render the shared footer with the current channel visibility choices.
     */
    public function render(): View
    {
        return view('components.public.footer', [
            'channels' => $this->channelRepository->getHomepageChannelNames(),
        ]);
    }
}
