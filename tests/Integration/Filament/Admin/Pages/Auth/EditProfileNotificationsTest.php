<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Admin\Pages\Auth;

use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Models\Channel;
use App\Models\User;
use App\Notifications\OfferDownloadReadyNotification;
use App\Notifications\UserUploadDuplicatedNotification;
use App\Notifications\UserUploadProceedNotification;
use App\Repository\UserMailConfigRepository;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class EditProfileNotificationsTest extends DatabaseTestCase
{
    public function testNotificationPreferencesRenderedWithDefaults(): void
    {
        $user = User::factory()->create();
        $repository = app(UserMailConfigRepository::class);

        $repository->setForUser($user, UserUploadDuplicatedNotification::class, false);
        $repository->setForUser($user, UserUploadProceedNotification::class, true);

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->assertFormFieldExists('notifications.mail.types.' . UserUploadDuplicatedNotification::class)
            ->assertFormFieldExists('notifications.mail.types.' . UserUploadProceedNotification::class)
            ->assertSet('data.notifications.mail.types.' . UserUploadDuplicatedNotification::class, false)
            ->assertSet('data.notifications.mail.types.' . UserUploadProceedNotification::class, true);
    }

    public function testChannelOperatorsSeeTheDownloadMailPreference(): void
    {
        $operator = User::factory()->haveAccessToChannel(Channel::factory()->create())->create();

        $this->actingAs($operator);

        Livewire::test(EditProfile::class)
            ->assertFormFieldExists('notifications.mail.types.' . OfferDownloadReadyNotification::class)
            ->assertSet('data.notifications.mail.types.' . OfferDownloadReadyNotification::class, true);
    }

    public function testUsersWithoutChannelAccessDoNotSeeTheDownloadMailPreference(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(EditProfile::class)
            ->assertFormFieldDoesNotExist('notifications.mail.types.' . OfferDownloadReadyNotification::class)
            ->assertFormFieldExists('notifications.mail.types.' . UserUploadProceedNotification::class);
    }
}
