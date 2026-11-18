<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\EventListener;

use Modules\Agents\Domain\AgentRepository;
use Modules\Workers\Api\Event\WorkerRevoked;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: WorkerRevoked::class)]
final readonly class CascadeWorkerRevokedListener
{
    public function __construct(
        private AgentRepository $agents,
    ) {
    }

    public function __invoke(WorkerRevoked $event): void
    {
        foreach ($this->agents->findByDefaultWorker($event->workerId) as $agent) {
            $agent->assignDefaultWorker(null, $event->occurredAt);
            $this->agents->save($agent);
        }
    }
}
