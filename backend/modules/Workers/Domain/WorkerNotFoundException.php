<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

use DomainException;
use Modules\Shared\Kernel\WorkerId;

use function sprintf;

final class WorkerNotFoundException extends DomainException
{
    public static function forId(WorkerId $id): self
    {
        return new self(sprintf('Worker "%s" not found.', $id));
    }
}
