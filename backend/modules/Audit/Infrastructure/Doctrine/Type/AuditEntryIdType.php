<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Modules\Shared\Kernel\AuditEntryId;

final class AuditEntryIdType extends GuidType
{
    public const string NAME = 'audit_entry_id_vo';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?AuditEntryId
    {
        if ($value === null) {
            return null;
        }

        return AuditEntryId::fromString((string) $value);
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
