<?php

declare(strict_types=1);

namespace Modules\Shared\Kernel;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
