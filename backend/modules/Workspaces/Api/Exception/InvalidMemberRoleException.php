<?php

declare(strict_types=1);

namespace Modules\Workspaces\Api\Exception;

use RuntimeException;

use function sprintf;

final class InvalidMemberRoleException extends RuntimeException
{
    public static function ownerNotAssignable(): self
    {
        return new self('Role "owner" cannot be assigned through member management; use workspace transfer instead.');
    }

    public static function unknownRole(string $role): self
    {
        return new self(sprintf('Unknown workspace role: "%s".', $role));
    }
}
