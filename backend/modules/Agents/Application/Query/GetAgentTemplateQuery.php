<?php

declare(strict_types=1);

namespace Modules\Agents\Application\Query;

final readonly class GetAgentTemplateQuery
{
    public function __construct(public string $idOrSlug)
    {
    }
}
