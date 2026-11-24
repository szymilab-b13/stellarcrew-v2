<?php

declare(strict_types=1);

namespace Modules\Core\Api;

/**
 * Pushes an event with the given payload to a realtime topic. Consumers
 * (frontend, worker health dashboards) subscribe via Mercure/SSE and react
 * to whatever structure the publisher chooses to send. The payload is
 * encoded as JSON before transport.
 */
interface RealtimePublisher
{
    /**
     * @param array<string, mixed> $data
     */
    public function publish(string $topic, array $data): void;
}
