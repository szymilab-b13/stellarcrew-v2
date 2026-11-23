<?php

declare(strict_types=1);

namespace Modules\Workers\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;

final readonly class WorkerRevoked
{
    public function __construct(
        public WorkerId $workerId,
        public UserId $ownerUserId,
        public UserId $actorId,
        public DateTimeImmutable $occurredAt,
        public string $rabbitmqUser,
    ) {
    }
}
