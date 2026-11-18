<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

enum ClassifiedBy: string
{
    case Sender = 'sender';
    case Supervisor = 'supervisor';
    case Manual = 'manual';
}
