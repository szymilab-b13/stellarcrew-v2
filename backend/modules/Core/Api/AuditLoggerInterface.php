<?php

declare(strict_types=1);

namespace Modules\Core\Api;

use Modules\Core\Api\Dto\AuditEntry;

/**
 * Public contract for recording audit entries from any module.
 *
 * Consumers inject this interface and never touch the Audit module directly.
 * Action naming convention: "<module>.<entity>.<verb>[.<qualifier>]".
 * Examples: "auth.user.login.success", "agents.agent.created", "chat.message.sent".
 */
interface AuditLoggerInterface
{
    public function log(AuditEntry $entry): void;
}
