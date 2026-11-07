<?php

declare(strict_types=1);

namespace Modules\Workspaces\Domain;

use Modules\Shared\Kernel\WorkspaceId;
use RuntimeException;

use function sprintf;

final class WorkspacePermissionDeniedException extends RuntimeException
{
    public static function forEdit(WorkspaceId $id): self
    {
        return new self(sprintf('Permission denied: cannot edit workspace "%s".', (string) $id));
    }

    public static function forDelete(WorkspaceId $id): self
    {
        return new self(sprintf('Permission denied: cannot delete workspace "%s".', (string) $id));
    }
}
