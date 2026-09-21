<?php

declare(strict_types=1);

namespace Tests\Integration\Commands;

use App\Filament\Standard\Exports\OfferExporter;
use App\Models\User;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class CleanExpiredZipsCommandTest extends DatabaseTestCase
{
    public function testOfferExportsOlderThanADayAreDeletedWithTheirFiles(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $make = function (string $exporter, $createdAt) use ($user): Export {
            $export = new Export();
            $export->forceFill(['exporter' => $exporter, 'file_disk' => 'local', 'file_name' => 'x', 'total_rows' => 1]);
            $export->user()->associate($user);
            $export->created_at = $createdAt;
            $export->save();
            Storage::disk('local')->put($export->getFileDirectory().'/x.zip', 'zip');

            return $export;
        };
        $old = $make(OfferExporter::class, now()->subHours(25));
        $fresh = $make(OfferExporter::class, now()->subHours(2));
        $foreign = $make('App\\Other\\Exporter', now()->subDays(3));

        $this->artisan('zips:clean-expired')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertFalse(Storage::disk('local')->directoryExists($old->getFileDirectory()));
        $this->assertModelExists($fresh);
        $this->assertModelExists($foreign);
        $this->assertTrue(Storage::disk('local')->exists($fresh->getFileDirectory().'/x.zip'));
    }
}
