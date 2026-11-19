<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Shared\Kernel\Clock;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function sprintf;

final readonly class AcceptFingerprintCommandHandler
{
    public function __construct(
        private WorkerRepository $workers,
        private Clock $clock,
    ) {
    }

    public function __invoke(AcceptFingerprintCommand $command): void
    {
        $worker = $this->workers->findById($command->workerId);
        if (!$worker instanceof Worker) {
            throw new NotFoundHttpException(sprintf('Worker "%s" not found.', $command->workerId));
        }

        if (!$worker->ownerUserId()->equals($command->actorId)) {
            throw new AccessDeniedHttpException(sprintf('Cannot accept fingerprint for worker "%s".', $command->workerId));
        }

        $worker->updateFingerprint($command->fingerprintHash, $command->fingerprintMeta, $this->clock->now());
        $this->workers->save($worker);
    }
}
