<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use function substr;

use Modules\Shared\Kernel\Clock;
use Modules\Workers\Domain\WorkerRepository;

final readonly class ListUserWorkersQueryHandler
{
    public const int ONLINE_THRESHOLD_SECONDS = 120;

    public const int FINGERPRINT_HASH_SHORT_LENGTH = 12;

    public function __construct(
        private WorkerRepository $workers,
        private Clock $clock,
    ) {
    }

    /**
     * @return list<WorkerView>
     */
    public function __invoke(ListUserWorkersQuery $query): array
    {
        $now = $this->clock->now();
        $views = [];
        foreach ($this->workers->findByOwner($query->ownerUserId) as $worker) {
            $fingerprintHash = $worker->fingerprintHash();
            $views[] = new WorkerView(
                id: $worker->id(),
                name: $worker->name(),
                hostLabel: $worker->hostLabel(),
                rabbitmqUser: $worker->rabbitmqUser(),
                lastHeartbeatAt: $worker->lastHeartbeatAt(),
                isOnline: $worker->isOnline($now, self::ONLINE_THRESHOLD_SECONDS),
                createdAt: $worker->createdAt(),
                updatedAt: $worker->updatedAt(),
                fingerprintHashShort: $fingerprintHash !== null ? substr($fingerprintHash, 0, self::FINGERPRINT_HASH_SHORT_LENGTH) : null,
                fingerprintStatus: $worker->fingerprintStatus(),
            );
        }

        return $views;
    }
}
