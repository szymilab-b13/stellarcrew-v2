<?php

declare(strict_types=1);

namespace Modules\Workspaces\Domain;

use Modules\Shared\Kernel\WorkspaceId;
use RuntimeException;

use function sprintf;

final class WorkspaceNotFoundException extends RuntimeException
{
    public static function byId(WorkspaceId $id): self
    {
        return new self(sprintf('Workspace "%s" not found.', (string) $id));
    }
}
