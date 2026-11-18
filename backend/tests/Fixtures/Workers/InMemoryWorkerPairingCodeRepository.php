<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Workers;

use Modules\Workers\Domain\WorkerPairingCode;
use Modules\Workers\Domain\WorkerPairingCodeRepository;

final class InMemoryWorkerPairingCodeRepository implements WorkerPairingCodeRepository
{
    /** @var array<string, WorkerPairingCode> */
    private array $codes = [];

    public function save(WorkerPairingCode $code): void
    {
        $this->codes[$code->code()] = $code;
    }

    public function findByCode(string $code): ?WorkerPairingCode
    {
        return $this->codes[$code] ?? null;
    }
}
