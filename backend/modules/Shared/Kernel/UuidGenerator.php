<?php

declare(strict_types=1);

namespace Modules\Shared\Kernel;

use Symfony\Component\Uid\Uuid;

interface UuidGenerator
{
    public function generate(): Uuid;
}
