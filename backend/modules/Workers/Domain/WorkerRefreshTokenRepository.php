<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

interface WorkerRefreshTokenRepository
{
    public function findByTokenHash(string $tokenHash): ?WorkerRefreshToken;

    public function save(WorkerRefreshToken $token): void;
}
