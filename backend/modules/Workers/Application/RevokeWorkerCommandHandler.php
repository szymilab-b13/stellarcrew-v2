<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\Clock;
use Modules\Workers\Api\Event\WorkerRevoked;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerNotFoundException;
use Modules\Workers\Domain\WorkerRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

use function sprintf;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class RevokeWorkerCommandHandler
{
    public const string AUDIT_ACTION = 'workers.worker.revoked';

    public const string AUDIT_RESOURCE_TYPE = 'worker';

    public function __construct(
        private WorkerRepository $workers,
        private AuditLoggerInterface $audit,
        private Clock $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    public function __invoke(RevokeWorkerCommand $command): void
    {
        $worker = $this->workers->findById($command->workerId);
        if (!$worker instanceof Worker) {
            throw WorkerNotFoundException::forId($command->workerId);
        }

        if (!$worker->ownerUserId()->equals($command->actorUserId)) {
            throw new AccessDeniedException(sprintf('Cannot revoke worker "%s".', $command->workerId));
        }

        $now = $this->clock->now();
        $ownerUserId = $worker->ownerUserId();
        $rabbitmqUser = $worker->rabbitmqUser();
        $name = $worker->name();

        $this->workers->delete($worker);

        $this->audit->log(new AuditEntry(
            userId: $command->actorUserId,
            action: self::AUDIT_ACTION,
            occurredAt: $now,
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $command->workerId,
            metadata: [
                'name' => $name,
                'rabbitmq_user' => $rabbitmqUser,
            ],
        ));

        $this->events->dispatch(new WorkerRevoked(
            workerId: $command->workerId,
            ownerUserId: $ownerUserId,
            actorId: $command->actorUserId,
            occurredAt: $now,
        ));
    }
}
