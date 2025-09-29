<?php

namespace App\View\Components;

use App\Models\Assignment;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class VideoCard extends Component
{
    public Assignment $assignment;
    public bool $disabled = false;

    /**
     * Create a new component instance.
     */
    public function __construct(Assignment $assignment, bool $disabled = false)
    {
        $this->assignment = $assignment;
        $this->disabled = $disabled;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.video-card');
    }
}
