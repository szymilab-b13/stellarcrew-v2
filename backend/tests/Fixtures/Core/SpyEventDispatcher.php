<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Core;

use Psr\EventDispatcher\EventDispatcherInterface;

final class SpyEventDispatcher implements EventDispatcherInterface
{
    /** @var list<object> */
    public array $dispatched = [];

    public function dispatch(object $event): object
    {
        $this->dispatched[] = $event;

        return $event;
    }
}
