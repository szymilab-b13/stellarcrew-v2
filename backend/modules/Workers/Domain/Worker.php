<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function end;
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
     * @param array<string, mixed>|null                $fingerprintMeta
     * @param list<array{
     *     at: string,
     *     status: string,
     *     previousHash: string|null,
     *     previousMeta: array<string, mixed>|null,
     *     newHash: string|null,
     *     newMeta: array<string, mixed>|null
     * }>                                              $fingerprintChangeHistory
     * @param array<string, mixed>|null                $hostInfo
     */
    public function __construct(
        private readonly WorkerId $id,
        private readonly UserId $ownerUserId,
        private string $name,
        private ?string $hostLabel,
        private readonly string $rabbitmqUser,
        private ?DateTimeImmutable $lastHeartbeatAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?string $fingerprintHash = null,
        private ?array $fingerprintMeta = null,
        private array $fingerprintChangeHistory = [],
        private ?array $hostInfo = null,
    ) {
        $this->guardName($name);
        $this->guardHostLabel($hostLabel);
        $this->guardRabbitmqUser($rabbitmqUser);
        $this->guardFingerprintHash($fingerprintHash);
    }

    public static function create(
        WorkerId $id,
        UserId $ownerUserId,
        string $name,
        string $rabbitmqUser,
        DateTimeImmutable $now,
        ?string $hostLabel = null,
    ): self {
        return new self(
            $id,
            $ownerUserId,
            $name,
            $hostLabel,
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
     * @return list<array{
     *     at: string,
     *     status: string,
     *     previousHash: string|null,
     *     previousMeta: array<string, mixed>|null,
     *     newHash: string|null,
     *     newMeta: array<string, mixed>|null
     * }>
     */
    public function fingerprintChangeHistory(): array
    {
        return $this->fingerprintChangeHistory;
    }

    /**
     * @param array<string, mixed>|null $meta
     */
    public function updateFingerprint(?string $hash, ?array $meta, DateTimeImmutable $now): void
    {
        $this->guardFingerprintHash($hash);

        if ($this->fingerprintHash !== null && $this->fingerprintHash !== $hash) {
            $this->fingerprintChangeHistory[] = [
                'at' => $now->format(DateTimeImmutable::ATOM),
                'status' => 'accepted',
                'previousHash' => $this->fingerprintHash,
                'previousMeta' => $this->fingerprintMeta,
                'newHash' => $hash,
                'newMeta' => $meta,
            ];
        }

        $this->fingerprintHash = $hash;
        $this->fingerprintMeta = $meta;
        $this->updatedAt = $now;
    }

    /**
     * Appends a 'detected' entry to the change history without rotating the stored hash.
     * The stored meta is refreshed so the UI diff shows the latest observed signal.
     *
     * @param array<string, mixed>|null $newMeta
     */
    public function recordFingerprintMismatch(?string $newHash, ?array $newMeta, DateTimeImmutable $now): void
    {
        $this->guardFingerprintHash($newHash);

        $this->fingerprintChangeHistory[] = [
            'at' => $now->format(DateTimeImmutable::ATOM),
            'status' => 'detected',
            'previousHash' => $this->fingerprintHash,
            'previousMeta' => $this->fingerprintMeta,
            'newHash' => $newHash,
            'newMeta' => $newMeta,
        ];

        $this->fingerprintMeta = $newMeta;
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

    public function isOnline(DateTimeImmutable $now, int $thresholdSeconds = 60): bool
    {
        if (!$this->lastHeartbeatAt instanceof DateTimeImmutable) {
            return false;
        }

        return ($now->getTimestamp() - $this->lastHeartbeatAt->getTimestamp()) <= $thresholdSeconds;
    }

    public function fingerprintStatus(): string
    {
        if ($this->fingerprintHash === null) {
            return 'missing';
        }

        $lastEntry = end($this->fingerprintChangeHistory);
        if ($lastEntry !== false && $lastEntry['status'] === 'detected') {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function hostInfo(): ?array
    {
        return $this->hostInfo;
    }

    /**
     * @param array<string, mixed>|null $hostInfo
     */
    public function updateHostInfo(?array $hostInfo, DateTimeImmutable $now): void
    {
        if ($this->hostInfo === $hostInfo) {
            return;
        }

        $this->hostInfo = $hostInfo;
        $this->updatedAt = $now;
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
