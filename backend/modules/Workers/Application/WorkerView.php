<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use DateTimeImmutable;
use Modules\Shared\Kernel\WorkerId;

final readonly class WorkerView
{
    public function __construct(
        public WorkerId $id,
        public string $name,
        public ?string $hostLabel,
        public string $rabbitmqUser,
        public ?DateTimeImmutable $lastHeartbeatAt,
        public bool $isOnline,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?string $fingerprintHashShort,
        public string $fingerprintStatus,
    ) {
    }
}
