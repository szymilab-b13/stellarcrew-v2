<?php

declare(strict_types=1);

namespace Modules\Shared\Api;

enum LimitType: string
{
    case Agents = 'agents';
    case Projects = 'projects';
    case Workspaces = 'workspaces';
    case Members = 'members';
}
