<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class HashablePlaintextUser implements PasswordAuthenticatedUserInterface
{
    public function getPassword(): ?string
    {
        return null;
    }
}
