<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Modules\Shared\Kernel\WorkerId;

final class WorkerIdType extends GuidType
{
    public const string NAME = 'worker_id_vo';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?WorkerId
    {
        if ($value === null) {
            return null;
        }

        return WorkerId::fromString((string) $value);
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
