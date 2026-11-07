<?php

declare(strict_types=1);

namespace Modules\Workspaces\Api\Exception;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use RuntimeException;

use function sprintf;

final class MemberAlreadyExistsException extends RuntimeException
{
    public static function forPair(WorkspaceId $workspaceId, UserId $userId): self
    {
        return new self(sprintf(
            'User "%s" is already a member of workspace "%s".',
            (string) $userId,
            (string) $workspaceId,
        ));
    }
}
