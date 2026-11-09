<?php

declare(strict_types=1);

namespace Modules\Projects\Infrastructure\Bundle;

use function dirname;

use Modules\Shared\Infrastructure\Doctrine\Type\ProjectIdType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class ProjectsBundle extends AbstractBundle
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
                    ProjectIdType::NAME => ProjectIdType::class,
                ],
            ],
            'orm' => [
                'mappings' => [
                    'Projects' => [
                        'is_bundle' => false,
                        'type' => 'xml',
                        'dir' => $this->getPath() . '/Infrastructure/Doctrine/Mapping',
                        'prefix' => 'Modules\Projects\Domain',
                        'alias' => 'Projects',
                    ],
                ],
            ],
        ]);
    }
}
