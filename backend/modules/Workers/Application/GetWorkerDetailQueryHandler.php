<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use function sprintf;
use function substr;

use Modules\Shared\Kernel\Clock;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetWorkerDetailQueryHandler
{
    public const int ONLINE_THRESHOLD_SECONDS = 120;

    public const int FINGERPRINT_HASH_SHORT_LENGTH = 12;

    public function __construct(
        private WorkerRepository $workers,
        private Clock $clock,
    ) {
    }

    public function __invoke(GetWorkerDetailQuery $query): WorkerDetailView
    {
        $worker = $this->workers->findById($query->workerId);
        if (!$worker instanceof Worker) {
            throw new NotFoundHttpException(sprintf('Worker "%s" not found.', $query->workerId));
        }

        if (!$worker->ownerUserId()->equals($query->actorUserId)) {
            throw new AccessDeniedHttpException(sprintf('Cannot view worker "%s".', $query->workerId));
        }

        $now = $this->clock->now();
        $fingerprintHash = $worker->fingerprintHash();

        return new WorkerDetailView(
            id: $worker->id(),
            name: $worker->name(),
            hostLabel: $worker->hostLabel(),
            rabbitmqUser: $worker->rabbitmqUser(),
            lastHeartbeatAt: $worker->lastHeartbeatAt(),
            isOnline: $worker->isOnline($now, self::ONLINE_THRESHOLD_SECONDS),
            createdAt: $worker->createdAt(),
            updatedAt: $worker->updatedAt(),
            fingerprintHash: $fingerprintHash,
            fingerprintHashShort: $fingerprintHash !== null ? substr($fingerprintHash, 0, self::FINGERPRINT_HASH_SHORT_LENGTH) : null,
            fingerprintStatus: $worker->fingerprintStatus(),
            fingerprintMeta: $worker->fingerprintMeta(),
            fingerprintChangeHistory: $worker->fingerprintChangeHistory(),
        );
    }
}
