<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Workers;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerRepository;

use function usort;

final class InMemoryWorkerRepository implements WorkerRepository
{
    /** @var array<string, Worker> */
    private array $workers = [];

    public function save(Worker $worker): void
    {
        $this->workers[(string) $worker->id()] = $worker;
    }

    public function findById(WorkerId $id): ?Worker
    {
        return $this->workers[(string) $id] ?? null;
    }

    public function findByRabbitmqUser(string $rabbitmqUser): ?Worker
    {
        foreach ($this->workers as $worker) {
            if ($worker->rabbitmqUser() === $rabbitmqUser) {
                return $worker;
            }
        }

        return null;
    }

    public function findByOwner(UserId $ownerUserId): array
    {
        $result = [];
        foreach ($this->workers as $worker) {
            if ($worker->ownerUserId()->equals($ownerUserId)) {
                $result[] = $worker;
            }
        }

        usort($result, static fn (Worker $left, Worker $right): int => $left->createdAt() <=> $right->createdAt());

        return $result;
    }
}
