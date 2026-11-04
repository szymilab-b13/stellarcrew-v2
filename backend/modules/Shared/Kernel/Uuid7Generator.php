<?php

declare(strict_types=1);

namespace Modules\Shared\Kernel;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class Uuid7Generator implements UuidGenerator
{
    public function generate(): UuidV7
    {
        return Uuid::v7();
    }
}
