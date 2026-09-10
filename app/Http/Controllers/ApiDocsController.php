<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ApiDocumentationService;
use Illuminate\Contracts\View\View;

class ApiDocsController extends Controller
{
    public function index(ApiDocumentationService $documentation): View
    {
        return view('api-docs', ['documentations' => $documentation->all()]);
    }
}
