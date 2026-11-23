<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Shared\Kernel\Clock;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerNotFoundException;
use Modules\Workers\Domain\WorkerRepository;

final readonly class RecordHeartbeatCommandHandler
{
    public function __construct(
        private WorkerRepository $workers,
        private Clock $clock,
    ) {
    }

    public function __invoke(RecordHeartbeatCommand $command): void
    {
        $worker = $this->workers->findById($command->workerId);
        if (!$worker instanceof Worker) {
            throw WorkerNotFoundException::forId($command->workerId);
        }

        $worker->recordHeartbeat($this->clock->now());
        $this->workers->save($worker);
    }
}
