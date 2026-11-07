<?php

declare(strict_types=1);

namespace Modules\Workspaces\Api\Exception;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use RuntimeException;

use function sprintf;

final class CannotRemoveOwnerException extends RuntimeException
{
    public static function forPair(WorkspaceId $workspaceId, UserId $userId): self
    {
        return new self(sprintf(
            'Owner "%s" of workspace "%s" cannot be removed; transfer ownership instead.',
            (string) $userId,
            (string) $workspaceId,
        ));
    }
}
