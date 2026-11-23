<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use DateTimeImmutable;
use Modules\Shared\Kernel\WorkerId;

final readonly class WorkerDetailView
{
    /**
     * @param array<string, mixed>|null $fingerprintMeta
     * @param list<array{
     *     at: string,
     *     status: string,
     *     previousHash: string|null,
     *     previousMeta: array<string, mixed>|null,
     *     newHash: string|null,
     *     newMeta: array<string, mixed>|null
     * }> $fingerprintChangeHistory
     */
    public function __construct(
        public WorkerId $id,
        public string $name,
        public ?string $hostLabel,
        public string $rabbitmqUser,
        public ?DateTimeImmutable $lastHeartbeatAt,
        public bool $isOnline,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?string $fingerprintHash,
        public ?string $fingerprintHashShort,
        public string $fingerprintStatus,
        public ?array $fingerprintMeta,
        public array $fingerprintChangeHistory,
    ) {
    }
}
