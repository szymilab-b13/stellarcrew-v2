<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

enum ParticipantType: string
{
    case User = 'user';
    case Agent = 'agent';
    case System = 'system';
    case Supervisor = 'supervisor';
}
