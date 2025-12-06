<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Forms\Components\Checkbox;
use Illuminate\Support\HtmlString;

class FilamentComponents
{
    public static function tosCheckbox(string $name = 'accept_terms'): Checkbox
    {
        $tosUrl = route('tos');
        $tosText = __('auth.register.tos_link_text');
        $label = __('auth.register.accept_terms_label', [
            'tos_link' => '<a href="'.$tosUrl.'" target="_blank" class="underline text-primary-600 hover:text-primary-700">'.$tosText.'</a>',
        ]);

        return Checkbox::make($name)
            ->label(fn() => new HtmlString($label))
            ->required()
            ->accepted()
            ->columnSpanFull();
    }
}