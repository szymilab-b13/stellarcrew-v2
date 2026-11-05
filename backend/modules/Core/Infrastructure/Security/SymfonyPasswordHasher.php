<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Security;

use Modules\Core\Application\Install\PasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class SymfonyPasswordHasher implements PasswordHasher
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function hash(string $plaintext): string
    {
        return $this->hasher->hashPassword(new HashablePlaintextUser(), $plaintext);
    }
}
