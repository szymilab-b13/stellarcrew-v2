<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Bundle;

use function dirname;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class AgentsBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return dirname(__DIR__, 2);
    }
}
