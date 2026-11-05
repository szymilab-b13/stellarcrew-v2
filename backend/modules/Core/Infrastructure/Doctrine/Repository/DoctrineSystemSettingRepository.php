<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Core\Domain\SystemSetting;
use Modules\Core\Domain\SystemSettingRepository;

final readonly class DoctrineSystemSettingRepository implements SystemSettingRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function get(string $name): ?SystemSetting
    {
        return $this->em->find(SystemSetting::class, $name);
    }

    public function save(SystemSetting $setting): void
    {
        $this->em->persist($setting);
        $this->em->flush();
    }
}
