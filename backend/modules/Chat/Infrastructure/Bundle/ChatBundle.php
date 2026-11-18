<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Bundle;

use function dirname;

use Modules\Shared\Infrastructure\Doctrine\Type\ChatMessageIdType;
use Modules\Shared\Infrastructure\Doctrine\Type\ChatSessionIdType;
use Modules\Shared\Infrastructure\Doctrine\Type\ChatThreadIdType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class ChatBundle extends AbstractBundle
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
                    ChatSessionIdType::NAME => ChatSessionIdType::class,
                    ChatThreadIdType::NAME => ChatThreadIdType::class,
                    ChatMessageIdType::NAME => ChatMessageIdType::class,
                ],
            ],
            'orm' => [
                'mappings' => [
                    'Chat' => [
                        'is_bundle' => false,
                        'type' => 'xml',
                        'dir' => $this->getPath() . '/Infrastructure/Doctrine/Mapping',
                        'prefix' => 'Modules\Chat\Domain',
                        'alias' => 'Chat',
                    ],
                ],
            ],
        ]);
    }
}
