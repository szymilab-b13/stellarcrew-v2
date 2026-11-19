<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function mb_strlen;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;

use function preg_match;
use function sprintf;
use function trim;

class Worker
{
    public const int NAME_MAX = 80;

    public const int HOST_LABEL_MAX = 120;

    public const int RABBITMQ_USER_MAX = 80;

    public const string RABBITMQ_USER_PATTERN = '/^[a-z0-9_-]+$/';

    public const string FINGERPRINT_HASH_PATTERN = '/^[a-f0-9]{64}$/';

    /**
     * @param array<string, mixed>|null $fingerprintMeta
     */
    public function __construct(
        private readonly WorkerId $id,
        private readonly UserId $ownerUserId,
        private string $name,
        private ?string $hostLabel,
        private string $secretTokenHash,
        private readonly string $rabbitmqUser,
        private ?DateTimeImmutable $lastHeartbeatAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?string $fingerprintHash = null,
        private ?array $fingerprintMeta = null,
    ) {
        $this->guardName($name);
        $this->guardHostLabel($hostLabel);
        $this->guardSecretTokenHash($secretTokenHash);
        $this->guardRabbitmqUser($rabbitmqUser);
        $this->guardFingerprintHash($fingerprintHash);
    }

    public static function create(
        WorkerId $id,
        UserId $ownerUserId,
        string $name,
        string $rabbitmqUser,
        string $secretTokenHash,
        DateTimeImmutable $now,
        ?string $hostLabel = null,
    ): self {
        return new self(
            $id,
            $ownerUserId,
            $name,
            $hostLabel,
            $secretTokenHash,
            $rabbitmqUser,
            null,
            $now,
            $now,
        );
    }

    public function id(): WorkerId
    {
        return $this->id;
    }

    public function ownerUserId(): UserId
    {
        return $this->ownerUserId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function hostLabel(): ?string
    {
        return $this->hostLabel;
    }

    public function secretTokenHash(): string
    {
        return $this->secretTokenHash;
    }

    public function rabbitmqUser(): string
    {
        return $this->rabbitmqUser;
    }

    public function lastHeartbeatAt(): ?DateTimeImmutable
    {
        return $this->lastHeartbeatAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function fingerprintHash(): ?string
    {
        return $this->fingerprintHash;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fingerprintMeta(): ?array
    {
        return $this->fingerprintMeta;
    }

    /**
     * @param array<string, mixed>|null $meta
     */
    public function updateFingerprint(?string $hash, ?array $meta, DateTimeImmutable $now): void
    {
        $this->guardFingerprintHash($hash);
        $this->fingerprintHash = $hash;
        $this->fingerprintMeta = $meta;
        $this->updatedAt = $now;
    }

    /**
     * Updates only the raw metadata (hostname/kernelVersion/etc.) without touching the stable hash.
     * Used when a fingerprint mismatch is detected and the UI needs the latest observed meta for diff.
     *
     * @param array<string, mixed>|null $meta
     */
    public function touchFingerprintMeta(?array $meta, DateTimeImmutable $now): void
    {
        $this->fingerprintMeta = $meta;
        $this->updatedAt = $now;
    }

    public function rename(string $name, DateTimeImmutable $now): void
    {
        $this->guardName($name);
        if ($this->name === $name) {
            return;
        }

        $this->name = $name;
        $this->updatedAt = $now;
    }

    public function updateHostLabel(?string $hostLabel, DateTimeImmutable $now): void
    {
        $this->guardHostLabel($hostLabel);
        if ($this->hostLabel === $hostLabel) {
            return;
        }

        $this->hostLabel = $hostLabel;
        $this->updatedAt = $now;
    }

    public function recordHeartbeat(DateTimeImmutable $now): void
    {
        $this->lastHeartbeatAt = $now;
        $this->updatedAt = $now;
    }

    public function rotateSecret(string $secretTokenHash, DateTimeImmutable $now): void
    {
        $this->guardSecretTokenHash($secretTokenHash);
        $this->secretTokenHash = $secretTokenHash;
        $this->updatedAt = $now;
    }

    public function isOnline(DateTimeImmutable $now, int $thresholdSeconds = 60): bool
    {
        if (!$this->lastHeartbeatAt instanceof DateTimeImmutable) {
            return false;
        }

        return ($now->getTimestamp() - $this->lastHeartbeatAt->getTimestamp()) <= $thresholdSeconds;
    }

    private function guardName(string $name): void
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Worker name cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::NAME_MAX) {
            throw new InvalidArgumentException(sprintf('Worker name cannot exceed %d characters.', self::NAME_MAX));
        }
    }

    private function guardHostLabel(?string $hostLabel): void
    {
        if ($hostLabel === null) {
            return;
        }

        if (mb_strlen($hostLabel) > self::HOST_LABEL_MAX) {
            throw new InvalidArgumentException(sprintf('Worker host label cannot exceed %d characters.', self::HOST_LABEL_MAX));
        }
    }

    private function guardSecretTokenHash(string $hash): void
    {
        if (trim($hash) === '') {
            throw new InvalidArgumentException('Worker secret token hash cannot be empty.');
        }
    }

    private function guardFingerprintHash(?string $hash): void
    {
        if ($hash === null) {
            return;
        }

        if (preg_match(self::FINGERPRINT_HASH_PATTERN, $hash) !== 1) {
            throw new InvalidArgumentException(sprintf('Worker fingerprint hash must match sha256 hex pattern: "%s".', $hash));
        }
    }

    private function guardRabbitmqUser(string $user): void
    {
        $trimmed = trim($user);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Worker RabbitMQ user cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::RABBITMQ_USER_MAX) {
            throw new InvalidArgumentException(sprintf('Worker RabbitMQ user cannot exceed %d characters.', self::RABBITMQ_USER_MAX));
        }

        if (preg_match(self::RABBITMQ_USER_PATTERN, $trimmed) !== 1) {
            throw new InvalidArgumentException(sprintf('Worker RabbitMQ user must match pattern [a-z0-9_-]+: "%s".', $user));
        }
    }
}
