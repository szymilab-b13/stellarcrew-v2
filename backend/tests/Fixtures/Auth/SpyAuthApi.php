<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Auth;

use DateTimeImmutable;
use Modules\Auth\Api\AuthApi;
use Modules\Auth\Api\Dto\AuthUser;
use Modules\Shared\Kernel\UserId;
use Symfony\Component\Uid\Uuid;

final class SpyAuthApi implements AuthApi
{
    /** @var list<array{email: string, password: string}> */
    public array $calls = [];

    public function createAdmin(string $email, string $plaintextPassword): AuthUser
    {
        $this->calls[] = ['email' => $email, 'password' => $plaintextPassword];

        return new AuthUser(
            new UserId(Uuid::v7()),
            $email,
            new DateTimeImmutable('2026-11-05T09:00:00+00:00'),
        );
    }
}
