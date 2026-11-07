<?php

declare(strict_types=1);

namespace Modules\Audit\Api\Exception;

use RuntimeException;

use function sprintf;

final class UndoNotSupportedException extends RuntimeException
{
    public static function forAction(string $action): self
    {
        return new self(sprintf('Action "%s" cannot be undone.', $action));
    }
}
