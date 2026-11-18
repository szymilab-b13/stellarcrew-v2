<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

enum Priority: string
{
    case P0 = 'P0';
    case P1 = 'P1';
    case P2 = 'P2';
    case P3 = 'P3';
}
