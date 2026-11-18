<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

interface PairingCodeGenerator
{
    public function generate(): string;
}
