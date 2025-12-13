<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyOffersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_channel_operator_can_access_page(): void
    {
        $role = Role::factory()->forRole(RoleEnum::CHANNEL_OPERATOR)->forGuard(GuardEnum::STANDARD)->create();
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user, GuardEnum::STANDARD->value)
            ->get(route('filament.standard.pages.my-offers'));

        $response->assertOk();
    }

    public function test_non_operator_cannot_access(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, GuardEnum::STANDARD->value)
            ->get(route('filament.standard.pages.my-offers'));

        $response->assertForbidden();
    }
}
