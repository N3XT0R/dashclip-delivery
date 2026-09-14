<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Tests\DatabaseTestCase;

final class UserLastLoginTest extends DatabaseTestCase
{
    public function testLastLoginIsDisplayedOnUserListAndEditPages(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['last_login_at' => '2026-09-10 14:35:00']);

        $this->actingAs($admin, 'web');

        foreach (['de', 'en'] as $locale) {
            $admin->update(['locale' => $locale]);
            app()->setLocale($locale);

            foreach (['index' => [], 'edit' => ['record' => $user]] as $page => $parameters) {
                $this->get(UserResource::getUrl($page, $parameters, panel: 'admin'))
                    ->assertOk()
                    ->assertSee(__('filament.admin.labels.last_login'))
                    ->assertSee('10.09.2026 14:35');
            }
        }
    }

    public function testMissingLastLoginHasPlaceholderOnUserListAndEditPages(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['last_login_at' => null]);

        $this->actingAs($admin, 'web');

        foreach (['index' => [], 'edit' => ['record' => $user]] as $page => $parameters) {
            $this->get(UserResource::getUrl($page, $parameters, panel: 'admin'))
                ->assertOk()
                ->assertSee(__('filament.admin.labels.never_logged_in'));
        }
    }
}
