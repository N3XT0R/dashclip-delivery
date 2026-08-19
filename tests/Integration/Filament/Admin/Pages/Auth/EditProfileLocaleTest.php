<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Admin\Pages\Auth;

use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class EditProfileLocaleTest extends DatabaseTestCase
{
    public function testLocaleFieldExists(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->assertFormFieldExists('locale');
    }

    public function testSavingLocalePersistsToUserRecord(): void
    {
        $user = User::factory()->create(['locale' => null, 'submitted_name' => 'Original Submitted']);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm(['locale' => 'en'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function testLeavingLocaleEmptyPersistsNull(): void
    {
        $user = User::factory()->create(['locale' => 'en', 'submitted_name' => 'Original Submitted']);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm(['locale' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($user->fresh()->locale);
    }
}
