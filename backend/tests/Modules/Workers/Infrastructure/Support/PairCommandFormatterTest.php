<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Infrastructure\Support;

use Modules\Workers\Infrastructure\Support\PairCommandFormatter;
use PHPUnit\Framework\TestCase;

final class PairCommandFormatterTest extends TestCase
{
    public function testShouldComposeOneLineInstallCommandWhenGivenCode(): void
    {
        $sut = new PairCommandFormatter('https://app.stellarcrew.pl');

        $command = $sut->format('ABCD-EF23');

        self::assertSame(
            'curl -fsSL https://app.stellarcrew.pl/install-worker.sh | bash -s -- --code ABCD-EF23',
            $command,
        );
    }

    public function testShouldStripTrailingSlashFromBaseUrlWhenFormatting(): void
    {
        $sut = new PairCommandFormatter('https://app.stellarcrew.pl/');

        $command = $sut->format('XYZW-1234');

        self::assertSame(
            'curl -fsSL https://app.stellarcrew.pl/install-worker.sh | bash -s -- --code XYZW-1234',
            $command,
        );
    }

    public function testShouldWorkWithLocalDevUrlWhenNoCustomBaseConfigured(): void
    {
        $sut = new PairCommandFormatter('http://localhost:8000');

        $command = $sut->format('QRST-5678');

        self::assertSame(
            'curl -fsSL http://localhost:8000/install-worker.sh | bash -s -- --code QRST-5678',
            $command,
        );
    }
}
