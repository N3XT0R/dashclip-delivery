<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Clip;
use App\Models\Video;
use App\Services\InfoImporter;
use Tests\DatabaseTestCase;

class InfoImporterTest extends DatabaseTestCase
{
    protected InfoImporter $infoImporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->infoImporter = app(InfoImporter::class);
    }

    /** Write a small CSV file with given rows; returns absolute path. */
    private function writeCsv(array $rows): string
    {
        $path = sys_get_temp_dir().'/info_import_'.bin2hex(random_bytes(4)).'.csv';
        $fh = fopen($path, 'wb');
        // Header line (ignored by importer except to advance the file pointer)
        fwrite($fh, "filename;start;end;note;bundle;role;submitted_by;preferred_channel\n");
        foreach ($rows as $row) {
            // Ensure 8 columns as expected by the importer
            $line = implode(';', array_pad($row, 8, ''))."\n";
            fwrite($fh, $line);
        }
        fclose($fh);
        return $path;
    }

    public function testImportCreatesClipsForExistingVideos(): void
    {
        // Arrange: two videos; importer matches by original_name == basename(filename)
        $v1 = Video::factory()->create(['original_name' => 'dashcam_A.mp4']);
        $v2 = Video::factory()->create(['original_name' => 'dashcam_B.mov']);

        $csv = $this->writeCsv([
            // filename, start,  end,   note,        bundle,   role,    submitted_by
            ['dashcam_A.mp4', '00:05', '01:05', 'clip A', 'bundle-1', 'editor', 'alice'],
            ['dashcam_B.mov', '75', '130', 'clip B', 'bundle-2', 'review', 'bob'],
        ]);

        // Act
        $result = $this->infoImporter->import($csv);

        // Assert counters
        $resultArray = $result->toArray();
        $this->assertContains(['created' => 2, 'updated' => 0, 'warnings' => 0], $resultArray);

        // Assert DB rows for A
        $this->assertDatabaseHas('clips', [
            'video_id' => $v1->id,
            'start_sec' => 5,
            'end_sec' => 65,
            'note' => 'clip A',
            'bundle_key' => 'bundle-1',
            'role' => 'editor',
            'submitted_by' => 'alice',
        ]);

        // Assert DB rows for B (seconds parsing)
        $this->assertDatabaseHas('clips', [
            'video_id' => $v2->id,
            'start_sec' => 75,
            'end_sec' => 130,
            'note' => 'clip B',
            'bundle_key' => 'bundle-2',
            'role' => 'review',
            'submitted_by' => 'bob',
        ]);

        @unlink($csv);
    }

    public function testImportUpdatesExistingClipAndAppliesDefaults(): void
    {
        // Existing video & clip (role/start/end all NULL to match empty role/start/end)
        $video = Video::factory()->create(['original_name' => 'update_me.mp4']);
        $clip = Clip::factory()->for($video)->create([
            'start_sec' => 60,
            'end_sec' => 120,
            'note' => 'old note',
            'bundle_key' => null,
            'role' => null,
            'submitted_by' => null,
        ]);

        $csv = $this->writeCsv([
            // Same start/end; empty bundle/submitter in CSV → defaults should be applied
            ['update_me.mp4', '01:00', '02:00', 'new note', '', '', ''],
        ]);

        // Act
        $result = $this->infoImporter->import($csv, [
            'default-bundle' => 'DEF-BUNDLE',
            'default-submitter' => 'system',
        ]);

        // Assert: one row updated, none created, no warnings
        $resultArray = $result->toArray();
        $this->assertContains(['created' => 0, 'updated' => 1, 'warnings' => 0], $resultArray);

        $this->assertDatabaseHas('clips', [
            'id' => $clip->id,
            'video_id' => $video->id,
            'start_sec' => 60,
            'end_sec' => 120,
            'note' => 'new note',
            'bundle_key' => 'DEF-BUNDLE',
            'role' => null,         // still null (CSV empty + no infer-role)
            'submitted_by' => 'system',
        ]);

        @unlink($csv);
    }

    public function testImportInfersRoleFromFilenameWhenEnabled(): void
    {
        // Videos that encode role in filename suffix _F / _R
        $vF = Video::factory()->create(['original_name' => 'road_F.mp4']);
        $vR = Video::factory()->create(['original_name' => 'rear_R.mov']);

        $csv = $this->writeCsv([
            ["\xEF\xBB\xBFroad_F.mp4", '00:10', '00:20', 'front', '', '', ''],
            // filename contains BOM; should be trimmed
            ['rear_R.mov', '15', '45', 'rear', '', '', ''],
        ]);

        // Act
        $result = $this->infoImporter->import($csv, ['infer-role' => true]);
        $resultArray = $result->toArray();

        $this->assertContains(['created' => 2, 'updated' => 0, 'warnings' => 0], $resultArray);

        $this->assertDatabaseHas('clips', [
            'video_id' => $vF->id,
            'start_sec' => 10,
            'end_sec' => 20,
            'role' => 'F',
            'note' => 'front',
        ]);
        $this->assertDatabaseHas('clips', [
            'video_id' => $vR->id,
            'start_sec' => 15,
            'end_sec' => 45,
            'role' => 'R',
            'note' => 'rear',
        ]);

        @unlink($csv);
    }

    public function testImportWarnsOnUnknownVideoAndInvalidTime(): void
    {
        $warnings = [];
        $csv = $this->writeCsv([
            // Unknown video + two invalid times -> 3 warnings total
            ['not_found.mp4', 'xx:yy', 'aa', 'note', '', '', ''],
        ]);

        $result = $this->infoImporter->import($csv, [], function (string $msg) use (&$warnings) {
            $warnings[] = $msg;
        });

        $this->assertContains(['created' => 0, 'updated' => 0, 'warnings' => 3], $result->toArray());
        $this->assertCount(3, $warnings);

        $invalidTimeCount = count(array_filter($warnings, fn ($m) => str_contains($m, 'Ungültige Zeitangabe')));
        $notFoundCount = count(array_filter($warnings, fn ($m) => str_contains($m, 'Kein Video gefunden')));

        $this->assertSame(2, $invalidTimeCount, 'Expected two invalid time warnings (start and end).');
        $this->assertSame(1, $notFoundCount, 'Expected one "video not found" warning.');

        @unlink($csv);
    }


    public function testImportParsesHmsAndSecondsCorrectly(): void
    {
        $video = Video::factory()->create(['original_name' => 'hms.mp4']);

        $csv = $this->writeCsv([
            // 1:02:03 -> 3723 seconds; end "75" -> 75 seconds
            ['hms.mp4', '1:02:03', '75', 'timed', 'B', '', 'tester'],
        ]);

        $result = $this->infoImporter->import($csv);

        $this->assertContains(['created' => 1, 'updated' => 0, 'warnings' => 0], $result->toArray());

        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'start_sec' => 3723,
            'end_sec' => 75,
            'note' => 'timed',
            'bundle_key' => 'B',
            'submitted_by' => 'tester',
        ]);

        @unlink($csv);
    }

    public function testImportResolvesPreferredChannelByName(): void
    {
        $video = Video::factory()->create(['original_name' => 'pref_by_name.mp4']);
        $channel = \App\Models\Channel::factory()->create(['name' => 'Highway West']);

        $csv = $this->writeCsv([
            ['pref_by_name.mp4', '00:00', '00:10', 'n', '', '', 'alice', 'highway west'],
        ]);

        $result = $this->infoImporter->import($csv);

        $this->assertSame(0, $result->stats->warnings);
        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel' => 'highway west',
            'preferred_channel_id' => $channel->getKey(),
        ]);

        @unlink($csv);
    }

    public function testImportResolvesPreferredChannelById(): void
    {
        $video = Video::factory()->create(['original_name' => 'pref_by_id.mp4']);
        $channel = \App\Models\Channel::factory()->create();

        $csv = $this->writeCsv([
            ['pref_by_id.mp4', '00:00', '00:10', 'n', '', '', 'alice', (string) $channel->getKey()],
        ]);

        $result = $this->infoImporter->import($csv);

        $this->assertSame(0, $result->stats->warnings);
        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel_id' => $channel->getKey(),
        ]);

        @unlink($csv);
    }

    public function testImportWarnsAndStoresRawValueForUnknownPreferredChannel(): void
    {
        $video = Video::factory()->create(['original_name' => 'pref_unknown.mp4']);

        $warnings = [];
        $csv = $this->writeCsv([
            ['pref_unknown.mp4', '00:00', '00:10', 'n', '', '', 'alice', 'No Such Channel'],
        ]);

        $result = $this->infoImporter->import($csv, [], function (string $m) use (&$warnings): void {
            $warnings[] = $m;
        });

        $this->assertSame(1, $result->stats->warnings);
        $this->assertNotEmpty($warnings);
        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel' => 'No Such Channel',
            'preferred_channel_id' => null,
        ]);

        @unlink($csv);
    }

    public function testImportWithoutPreferredChannelColumnKeepsExistingBehaviour(): void
    {
        $video = Video::factory()->create(['original_name' => 'legacy.mp4']);

        // 7-column row (no preferred_channel) — array_pad fills the 8th slot with ''.
        $csv = $this->writeCsv([
            ['legacy.mp4', '00:00', '00:10', 'n', '', '', 'alice'],
        ]);

        $result = $this->infoImporter->import($csv);

        $this->assertSame(0, $result->stats->warnings);
        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel' => null,
            'preferred_channel_id' => null,
        ]);

        @unlink($csv);
    }

    public function testReimportUpdatesPreferredChannelOnExistingClip(): void
    {
        $video = Video::factory()->create(['original_name' => 'reimport.mp4']);
        $channel = \App\Models\Channel::factory()->create(['name' => 'Second Lane']);

        $first = $this->writeCsv([['reimport.mp4', '00:00', '00:10', 'n', '', '', 'alice']]);
        $this->infoImporter->import($first);
        @unlink($first);

        $second = $this->writeCsv([['reimport.mp4', '00:00', '00:10', 'n', '', '', 'alice', 'Second Lane']]);
        $result = $this->infoImporter->import($second);
        @unlink($second);

        $this->assertSame(1, $result->stats->updated);
        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel' => 'Second Lane',
            'preferred_channel_id' => $channel->getKey(),
        ]);
    }

    public function testReimportNullsPreferredChannelIdWhenChannelBecomesPaused(): void
    {
        $video = Video::factory()->create(['original_name' => 'pref_paused.mp4']);
        $channel = \App\Models\Channel::factory()->create(['name' => 'Pause Lane']);

        $csv = $this->writeCsv([
            ['pref_paused.mp4', '00:00', '00:10', 'n', '', '', 'alice', 'Pause Lane'],
        ]);

        $first = $this->infoImporter->import($csv);
        $this->assertSame(0, $first->stats->warnings);
        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel' => 'Pause Lane',
            'preferred_channel_id' => $channel->getKey(),
        ]);

        // Channel is paused between imports; the same raw value now resolves to null.
        $channel->update(['is_video_reception_paused' => true]);

        $second = $this->infoImporter->import($csv);

        $this->assertSame(1, $second->stats->warnings);
        $this->assertSame(1, $second->stats->updated);
        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel' => 'Pause Lane',
            'preferred_channel_id' => null,
        ]);

        @unlink($csv);
    }
}
