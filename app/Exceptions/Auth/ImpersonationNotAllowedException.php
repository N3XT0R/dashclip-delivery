<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

/**
 * Thrown when someone tries to view the application as another user without being allowed to.
 */
class ImpersonationNotAllowedException extends AuthException
{
}
