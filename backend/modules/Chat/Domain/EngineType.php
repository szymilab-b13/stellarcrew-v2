<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

enum EngineType: string
{
    case CliTerminal = 'cli_terminal';
    case Webhook = 'webhook';
    case Batch = 'batch';
}
