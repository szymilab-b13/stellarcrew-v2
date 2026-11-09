<?php

declare(strict_types=1);

namespace Modules\Projects\Domain;

use Modules\Shared\Kernel\ProjectId;
use RuntimeException;

use function sprintf;

final class ProjectNotFoundException extends RuntimeException
{
    public static function byId(ProjectId $id): self
    {
        return new self(sprintf('Project "%s" not found.', (string) $id));
    }
}
