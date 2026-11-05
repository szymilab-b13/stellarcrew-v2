<?php

declare(strict_types=1);

namespace Modules\Shared\Kernel;

use DateInterval;
use DateTimeImmutable;

final class FrozenClock implements Clock
{
    public function __construct(private DateTimeImmutable $current)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->current;
    }

    public function advance(DateInterval $interval): void
    {
        $this->current = $this->current->add($interval);
    }
}
