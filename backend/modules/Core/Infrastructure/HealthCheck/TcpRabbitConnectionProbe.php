<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\HealthCheck;

use function fclose;
use function fsockopen;

use RuntimeException;

final readonly class TcpRabbitConnectionProbe implements RabbitConnectionProbe
{
    public function __construct(
        private string $host,
        private int $port,
        private float $timeoutSeconds = 2.0,
    ) {
    }

    public function ping(): void
    {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeoutSeconds);

        if ($socket === false) {
            throw new RuntimeException($errstr !== '' ? $errstr : 'socket connect failed');
        }

        fclose($socket);
    }
}
