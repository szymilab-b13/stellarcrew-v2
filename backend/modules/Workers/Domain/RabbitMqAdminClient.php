<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

use Modules\Shared\Kernel\WorkerId;

interface RabbitMqAdminClient
{
    public function provisionWorkerUser(string $username, string $password, WorkerId $workerId): void;

    public function removeUser(string $username): void;
}
