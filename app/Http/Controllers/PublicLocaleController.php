<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LocaleDiscoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicLocaleController extends Controller
{
    /** Persist an explicit public language choice and return to the same local page. */
    public function __invoke(Request $request, LocaleDiscoveryService $locales): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in($locales->list())],
            'return_to' => ['required', 'string', 'regex:~^/(?!/)[^\\\\\\x00-\\x20]*$~'],
        ]);

        return redirect(url($data['return_to']))->withCookie(cookie(
            'public_locale',
            $data['locale'],
            60 * 24 * 365,
            '/',
            secure: $request->isSecure(),
            httpOnly: true,
            sameSite: 'lax',
        ));
    }
}
