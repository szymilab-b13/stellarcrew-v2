<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;

interface WorkerRepository
{
    public function save(Worker $worker): void;

    public function delete(Worker $worker): void;

    public function findById(WorkerId $id): ?Worker;

    public function findByRabbitmqUser(string $rabbitmqUser): ?Worker;

    /**
     * @return list<Worker>
     */
    public function findByOwner(UserId $ownerUserId): array;
}
