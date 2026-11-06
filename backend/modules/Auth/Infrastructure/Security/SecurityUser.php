<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Security;

use function assert;

use Modules\Auth\Domain\User as DomainUser;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private DomainUser $user)
    {
    }

    public function getUserIdentifier(): string
    {
        $email = $this->user->email();
        assert($email !== '');

        return $email;
    }

    public function getPassword(): string
    {
        return $this->user->passwordHash();
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_'.strtoupper($this->user->role()->value)];
    }

    public function eraseCredentials(): void
    {
    }

    public function domainUser(): DomainUser
    {
        return $this->user;
    }
}
