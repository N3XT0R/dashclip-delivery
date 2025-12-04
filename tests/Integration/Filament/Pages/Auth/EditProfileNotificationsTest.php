<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Pages\Auth;

use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
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
}
