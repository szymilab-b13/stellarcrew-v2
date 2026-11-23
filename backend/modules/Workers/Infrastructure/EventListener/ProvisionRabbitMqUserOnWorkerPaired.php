<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\EventListener;

use Modules\Workers\Api\Event\WorkerPaired;
use Modules\Workers\Domain\RabbitMqAdminClient;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: WorkerPaired::class)]
final readonly class ProvisionRabbitMqUserOnWorkerPaired
{
    public function __construct(
        private RabbitMqAdminClient $admin,
    ) {
    }

    public function __invoke(WorkerPaired $event): void
    {
        $this->admin->provisionWorkerUser(
            $event->rabbitmqUser,
            $event->rabbitmqPassword,
            $event->workerId,
        );
    }
}
