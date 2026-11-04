<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Standard\Exports\OfferExporter;
use App\Filament\Standard\Exports\OfferExportZipDownloader;
use App\Repository\AssignmentRepository;
use App\Services\AssignmentService;
use App\Services\OfferExportFileService;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Delivers the ZIP of an offer export to its creator and records the packed offers as downloaded.
 */
final class OfferExportDownloadController extends Controller
{
    public function __construct(
        private readonly OfferExportFileService $files,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly AssignmentService $assignments,
        private readonly OfferExportZipDownloader $downloader,
    ) {
    }

    public function __invoke(Request $request, int $exportId): BinaryFileResponse|Response|RedirectResponse
    {
        $guard = (string) $request->query('authGuard');
        abort_unless(array_key_exists($guard, config('auth.guards')), 401);

        if (!auth($guard)->check()) {
            // links from the mail are often opened signed out: sign in, then continue to the download
            $panel = $this->panelFor($guard);
            abort_if($panel === null, 401);

            return redirect()->guest($panel->getLoginUrl());
        }

        $export = Export::query()->find($exportId);
        if ($export === null) {
            // prepared downloads are removed after a day, the notification stays in the bell
            return $this->expired();
        }

        abort_unless($export->user()->is(auth($guard)->user()), 403);
        abort_unless($export->exporter === OfferExporter::class && $export->completed_at !== null, 404);

        if (!$this->files->hasZip($export)) {
            return $this->expired();
        }

        foreach ($this->assignmentRepository->findByIds($this->files->packedIds($export)) as $assignment) {
            $this->assignments->markDownloaded($assignment, (string) $request->ip(), $request->userAgent());
        }

        return ($this->downloader)($export);
    }

    private function panelFor(string $guard): ?Panel
    {
        foreach (Filament::getPanels() as $panel) {
            if ($panel->getAuthGuard() === $guard && $panel->hasLogin()) {
                return $panel;
            }
        }

        return null;
    }

    /** Explain that the prepared download is gone instead of showing a bare error page. */
    private function expired(): Response
    {
        return response()->view('offers.export-expired', [], 410);
    }
}
