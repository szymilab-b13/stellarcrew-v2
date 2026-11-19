<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

final readonly class RevokeAllOwnerWorkersCommandHandler
{
    public function __construct(
        private RevokeWorkerCommandHandler $revokeWorker,
        private ListUserWorkersQueryHandler $listWorkers,
    ) {
    }

    public function __invoke(RevokeAllOwnerWorkersCommand $command): int
    {
        $workers = ($this->listWorkers)(new ListUserWorkersQuery($command->ownerUserId));
        $count = 0;
        foreach ($workers as $worker) {
            ($this->revokeWorker)(new RevokeWorkerCommand(
                workerId: $worker->id,
                actorUserId: $command->ownerUserId,
            ));
            ++$count;
        }

        return $count;
    }
}
