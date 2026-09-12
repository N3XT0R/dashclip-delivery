<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PublicDocumentService;
use Illuminate\Contracts\View\View;

final class PublicDocumentController extends Controller
{
    public function changelog(PublicDocumentService $documents): View
    {
        return view('document', [
            'title' => 'Changelog',
            'description' => 'Änderungen und Versionsgeschichte von DashClip Delivery.',
            'html' => $documents->changelog(),
        ]);
    }

    public function license(PublicDocumentService $documents): View
    {
        return view('document', [
            'title' => 'Lizenz',
            'description' => 'Lizenzbedingungen für den Quellcode von DashClip Delivery.',
            'text' => $documents->license(),
        ]);
    }
}
