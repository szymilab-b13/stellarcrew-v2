<?php

declare(strict_types=1);

namespace Modules\Audit\Api\Exception;

use Modules\Shared\Kernel\AuditEntryId;
use RuntimeException;

use function sprintf;

final class AuditEntryNotFoundException extends RuntimeException
{
    public static function forId(AuditEntryId $id): self
    {
        return new self(sprintf('Audit entry "%s" not found.', (string) $id));
    }
}
