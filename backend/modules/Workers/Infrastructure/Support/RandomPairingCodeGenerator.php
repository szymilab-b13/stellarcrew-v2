<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Support;

use Modules\Workers\Domain\PairingCodeGenerator;

use function random_int;
use function sprintf;
use function strlen;

final class RandomPairingCodeGenerator implements PairingCodeGenerator
{
    private const string ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 32 chars, no 0/O/1/I/L

    public function generate(): string
    {
        return sprintf('%s-%s', $this->generateBlock(), $this->generateBlock());
    }

    private function generateBlock(): string
    {
        $len = strlen(self::ALPHABET);
        $out = '';
        for ($i = 0; $i < 4; ++$i) {
            $out .= self::ALPHABET[random_int(0, $len - 1)];
        }

        return $out;
    }
}
