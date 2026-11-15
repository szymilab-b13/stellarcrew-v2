<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Bundle;

use function dirname;

use Modules\Shared\Infrastructure\Doctrine\Type\AgentIdType;
use Modules\Shared\Infrastructure\Doctrine\Type\AgentTemplateIdType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class AgentsBundle extends AbstractBundle
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
                    AgentIdType::NAME => AgentIdType::class,
                    AgentTemplateIdType::NAME => AgentTemplateIdType::class,
                ],
            ],
            'orm' => [
                'mappings' => [
                    'Agents' => [
                        'is_bundle' => false,
                        'type' => 'xml',
                        'dir' => $this->getPath() . '/Infrastructure/Doctrine/Mapping',
                        'prefix' => 'Modules\Agents\Domain',
                        'alias' => 'Agents',
                    ],
                ],
            ],
        ]);
    }
}
