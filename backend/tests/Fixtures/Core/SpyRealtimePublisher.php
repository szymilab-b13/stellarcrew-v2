<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Core;

use Modules\Core\Api\RealtimePublisher;

final class SpyRealtimePublisher implements RealtimePublisher
{
    /** @var list<array{topic: string, data: array<string, mixed>}> */
    public array $events = [];

    public function publish(string $topic, array $data): void
    {
        $this->events[] = ['topic' => $topic, 'data' => $data];
    }
}
