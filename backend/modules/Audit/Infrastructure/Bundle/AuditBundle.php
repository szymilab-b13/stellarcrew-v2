<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Bundle;

use function dirname;

use Modules\Audit\Infrastructure\Doctrine\Type\AuditEntryIdType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class AuditBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return dirname(__DIR__, 2);
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    AuditEntryIdType::NAME => AuditEntryIdType::class,
                ],
            ],
            'orm' => [
                'mappings' => [
                    'Audit' => [
                        'is_bundle' => false,
                        'type' => 'xml',
                        'dir' => $this->getPath() . '/Infrastructure/Doctrine/Mapping',
                        'prefix' => 'Modules\Audit\Domain',
                        'alias' => 'Audit',
                    ],
                ],
            ],
        ]);
    }
}
