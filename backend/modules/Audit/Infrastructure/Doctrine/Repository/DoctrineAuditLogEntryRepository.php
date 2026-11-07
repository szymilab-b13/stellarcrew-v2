<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Audit\Domain\AuditLogEntry;
use Modules\Audit\Domain\AuditLogEntryRepository;
use Modules\Shared\Kernel\AuditEntryId;

final readonly class DoctrineAuditLogEntryRepository implements AuditLogEntryRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(AuditLogEntry $entry): void
    {
        $this->em->persist($entry);
        $this->em->flush();
    }

    public function findById(AuditEntryId $id): ?AuditLogEntry
    {
        return $this->em->getRepository(AuditLogEntry::class)->findOneBy(['id' => $id]);
    }
}
