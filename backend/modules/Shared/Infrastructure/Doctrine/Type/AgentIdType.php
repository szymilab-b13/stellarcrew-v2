<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Modules\Shared\Kernel\AgentId;

final class AgentIdType extends GuidType
{
    public const string NAME = 'agent_id_vo';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?AgentId
    {
        if ($value === null) {
            return null;
        }

        return AgentId::fromString((string) $value);
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
