<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\EventListener;

use Modules\Workers\Api\Event\WorkerRevoked;
use Modules\Workers\Domain\RabbitMqAdminClient;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: WorkerRevoked::class)]
final readonly class DeprovisionRabbitMqUserOnWorkerRevoked
{
    public function __construct(
        private RabbitMqAdminClient $admin,
    ) {
    }

    public function __invoke(WorkerRevoked $event): void
    {
        $this->admin->removeUser($event->rabbitmqUser);
    }
}
