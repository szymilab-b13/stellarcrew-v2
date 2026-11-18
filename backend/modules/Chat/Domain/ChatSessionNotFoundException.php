<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use Modules\Shared\Kernel\ChatSessionId;
use RuntimeException;

use function sprintf;

final class ChatSessionNotFoundException extends RuntimeException
{
    public static function forId(ChatSessionId $id): self
    {
        return new self(sprintf('Chat session "%s" not found.', (string) $id));
    }
}
