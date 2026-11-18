<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Modules\Shared\Kernel\ChatMessageId;

final class ChatMessageIdType extends GuidType
{
    public const string NAME = 'chat_message_id_vo';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ChatMessageId
    {
        if ($value === null) {
            return null;
        }

        return ChatMessageId::fromString((string) $value);
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
