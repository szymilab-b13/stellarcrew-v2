<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Workers;

use Modules\Workers\Domain\WorkerRefreshToken;
use Modules\Workers\Domain\WorkerRefreshTokenRepository;

final class InMemoryWorkerRefreshTokenRepository implements WorkerRefreshTokenRepository
{
    /** @var array<string, WorkerRefreshToken> */
    private array $tokens = [];

    public function save(WorkerRefreshToken $token): void
    {
        $this->tokens[$token->tokenHash()] = $token;
    }

    public function findByTokenHash(string $tokenHash): ?WorkerRefreshToken
    {
        return $this->tokens[$tokenHash] ?? null;
    }
}
