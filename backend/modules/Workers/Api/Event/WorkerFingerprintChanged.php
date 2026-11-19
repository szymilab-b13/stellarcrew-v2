<?php

declare(strict_types=1);

namespace Modules\Workers\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;

final readonly class WorkerFingerprintChanged
{
    /**
     * @param array<string, mixed>|null $previousMeta
     * @param array<string, mixed>|null $currentMeta
     */
    public function __construct(
        public WorkerId $workerId,
        public UserId $ownerUserId,
        public ?array $previousMeta,
        public ?array $currentMeta,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
