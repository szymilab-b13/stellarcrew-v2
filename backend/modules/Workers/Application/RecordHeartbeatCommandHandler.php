<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Shared\Kernel\Clock;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerRepository;
use Modules\Workers\Domain\WorkerSecretTokenHasher;

use function sprintf;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final readonly class RecordHeartbeatCommandHandler
{
    public function __construct(
        private WorkerRepository $workers,
        private WorkerSecretTokenHasher $hasher,
        private Clock $clock,
    ) {
    }

    public function __invoke(RecordHeartbeatCommand $command): void
    {
        $worker = $this->workers->findById($command->workerId);
        if (!$worker instanceof Worker) {
            throw new UserNotFoundException(sprintf('Worker "%s" not found.', $command->workerId));
        }

        if (!$this->hasher->verify($command->plaintextSecret, $worker->secretTokenHash())) {
            throw new AccessDeniedException('Invalid worker credentials.');
        }

        $worker->recordHeartbeat($this->clock->now());
        $this->workers->save($worker);
    }
}
