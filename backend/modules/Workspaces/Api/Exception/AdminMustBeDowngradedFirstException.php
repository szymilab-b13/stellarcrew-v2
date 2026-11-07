<?php

declare(strict_types=1);

namespace Modules\Workspaces\Api\Exception;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use RuntimeException;

use function sprintf;

final class AdminMustBeDowngradedFirstException extends RuntimeException
{
    public static function forPair(WorkspaceId $workspaceId, UserId $userId): self
    {
        return new self(sprintf(
            'Admin "%s" of workspace "%s" must be downgraded before removal.',
            (string) $userId,
            (string) $workspaceId,
        ));
    }
}
