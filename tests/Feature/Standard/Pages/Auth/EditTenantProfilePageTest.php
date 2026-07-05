<?php

declare(strict_types=1);

namespace Tests\Feature\Standard\Pages\Auth;

use App\Filament\Standard\Pages\Auth\EditTenantProfile;
use Tests\TestCase;

final class EditTenantProfilePageTest extends TestCase
{
    public function testGetLabelReturnsProfile(): void
    {
        $this->assertSame(__('filament.standard.profile.label'), EditTenantProfile::getLabel());
    }
}
