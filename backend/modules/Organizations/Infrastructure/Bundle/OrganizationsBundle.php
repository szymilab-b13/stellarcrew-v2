<?php

declare(strict_types=1);

namespace Modules\Organizations\Infrastructure\Bundle;

use function dirname;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class OrganizationsBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return dirname(__DIR__, 2);
    }
}
