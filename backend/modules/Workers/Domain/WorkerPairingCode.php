<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Modules\Shared\Kernel\UserId;

use function preg_match;
use function sprintf;

class WorkerPairingCode
{
    public const string CODE_PATTERN = '/^[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/';

    public const int DEFAULT_TTL_SECONDS = 600;

    public function __construct(
        private readonly string $code,
        private readonly UserId $ownerUserId,
        private readonly DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $claimedAt,
        private readonly DateTimeImmutable $createdAt,
    ) {
        if (preg_match(self::CODE_PATTERN, $code) !== 1) {
            throw new InvalidArgumentException(sprintf('Pairing code must match pattern XXXX-XXXX (unambiguous chars): "%s".', $code));
        }
    }

    public static function issue(
        string $code,
        UserId $ownerUserId,
        DateTimeImmutable $now,
        int $ttlSeconds = self::DEFAULT_TTL_SECONDS,
    ): self {
        $expiresAt = $now->modify(sprintf('+%d seconds', $ttlSeconds));

        return new self($code, $ownerUserId, $expiresAt, null, $now);
    }

    public function code(): string
    {
        return $this->code;
    }

    public function ownerUserId(): UserId
    {
        return $this->ownerUserId;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function claimedAt(): ?DateTimeImmutable
    {
        return $this->claimedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isClaimed(): bool
    {
        return $this->claimedAt instanceof DateTimeImmutable;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now > $this->expiresAt;
    }

    public function claim(DateTimeImmutable $now): void
    {
        if ($this->isClaimed()) {
            throw new DomainException(sprintf('Pairing code "%s" already claimed.', $this->code));
        }

        if ($this->isExpired($now)) {
            throw new DomainException(sprintf('Pairing code "%s" expired.', $this->code));
        }

        $this->claimedAt = $now;
    }
}
