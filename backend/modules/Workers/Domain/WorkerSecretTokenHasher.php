<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

interface WorkerSecretTokenHasher
{
    public function generateToken(): string;

    public function hash(string $token): string;

    public function verify(string $token, string $hash): bool;
}
