<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Security;

use Modules\Auth\Application\PasswordHasher;
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
