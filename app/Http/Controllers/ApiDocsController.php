<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ApiDocumentationService;
use Illuminate\Contracts\View\View;

class ApiDocsController extends Controller
{
    public function index(ApiDocumentationService $documentation): View
    {
        $all = $documentation->all();

        return view('api-docs', [
            'authentication' => $all->firstWhere('key', 'authentication'),
            'resourceApis' => $all->reject(static fn ($doc) => $doc->key === 'authentication')->values(),
        ]);
    }
}
