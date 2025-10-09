<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources;

use App\Enum\MailStatus;
use App\Filament\Resources\MailLogResource\Pages\ListMailLogs;
use App\Filament\Resources\MailLogResource\Pages\ViewMailLog;
use App\Models\MailLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * Integration tests for the Filament MailLogResource.
 *
 * We verify:
 *  - ListMailLogs renders records with formatted columns and default sorting
 *  - ViewMailLog renders metadata, including headers stored as array or string
 */
final class MailLogResourceTest extends DatabaseTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function testListMailLogsShowsNewestFirstWithFormattedColumns(): void
    {
        $older = MailLog::query()->create([
            'message_id' => (string) Str::uuid(),
            'internal_id' => 'internal-older',
            'to' => 'older@example.test',
            'subject' => 'Older Subject',
            'status' => MailStatus::Bounced,
            'bounced_at' => Carbon::parse('2024-01-02 12:00:00'),
            'meta' => ['headers' => ['X-Older: 1']],
        ]);

        $older->forceFill([
            'created_at' => Carbon::parse('2024-01-02 12:00:00'),
            'updated_at' => Carbon::parse('2024-01-02 12:00:00'),
        ])->save();

        $newer = MailLog::query()->create([
            'message_id' => (string) Str::uuid(),
            'internal_id' => 'internal-newer',
            'to' => 'newer@example.test',
            'subject' => 'Newer Subject',
            'status' => MailStatus::Replied,
            'replied_at' => Carbon::parse('2024-02-03 09:15:00'),
            'meta' => ['headers' => ['X-Newer: 1']],
        ]);

        $newer->forceFill([
            'created_at' => Carbon::parse('2024-02-03 09:15:00'),
            'updated_at' => Carbon::parse('2024-02-03 09:15:00'),
        ])->save();

        Livewire::test(ListMailLogs::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$newer, $older])
            ->assertTableColumnStateSet('to', 'newer@example.test', record: $newer)
            ->assertTableColumnStateSet('to', 'older@example.test', record: $older)
            ->assertTableColumnStateSet('subject', 'Newer Subject', record: $newer)
            ->assertTableColumnFormattedStateSet('created_at', '03.02.2024 09:15', record: $newer)
            ->assertTableColumnFormattedStateSet('created_at', '02.01.2024 12:00', record: $older)
            ->tap(function ($livewire) use ($newer, $older) {
                $this->assertSame(
                    [$newer->getKey(), $older->getKey()],
                    $livewire->instance()->getTableRecords()->pluck('id')->all(),
                );
            })
            ->assertSeeText('replied');
    }

    public function testViewMailLogDisplaysMetadataHeadersFromArray(): void
    {
        $log = MailLog::query()->create([
            'message_id' => (string) Str::uuid(),
            'internal_id' => 'view-array',
            'to' => 'recipient@example.test',
            'subject' => 'Array Headers',
            'status' => MailStatus::Sent,
            'meta' => [
                'headers' => [
                    'X-Test-One: Value',
                    'X-Test-Two: Another',
                ],
            ],
        ]);

        $log->forceFill([
            'created_at' => Carbon::parse('2024-03-04 08:30:00'),
            'updated_at' => Carbon::parse('2024-03-04 08:30:00'),
        ])->save();

        Livewire::test(ViewMailLog::class, ['record' => $log->getKey()])
            ->assertStatus(200)
            ->assertSee('Empfänger')
            ->assertSee('recipient@example.test')
            ->assertSee('Array Headers')
            ->assertSee('X-Test-One: Value')
            ->assertSee('X-Test-Two: Another');
    }

    public function testViewMailLogDisplaysMetadataHeadersFromString(): void
    {
        $log = MailLog::query()->create([
            'message_id' => (string) Str::uuid(),
            'internal_id' => 'view-string',
            'to' => 'string@example.test',
            'subject' => 'String Headers',
            'status' => MailStatus::Sent,
            'meta' => [
                'headers' => 'Single-Header: Value',
            ],
        ]);

        $log->forceFill([
            'created_at' => Carbon::parse('2024-03-05 10:45:00'),
            'updated_at' => Carbon::parse('2024-03-05 10:45:00'),
        ])->save();

        Livewire::test(ViewMailLog::class, ['record' => $log->getKey()])
            ->assertStatus(200)
            ->assertSee('Single-Header: Value');
    }
}
