<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Modules\Shared\Kernel\WorkerId;

use function preg_match;
use function sprintf;

class WorkerRefreshToken
{
    public const string TOKEN_HASH_PATTERN = '/^[a-f0-9]{64}$/';

    public const int DEFAULT_TTL_SECONDS = 7_776_000;

    public function __construct(
        private readonly string $tokenHash,
        private readonly WorkerId $workerId,
        private readonly DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $consumedAt,
        private readonly DateTimeImmutable $createdAt,
    ) {
        if (preg_match(self::TOKEN_HASH_PATTERN, $tokenHash) !== 1) {
            throw new InvalidArgumentException(sprintf('Worker refresh token hash must match sha256 hex pattern: "%s".', $tokenHash));
        }
    }

    public static function issue(
        string $tokenHash,
        WorkerId $workerId,
        DateTimeImmutable $now,
        int $ttlSeconds = self::DEFAULT_TTL_SECONDS,
    ): self {
        $expiresAt = $now->modify(sprintf('+%d seconds', $ttlSeconds));

        return new self($tokenHash, $workerId, $expiresAt, null, $now);
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function workerId(): WorkerId
    {
        return $this->workerId;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function consumedAt(): ?DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isConsumed(): bool
    {
        return $this->consumedAt instanceof DateTimeImmutable;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now > $this->expiresAt;
    }

    public function markConsumed(DateTimeImmutable $now): void
    {
        if ($this->isConsumed()) {
            throw new DomainException(sprintf('Refresh token "%s" already consumed.', $this->tokenHash));
        }

        $this->consumedAt = $now;
    }
}
