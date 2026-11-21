<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

interface WorkerPairingCodeRepository
{
    public function save(WorkerPairingCode $code): void;

    public function findByCode(string $code): ?WorkerPairingCode;

    public function delete(WorkerPairingCode $code): void;
}
