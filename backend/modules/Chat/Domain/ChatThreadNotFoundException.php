<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use Modules\Shared\Kernel\ChatThreadId;
use RuntimeException;

use function sprintf;

final class ChatThreadNotFoundException extends RuntimeException
{
    public static function forId(ChatThreadId $id): self
    {
        return new self(sprintf('Chat thread "%s" not found.', (string) $id));
    }
}
