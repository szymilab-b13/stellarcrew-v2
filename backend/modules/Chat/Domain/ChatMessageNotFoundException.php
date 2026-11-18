<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use Modules\Shared\Kernel\ChatMessageId;
use RuntimeException;

use function sprintf;

final class ChatMessageNotFoundException extends RuntimeException
{
    public static function forId(ChatMessageId $id): self
    {
        return new self(sprintf('Chat message "%s" not found.', (string) $id));
    }
}
