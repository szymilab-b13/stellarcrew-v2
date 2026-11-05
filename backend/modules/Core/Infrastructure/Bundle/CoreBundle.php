<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Bundle;

use function dirname;

use Modules\Shared\Infrastructure\Doctrine\Type\UserIdType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class CoreBundle extends AbstractBundle
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
                    UserIdType::NAME => UserIdType::class,
                ],
            ],
            'orm' => [
                'mappings' => [
                    'Core' => [
                        'is_bundle' => false,
                        'type' => 'xml',
                        'dir' => $this->getPath() . '/Infrastructure/Doctrine/Mapping',
                        'prefix' => 'Modules\Core\Domain',
                        'alias' => 'Core',
                    ],
                ],
            ],
        ]);
    }
}
