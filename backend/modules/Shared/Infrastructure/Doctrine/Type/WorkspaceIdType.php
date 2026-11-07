<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Modules\Shared\Kernel\WorkspaceId;

final class WorkspaceIdType extends GuidType
{
    public const string NAME = 'workspace_id_vo';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?WorkspaceId
    {
        if ($value === null) {
            return null;
        }

        return WorkspaceId::fromString((string) $value);
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
