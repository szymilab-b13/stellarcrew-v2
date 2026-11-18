<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Modules\Shared\Kernel\ChatSessionId;

final class ChatSessionIdType extends GuidType
{
    public const string NAME = 'chat_session_id_vo';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ChatSessionId
    {
        if ($value === null) {
            return null;
        }

        return ChatSessionId::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
