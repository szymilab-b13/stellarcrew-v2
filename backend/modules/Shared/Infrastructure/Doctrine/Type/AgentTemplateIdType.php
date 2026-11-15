<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Modules\Shared\Kernel\AgentTemplateId;

final class AgentTemplateIdType extends GuidType
{
    public const string NAME = 'agent_template_id_vo';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?AgentTemplateId
    {
        if ($value === null) {
            return null;
        }

        return AgentTemplateId::fromString((string) $value);
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
