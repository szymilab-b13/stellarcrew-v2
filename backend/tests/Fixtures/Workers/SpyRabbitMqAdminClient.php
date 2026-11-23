<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Workers;

use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Domain\RabbitMqAdminClient;

final class SpyRabbitMqAdminClient implements RabbitMqAdminClient
{
    /** @var list<array{username: string, password: string, workerId: string}> */
    public array $provisioned = [];

    /** @var list<string> */
    public array $removed = [];

    public function provisionWorkerUser(string $username, string $password, WorkerId $workerId): void
    {
        $this->provisioned[] = [
            'username' => $username,
            'password' => $password,
            'workerId' => (string) $workerId,
        ];
    }

    public function removeUser(string $username): void
    {
        $this->removed[] = $username;
    }
}
