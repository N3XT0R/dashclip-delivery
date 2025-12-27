<?php

declare(strict_types=1);

namespace Tests\Feature\Standard\Pages\ChannelApplication;

use App\Application\Channel\Application\CreateChannelApplication;
use App\Enum\Channel\ApplicationEnum;
use App\Filament\Standard\Pages\ChannelApplication;
use App\Models\Channel;
use App\Models\ChannelApplication as ChannelApplicationModel;
use App\Models\User;
use App\Repository\ChannelRepository;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Mockery;
use ReflectionProperty;
use Tests\TestCase;

final class ChannelApplicationPageTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        restore_error_handler();
        restore_exception_handler();

        parent::tearDown();
    }

    public function testMountLoadsPendingApplicationAndPrefillsEmail(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('cannot')->andReturnFalse();
        $user->email = 'user@example.com';
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('id')->andReturn($user->getKey());

        $pending = ChannelApplicationModel::factory()
            ->make([
                'id' => 10,
                'user_id' => $user->getKey(),
                'status' => ApplicationEnum::PENDING->value,
            ]);

        $repository = Mockery::mock(ChannelRepository::class);
        $repository
            ->shouldReceive('getChannelApplicationsByUser')
            ->once()
            ->with($user, ApplicationEnum::PENDING)
            ->andReturn($pending->newCollection([$pending]));

        $this->app->instance(ChannelRepository::class, $repository);

        $page = new ChannelApplication();
        $page->mount();

        self::assertTrue($pending->is($page->pendingApplication));
        self::assertSame($user->email, $page->data['new_channel_email']);
    }

    public function testTableConfigurationUsesAuthenticatedUserAndColumnCallbacks(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('cannot')->andReturnFalse();
        $user->email = 'user@example.com';
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('id')->andReturn($user->getKey());

        $capturedColumns = [];

        $table = Mockery::mock(Table::class);
        $table
            ->shouldReceive('query')
            ->once()
            ->with(Mockery::on(function ($callback) use ($user): bool {
                $builder = $callback();

                self::assertInstanceOf(Builder::class, $builder);
                self::assertStringContainsString('"user_id" = ?', $builder->toSql());
                self::assertSame([$user->getKey()], $builder->getBindings());

                return true;
            }))
            ->andReturnSelf();
        $table->shouldReceive('heading')->once()->andReturnSelf();
        $table
            ->shouldReceive('columns')
            ->once()
            ->with(Mockery::on(function (array $columns) use (&$capturedColumns): bool {
                $capturedColumns = $columns;

                return count($columns) === 4
                    && $columns[0] instanceof TextColumn
                    && $columns[1] instanceof TextColumn;
            }))
            ->andReturnSelf();
        $table->shouldReceive('recordActions')->once()->andReturnSelf();
        $table->shouldReceive('defaultSort')->once()->with('updated_at', 'desc')->andReturnSelf();

        $page = new ChannelApplication();
        $page->table($table);

        $metaRecord = ChannelApplicationModel::factory()->make([
            'user_id' => $user->getKey(),
            'meta' => [
                'new_channel' => ['name' => 'Meta Channel'],
            ],
        ]);

        $channel = Channel::factory()->make(['id' => 5, 'name' => 'Existing Channel']);
        $channelRecord = ChannelApplicationModel::factory()->make([
            'id' => 20,
            'channel_id' => $channel->getKey(),
            'user_id' => $user->getKey(),
        ]);
        $channelRecord->setRelation('channel', $channel);

        $fallbackRecord = ChannelApplicationModel::factory()->make([
            'user_id' => $user->getKey(),
            'meta' => [],
        ]);

        $channelColumn = $capturedColumns[0];
        $channelCallback = $channelColumn->getGetStateUsingCallback();

        self::assertSame('Meta Channel', $channelCallback($metaRecord));
        self::assertSame('Existing Channel', $channelCallback($channelRecord));
        self::assertSame(
            __('filament.channel_application.table.columns.channel_unknown'),
            $channelCallback($fallbackRecord)
        );

        $statusColumn = $capturedColumns[1];
        $formatProperty = new ReflectionProperty($statusColumn, 'formatStateUsing');
        $formatProperty->setAccessible(true);
        $formatter = $formatProperty->getValue($statusColumn);

        self::assertIsCallable($formatter);
        self::assertSame(
            __('filament.channel_application.status.approved'),
            $formatter(ApplicationEnum::APPROVED->value)
        );
    }

    public function testSubmitCreatesApplicationAndSendsSuccessNotification(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('cannot')->andReturnFalse();
        $user->email = 'user@example.com';
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('id')->andReturn($user->getKey());

        $useCase = Mockery::mock(CreateChannelApplication::class);
        $useCase
            ->shouldReceive('handle')
            ->once()
            ->with(['state'], $user);

        $this->app->instance(CreateChannelApplication::class, $useCase);

        if (method_exists(Notification::class, 'fake')) {
            Notification::fake();
        }

        $page = new class() extends ChannelApplication {
            public bool $mountedAfterSubmit = false;

            public function mount(): void
            {
                $this->mountedAfterSubmit = true;
            }
        };

        $page->form = Mockery::mock();
        $page->form->shouldReceive('getState')->once()->andReturn(['state']);
        $page->form->shouldReceive('fill')->once()->with([]);

        $page->submit();

        self::assertTrue($page->mountedAfterSubmit);
    }

    public function testSubmitHandlesDomainExceptionWithDangerNotification(): void
    {
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('id')->andReturn($user->getKey());

        $useCase = Mockery::mock(CreateChannelApplication::class);
        $useCase
            ->shouldReceive('handle')
            ->once()
            ->with(['state'], $user)
            ->andThrow(new DomainException('test-error'));

        $this->app->instance(CreateChannelApplication::class, $useCase);

        if (method_exists(Notification::class, 'fake')) {
            Notification::fake();
        }

        $page = new ChannelApplication();
        $page->form = Mockery::mock();
        $page->form->shouldReceive('getState')->once()->andReturn(['state']);
        $page->form->shouldNotReceive('fill');

        $page->submit();

        self::assertNull($page->pendingApplication);
    }
}
