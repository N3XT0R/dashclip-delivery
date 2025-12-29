<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Channel;
use App\Models\ChannelConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\DatabaseTestCase;

final class ChannelConfigTest extends DatabaseTestCase
{
    public function testBelongsToChannel(): void
    {
        $channel = Channel::factory()->create();

        $config = ChannelConfig::query()->create([
            'channel_id' => $channel->getKey(),
            'key' => 'upload.limit',
            'value' => '10',
            'type' => 'int',
        ]);

        $this->assertTrue($config->channel->is($channel));
        $this->assertTrue($channel->config->contains($config));
    }

    /**
     * @dataProvider valueCastingFromStorageProvider
     */
    public function testValueCastingFromStorage(?string $type, mixed $storedValue, mixed $expectedPhp, ?callable $extraAssertion = null): void
    {
        $channel = Channel::factory()->create();

        $key = Str::uuid()->toString();

        DB::table('channel_configs')->insert([
            'channel_id' => $channel->getKey(),
            'key' => $key,
            'value' => $storedValue,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $config = ChannelConfig::query()->firstWhere('key', $key);

        $this->assertEquals($expectedPhp, $config->value);

        if ($extraAssertion !== null) {
            $extraAssertion($config);
        }
    }

    /**
     * @return array<string, array{0: ?string, 1: mixed, 2: mixed, 3?: callable}>
     */
    public static function valueCastingFromStorageProvider(): array
    {
        $datetimeString = '2024-01-15 12:34:56';

        return [
            'int' => ['int', '5', 5],
            'float' => ['float', '3.5', 3.5],
            'bool true' => ['bool', '1', true],
            'bool false' => ['bool', 'false', false],
            'json array' => ['json', json_encode(['enabled' => true]), ['enabled' => true]],
            'string default' => [null, 'plain-text', 'plain-text'],
            'datetime' => ['datetime', $datetimeString, Carbon::parse($datetimeString), static function (ChannelConfig $config) use ($datetimeString): void {
                $value = $config->value;
                self::assertInstanceOf(Carbon::class, $value);
                self::assertSame($datetimeString, $value->format('Y-m-d H:i:s'));
            }],
            'encrypted' => ['encrypted', encrypt('secret-token'), 'secret-token'],
        ];
    }

    /**
     * @dataProvider valueSetterProvider
     */
    public function testValueSetterCastsToStorage(string $type, mixed $phpValue, callable $assertions): void
    {
        $channel = Channel::factory()->create();

        $config = ChannelConfig::query()->create([
            'channel_id' => $channel->getKey(),
            'key' => Str::uuid()->toString(),
            'value' => 'seed',
            'type' => $type,
        ]);

        $config->value = $phpValue;
        $config->save();
        $config->refresh();

        $assertions($config);
    }

    /**
     * @return array<string, array{0: string, 1: mixed, 2: callable}>
     */
    public static function valueSetterProvider(): array
    {
        return [
            'int' => ['int', 42, static function (ChannelConfig $config): void {
                self::assertSame('42', $config->getRawOriginal('value'));
                self::assertSame(42, $config->value);
            }],
            'float' => ['float', 2.5, static function (ChannelConfig $config): void {
                self::assertSame('2.5', $config->getRawOriginal('value'));
                self::assertSame(2.5, $config->value);
            }],
            'bool true' => ['bool', true, static function (ChannelConfig $config): void {
                self::assertSame('1', $config->getRawOriginal('value'));
                self::assertTrue($config->value);
            }],
            'bool false' => ['bool', false, static function (ChannelConfig $config): void {
                self::assertSame('0', $config->getRawOriginal('value'));
                self::assertFalse($config->value);
            }],
            'json' => ['json', ['enabled' => false], static function (ChannelConfig $config): void {
                self::assertSame(json_encode(['enabled' => false]), $config->getRawOriginal('value'));
                self::assertSame(['enabled' => false], $config->value);
            }],
            'string' => ['string', 'hello world', static function (ChannelConfig $config): void {
                self::assertSame('hello world', $config->getRawOriginal('value'));
                self::assertSame('hello world', $config->value);
            }],
            'datetime' => ['datetime', Carbon::create(2024, 5, 20, 8, 0, 0), static function (ChannelConfig $config): void {
                self::assertSame('2024-05-20 08:00:00', $config->getRawOriginal('value'));
                self::assertInstanceOf(Carbon::class, $config->value);
                self::assertSame('2024-05-20 08:00:00', $config->value->format('Y-m-d H:i:s'));
            }],
            'encrypted' => ['encrypted', 'sensitive-data', static function (ChannelConfig $config): void {
                self::assertNotSame('sensitive-data', $config->getRawOriginal('value'));
                self::assertSame('sensitive-data', Crypt::decrypt($config->getRawOriginal('value')));
                self::assertSame('sensitive-data', $config->value);
            }],
        ];
    }
}
