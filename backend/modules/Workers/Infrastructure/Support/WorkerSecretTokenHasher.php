<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Support;

use function base64_encode;

use const PASSWORD_BCRYPT;

use function password_hash;
use function password_verify;
use function random_bytes;
use function rtrim;
use function strtr;

final class WorkerSecretTokenHasher
{
    public function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public function hash(string $token): string
    {
        return password_hash($token, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function verify(string $token, string $hash): bool
    {
        return password_verify($token, $hash);
    }
}
