<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Agents\Domain\AgentTemplate;
use Modules\Agents\Domain\AgentTemplateRepository;
use Modules\Shared\Kernel\AgentTemplateId;

final readonly class DoctrineAgentTemplateRepository implements AgentTemplateRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(AgentTemplate $template): void
    {
        $this->em->persist($template);
        $this->em->flush();
    }

    public function findById(AgentTemplateId $id): ?AgentTemplate
    {
        return $this->em->getRepository(AgentTemplate::class)->findOneBy(['id' => $id]);
    }

    public function findBySlug(string $slug): ?AgentTemplate
    {
        return $this->em->getRepository(AgentTemplate::class)->findOneBy(['slug' => $slug]);
    }

    public function existsSlug(string $slug): bool
    {
        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(AgentTemplate::class, 't')
            ->where('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function listAll(): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('t')
            ->from(AgentTemplate::class, 't')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();

        /** @var list<AgentTemplate> $rows */
        return $rows;
    }
}
